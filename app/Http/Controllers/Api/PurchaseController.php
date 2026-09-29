<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProcurementAnalyticsService;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use App\Services\VendorService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        protected VendorService $vendorService,
        protected PurchaseOrderService $purchaseOrderService,
        protected PurchaseService $purchaseService,
        protected PurchaseReturnService $purchaseReturnService,
        protected ProcurementAnalyticsService $procurementAnalyticsService,
    ) {}

    // DASHBOARD & ANALYTICS
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $data = $this->procurementAnalyticsService->getDashboardMetrics($companyId);
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function analytics(Request $request): JsonResponse
    {
        return $this->dashboard($request);
    }

    // VENDORS
    public function vendors(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $vendors = $this->vendorService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $vendors]);
    }

    public function showVendor(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $vendor = $this->vendorService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $vendor]);
    }

    public function storeVendor(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->all();
        $data['company_id'] = $companyId;

        $vendor = $this->vendorService->create($data, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Vendor created successfully', 'data' => $vendor], 201);
    }

    public function updateVendor(Request $request, int $id): JsonResponse
    {
        $vendor = $this->vendorService->update($id, $request->all(), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Vendor updated successfully', 'data' => $vendor]);
    }

    public function destroyVendor(Request $request, int $id): JsonResponse
    {
        $this->vendorService->delete($id, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Vendor deleted successfully']);
    }

    // PURCHASE ORDERS
    public function orders(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $orders = $this->purchaseOrderService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $orders]);
    }

    public function showOrder(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $order = $this->purchaseOrderService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $order]);
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $orderData = $request->except('items');
        $orderData['company_id'] = $companyId;

        $order = $this->purchaseOrderService->createOrder($orderData, $request->input('items', []), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Purchase Order created', 'data' => $order], 201);
    }

    public function approveOrder(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->purchaseOrderService->approveOrder($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Purchase order approved', 'data' => $order]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function receiveOrder(Request $request, int $id): JsonResponse
    {
        try {
            $purchase = $this->purchaseOrderService->receiveOrder($id, $request->all(), $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Goods received and purchase record created', 'data' => $purchase]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelOrder(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->purchaseOrderService->cancelOrder($id, $request->input('reason'), $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Purchase order cancelled', 'data' => $order]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // PURCHASES (RECEIPTS & DIRECT PURCHASES)
    public function purchases(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $purchases = $this->purchaseService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $purchases]);
    }

    public function showPurchase(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $purchase = $this->purchaseService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $purchase]);
    }

    public function storePurchase(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $purchaseData = $request->except('items');
        $purchaseData['company_id'] = $companyId;

        $purchase = $this->purchaseService->createPurchase($purchaseData, $request->input('items', []), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Purchase created and stock received successfully', 'data' => $purchase], 201);
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'nullable|date',
        ]);

        try {
            $payment = $this->purchaseService->recordPayment($id, $request->all(), $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Payment recorded successfully', 'data' => $payment]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // PURCHASE RETURNS
    public function returns(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $returns = $this->purchaseReturnService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $returns]);
    }

    public function storeReturn(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $returnData = $request->except('items');
        $returnData['company_id'] = $companyId;

        $return = $this->purchaseReturnService->createReturn($returnData, $request->input('items', []), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Purchase return request created', 'data' => $return], 201);
    }

    public function approveReturn(Request $request, int $id): JsonResponse
    {
        try {
            $return = $this->purchaseReturnService->approveReturn($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Purchase return approved', 'data' => $return]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function completeReturn(Request $request, int $id): JsonResponse
    {
        try {
            $return = $this->purchaseReturnService->completeReturn($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Purchase return completed and stock deducted', 'data' => $return]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}
