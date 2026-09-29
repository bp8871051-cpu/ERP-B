<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Purchase::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['vendor', 'purchaseOrder', 'warehouse', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhere('vendor_invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->latest('invoice_date')->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): Purchase
    {
        $query = Purchase::query()->with([
            'vendor',
            'purchaseOrder',
            'warehouse',
            'items.product',
            'items.unit',
            'returns',
            'payments',
            'creator',
        ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->findOrFail($id);
    }

    public function createPurchase(array $data, array $items, ?int $userId = null): Purchase
    {
        return $this->receivePurchase($data, $items, $userId);
    }

    /**
     * Create and atomically receive a Purchase:
     * - Increases inventory stock
     * - Creates stock movement: PURCHASE_IN
     * - Increases vendor payable balance
     */
    public function receivePurchase(array $data, array $items, ?int $userId = null): Purchase
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $companyId = $data['company_id'] ?? 1;
            $warehouseId = $data['warehouse_id'] ?? 1;

            if (empty($data['purchase_number'])) {
                $count = Purchase::where('company_id', $companyId)->count() + 1;
                $data['purchase_number'] = 'PUR-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $subtotal = 0;
            $discount = 0;
            $tax = 0;

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);

                $subtotal += ($qty * $cost);
                $discount += $disc;
                $tax += $tx;
            }

            $shipping = (float) ($data['shipping'] ?? 0);
            $grandTotal = $subtotal - $discount + $tax + $shipping;

            $data['invoice_date'] = $data['invoice_date'] ?? Carbon::now()->toDateString();
            $data['subtotal'] = $subtotal;
            $data['discount'] = $discount;
            $data['tax'] = $tax;
            $data['shipping'] = $shipping;
            $data['grand_total'] = $grandTotal;
            $data['paid_amount'] = 0;
            $data['due_amount'] = $grandTotal;
            $data['payment_status'] = 'unpaid';
            $data['status'] = 'received';
            $data['created_by'] = $userId;

            $purchase = Purchase::create($data);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);
                $lineSubtotal = ($qty * $cost) - $disc + $tx;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'discount' => $disc,
                    'tax' => $tx,
                    'subtotal' => $lineSubtotal,
                ]);

                // 1. Increase stock and create movement PURCHASE_IN
                $this->stockService->increaseStock(
                    $item['product_id'],
                    $warehouseId,
                    $qty,
                    $cost,
                    $purchase->purchase_number,
                    'purchase_in',
                    "Goods received under Purchase #{$purchase->purchase_number}",
                    $userId,
                    $companyId
                );
            }

            // 2. Increase Vendor Payable balance
            $vendor = Vendor::find($purchase->vendor_id);
            if ($vendor) {
                $vendor->increment('balance', $grandTotal);
            }

            // 3. If linked to PurchaseOrder, update PO status
            if ($purchase->purchase_order_id) {
                PurchaseOrder::where('id', $purchase->purchase_order_id)->update(['status' => 'received']);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => 'purchase.received',
                'module' => 'purchase',
                'record_id' => $purchase->id,
                'new_values' => ['purchase_number' => $purchase->purchase_number, 'grand_total' => $grandTotal],
            ]);

            return $purchase->load(['vendor', 'items.product', 'warehouse']);
        });
    }

    public function recordPayment(int $purchaseId, array $paymentData, ?int $userId = null): VendorPayment
    {
        return DB::transaction(function () use ($purchaseId, $paymentData, $userId) {
            $purchase = Purchase::with('vendor')->findOrFail($purchaseId);
            $amount = (float) ($paymentData['amount'] ?? 0);

            if ($amount <= 0) {
                throw new Exception("Payment amount must be greater than zero.");
            }

            $count = VendorPayment::where('company_id', $purchase->company_id)->count() + 1;
            $paymentNumber = 'VPAY-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

            $payment = VendorPayment::create([
                'company_id' => $purchase->company_id,
                'vendor_id' => $purchase->vendor_id,
                'purchase_id' => $purchase->id,
                'payment_number' => $paymentNumber,
                'amount' => $amount,
                'payment_date' => $paymentData['payment_date'] ?? Carbon::now()->toDateString(),
                'payment_method' => $paymentData['payment_method'] ?? 'bank_transfer',
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $newPaid = (float) $purchase->paid_amount + $amount;
            $newDue = max(0, (float) $purchase->grand_total - $newPaid);
            $status = $newDue <= 0.01 ? 'paid' : 'partially_paid';

            $purchase->paid_amount = $newPaid;
            $purchase->due_amount = $newDue;
            $purchase->payment_status = $status;
            $purchase->save();

            // Reduce vendor payable balance
            if ($purchase->vendor) {
                $purchase->vendor->decrement('balance', $amount);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $purchase->company_id,
                'action' => 'vendor_payment.recorded',
                'module' => 'purchase',
                'record_id' => $purchase->id,
                'new_values' => ['payment_number' => $paymentNumber, 'amount' => $amount],
            ]);

            return $payment;
        });
    }
}
