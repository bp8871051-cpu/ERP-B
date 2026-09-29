<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['vendor', 'supplier', 'warehouse', 'creator', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        return $query->latest('po_date')->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): PurchaseOrder
    {
        $query = PurchaseOrder::query()->with([
            'vendor',
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
            'purchases',
        ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->findOrFail($id);
    }

    public function create(array $orderData, array $itemsData, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($orderData, $itemsData, $userId) {
            if (empty($orderData['po_number'])) {
                $count = PurchaseOrder::where('company_id', $orderData['company_id'] ?? 1)->count() + 1;
                $orderData['po_number'] = 'PO-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $orderData['po_date'] = $orderData['po_date'] ?? Carbon::now()->toDateString();
            $orderData['order_date'] = $orderData['po_date'];
            $orderData['status'] = $orderData['status'] ?? 'draft';
            $orderData['payment_status'] = $orderData['payment_status'] ?? 'unpaid';
            $orderData['created_by'] = $userId;

            $subtotal = 0;
            $tax = 0;
            $discount = 0;

            foreach ($itemsData as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? $item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);

                $subtotal += ($qty * $cost);
                $discount += $disc;
                $tax += $tx;
            }

            $shipping = (float) ($orderData['shipping'] ?? 0);
            $grandTotal = $subtotal - $discount + $tax + $shipping;

            $orderData['subtotal'] = $subtotal;
            $orderData['discount'] = $discount;
            $orderData['tax'] = $tax;
            $orderData['shipping'] = $shipping;
            $orderData['total'] = $grandTotal;
            $orderData['grand_total'] = $grandTotal;

            $po = PurchaseOrder::create($orderData);

            foreach ($itemsData as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? $item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);
                $lineSubtotal = ($qty * $cost) - $disc + $tx;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'unit_price' => $cost,
                    'discount' => $disc,
                    'tax' => $tx,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineSubtotal,
                ]);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $po->company_id,
                'action' => 'purchase_order.created',
                'module' => 'purchase',
                'record_id' => $po->id,
                'new_values' => ['po_number' => $po->po_number, 'grand_total' => $grandTotal],
            ]);

            return $po->load(['vendor', 'items.product']);
        });
    }

    public function approve(int $id, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($id, $userId) {
            $po = PurchaseOrder::findOrFail($id);
            $po->status = 'approved';
            $po->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $po->company_id,
                'action' => 'purchase_order.approved',
                'module' => 'purchase',
                'record_id' => $po->id,
            ]);

            return $po;
        });
    }

    public function cancel(int $id, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($id, $userId) {
            $po = PurchaseOrder::findOrFail($id);
            if ($po->status === 'received') {
                throw new Exception("Received purchase orders cannot be cancelled.");
            }
            $po->status = 'cancelled';
            $po->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $po->company_id,
                'action' => 'purchase_order.cancelled',
                'module' => 'purchase',
                'record_id' => $po->id,
            ]);

            return $po;
        });
    }

    public function createOrder(array $orderData, array $itemsData, ?int $userId = null): PurchaseOrder
    {
        return $this->create($orderData, $itemsData, $userId);
    }

    public function approveOrder(int $id, ?int $userId = null): PurchaseOrder
    {
        return $this->approve($id, $userId);
    }

    public function cancelOrder(int $id, ?string $reason = null, ?int $userId = null): PurchaseOrder
    {
        return $this->cancel($id, $userId);
    }

    public function receiveOrder(int $id, array $extraData = [], ?int $userId = null): Purchase
    {
        $po = PurchaseOrder::with(['items'])->findOrFail($id);
        $purchaseService = app(PurchaseService::class);

        $purchaseData = array_merge([
            'company_id' => $po->company_id,
            'vendor_id' => $po->vendor_id,
            'purchase_order_id' => $po->id,
            'warehouse_id' => $po->warehouse_id ?? 1,
            'vendor_invoice_number' => $extraData['vendor_invoice_number'] ?? 'VINV-' . $po->po_number,
            'invoice_date' => $extraData['invoice_date'] ?? Carbon::now()->toDateString(),
            'subtotal' => $po->subtotal,
            'discount' => $po->discount,
            'tax' => $po->tax,
            'shipping' => $po->shipping,
            'grand_total' => $po->grand_total,
        ], $extraData);

        $items = [];
        foreach ($po->items as $item) {
            $items[] = [
                'product_id' => $item->product_id,
                'unit_id' => $item->unit_id,
                'quantity' => $item->quantity,
                'unit_cost' => $item->unit_cost ?? $item->unit_price,
                'discount' => $item->discount,
                'tax' => $item->tax,
            ];
        }

        return $purchaseService->receivePurchase($purchaseData, $items, $userId);
    }
}
