<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Vendor;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseReturn::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['vendor', 'purchase', 'warehouse', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->latest('return_date')->paginate($perPage);
    }

    public function create(array $data, array $items, ?int $userId = null): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $companyId = $data['company_id'] ?? 1;

            if (empty($data['return_number'])) {
                $count = PurchaseReturn::where('company_id', $companyId)->count() + 1;
                $data['return_number'] = 'PRET-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $subtotal = 0;
            $tax = 0;
            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);

                $subtotal += ($qty * $cost);
                $tax += $tx;
            }

            $grandTotal = $subtotal + $tax;
            $data['return_date'] = $data['return_date'] ?? Carbon::now()->toDateString();
            $data['subtotal'] = $subtotal;
            $data['tax'] = $tax;
            $data['grand_total'] = $grandTotal;
            $data['status'] = $data['status'] ?? 'draft';
            $data['created_by'] = $userId;

            $return = PurchaseReturn::create($data);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $cost = (float) ($item['unit_cost'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'tax' => $tx,
                    'total' => ($qty * $cost) + $tx,
                ]);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => 'purchase_return.created',
                'module' => 'purchase',
                'record_id' => $return->id,
                'new_values' => ['return_number' => $return->return_number, 'grand_total' => $grandTotal],
            ]);

            return $return->load(['vendor', 'items.product']);
        });
    }

    /**
     * Complete Purchase Return:
     * - Decreases stock
     * - Creates stock movement: PURCHASE_RETURN_OUT
     * - Reduces vendor payable
     */
    public function complete(int $id, ?int $userId = null): PurchaseReturn
    {
        return DB::transaction(function () use ($id, $userId) {
            $return = PurchaseReturn::with(['items', 'vendor'])->findOrFail($id);

            if ($return->status === 'completed') {
                throw new Exception("Purchase return is already completed.");
            }

            $warehouseId = $return->warehouse_id;
            $companyId = $return->company_id;

            foreach ($return->items as $item) {
                // 1. Decrease stock and record movement
                $this->stockService->decreaseStock(
                    $item->product_id,
                    $warehouseId,
                    $item->quantity,
                    $return->return_number,
                    'purchase_return_out',
                    "Purchase Return to Vendor #{$return->return_number}",
                    $userId,
                    $companyId
                );
            }

            // 2. Reduce Vendor Payable balance
            if ($return->vendor) {
                $return->vendor->decrement('balance', min($return->vendor->balance, $return->grand_total));
            }

            $return->status = 'completed';
            $return->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => 'purchase_return.completed',
                'module' => 'purchase',
                'record_id' => $return->id,
                'new_values' => ['status' => 'completed', 'stock_decreased' => true],
            ]);

            return $return;
        });
    }
}
