<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosOrder;
use App\Models\PosPrintSetting;
use App\Models\Product;
use App\Services\BarcodeService;
use App\Services\PosCheckoutService;
use App\Services\PosReceiptService;
use App\Services\PosRefundService;
use App\Services\PosRegisterService;
use App\Services\PosService;
use App\Services\QrCodeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected PosService $posService;
    protected PosCheckoutService $checkoutService;
    protected PosRefundService $refundService;
    protected PosReceiptService $receiptService;
    protected PosRegisterService $registerService;
    protected BarcodeService $barcodeService;
    protected QrCodeService $qrCodeService;

    public function __construct(
        PosService $posService,
        PosCheckoutService $checkoutService,
        PosRefundService $refundService,
        PosReceiptService $receiptService,
        PosRegisterService $registerService,
        BarcodeService $barcodeService,
        QrCodeService $qrCodeService
    ) {
        $this->posService = $posService;
        $this->checkoutService = $checkoutService;
        $this->refundService = $refundService;
        $this->receiptService = $receiptService;
        $this->registerService = $registerService;
        $this->barcodeService = $barcodeService;
        $this->qrCodeService = $qrCodeService;
    }

    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?: 1);
    }

    /**
     * POS Dashboard API
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $data = $this->posService->getDashboardData($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * Search products for POS with category/brand filters and debounced query
     */
    public function products(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $query = Product::with(['category', 'brand', 'unit'])
            ->where('company_id', $companyId)
            ->where('status', 'active');

        if ($search = $request->input('search')) {
            $s = '%' . $search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('sku', 'like', $s)
                    ->orWhere('barcode', 'like', $s)
                    ->orWhere('product_code', 'like', $s);
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        $products = $query->paginate($request->input('per_page', 24));

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Calculate cart totals securely
     */
    public function calculate(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $items = $request->input('items', []);
        $discount = (float) $request->input('discount', 0);

        try {
            $calc = $this->checkoutService->calculate($items, $discount, $companyId);
            return response()->json([
                'status' => 'success',
                'data' => $calc,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List POS orders with pagination and search
     */
    public function orders(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $query = PosOrder::with(['customer', 'cashier', 'items.product', 'payments'])
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $s = '%' . $search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', $s)
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', $s)->orWhere('phone', 'like', $s));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('order_status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        $orders = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    /**
     * Store POS Order / Checkout
     */
    public function store(Request $request): JsonResponse
    {
        return $this->checkout($request);
    }

    public function checkout(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->checkoutService->checkout($request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'POS Order completed successfully.',
                'data' => $result,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show single POS order details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $order = PosOrder::with([
            'customer',
            'cashier',
            'warehouse',
            'invoice',
            'items.product',
            'payments',
            'refunds.items.product',
            'receipt'
        ])
        ->where('company_id', $companyId)
        ->findOrFail($id);

        $receiptData = $this->receiptService->generateReceiptData($order);

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'receipt' => $receiptData,
            ],
        ]);
    }

    /**
     * Process Refund for a POS Order
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        $request->validate([
            'reason' => 'required|string',
            'payment_method' => 'nullable|string',
        ]);

        try {
            $result = $this->refundService->processRefund($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Refund processed successfully and inventory updated.',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel POS Order
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $order = PosOrder::where('company_id', $companyId)->findOrFail($id);

        if ($order->order_status === 'refunded' || $order->order_status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => "Order is already {$order->order_status}.",
            ], 422);
        }

        $order->update([
            'order_status' => 'cancelled',
            'payment_status' => 'refunded',
            'status' => 'cancelled',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "POS Order {$order->order_number} cancelled.",
            'data' => $order,
        ]);
    }

    /**
     * Get Barcode for product or code
     */
    public function barcodes(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $products = Product::where('company_id', $companyId)
            ->where('status', 'active')
            ->select('id', 'name', 'sku', 'barcode', 'selling_price')
            ->take(50)
            ->get()
            ->map(function ($p) {
                $code = $p->barcode ?: $p->sku;
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'barcode' => $code,
                    'selling_price' => (float) $p->selling_price,
                    'barcode_svg' => $this->barcodeService->generate($code),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Generate barcode SVG / Data URL
     */
    public function generateBarcode(Request $request): JsonResponse
    {
        $code = $request->input('code', 'PROD-0001');
        $type = $request->input('type', 'Code128');
        $svgData = $this->barcodeService->generate($code, $type);

        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $code,
                'type' => $type,
                'barcode' => $svgData,
            ],
        ]);
    }

    /**
     * Generate QR code SVG / Data URL
     */
    public function generateQr(Request $request): JsonResponse
    {
        $content = $request->input('content', 'https://falconerp.com');
        $size = (int) $request->input('size', 200);
        $svgData = $this->qrCodeService->generate($content, $size);

        return response()->json([
            'status' => 'success',
            'data' => [
                'content' => $content,
                'qr' => $svgData,
            ],
        ]);
    }

    /**
     * Get POS Print Settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $settings = PosPrintSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'receipt_width' => 80,
                'show_logo' => true,
                'show_gst' => true,
                'show_sku' => true,
                'show_barcode' => true,
                'show_qr' => true,
                'footer_text' => 'Goods once sold will only be exchanged as per policy.',
                'thank_you_message' => 'Thank you for shopping with us! Have a wonderful day.',
                'auto_print' => false,
                'print_copies' => 1,
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $settings,
        ]);
    }

    /**
     * Update POS Print Settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $settings = PosPrintSetting::where('company_id', $companyId)->first();
        $payload = $request->only([
            'receipt_width', 'show_logo', 'company_name', 'company_address', 
            'company_phone', 'company_gstin', 'show_gst', 'show_sku', 
            'show_barcode', 'show_qr', 'header_text', 'footer_text', 
            'thank_you_message', 'auto_print', 'print_copies'
        ]);

        if (!$settings) {
            $payload['company_id'] = $companyId;
            $settings = PosPrintSetting::create($payload);
        } else {
            $settings->update($payload);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'POS Print settings saved successfully.',
            'data' => $settings,
        ]);
    }

    /**
     * Get Current Register Session
     */
    public function currentRegisterSession(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;
        $session = $this->registerService->getCurrentSession($companyId, $userId);

        return response()->json([
            'status' => 'success',
            'data' => $session,
        ]);
    }

    /**
     * Open Register Session
     */
    public function openRegisterSession(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;
        $session = $this->registerService->openSession($request->all(), $userId, $companyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Register session opened.',
            'data' => $session,
        ]);
    }

    /**
     * Close Register Session
     */
    public function closeRegisterSession(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $session = $this->registerService->closeSession($id, $request->all(), $companyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Register session closed and reconciled.',
            'data' => $session,
        ]);
    }
}
