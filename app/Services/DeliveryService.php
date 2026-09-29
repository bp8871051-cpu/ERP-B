<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\SalesOrder;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = DeliveryNote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['customer', 'salesOrder', 'warehouse', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('delivery_number', 'like', "%{$search}%")
                  ->orWhere('driver_name', 'like', "%{$search}%")
                  ->orWhere('vehicle_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->latest('delivery_date')->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): DeliveryNote
    {
        $query = DeliveryNote::query()->with([
            'customer',
            'salesOrder.items.product',
            'warehouse',
            'items.product',
            'creator',
        ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->findOrFail($id);
    }

    public function create(array $data, array $items, ?int $userId = null): DeliveryNote
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            if (empty($data['delivery_number'])) {
                $count = DeliveryNote::where('company_id', $data['company_id'] ?? 1)->count() + 1;
                $data['delivery_number'] = 'DN-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $data['status'] = $data['status'] ?? 'pending';
            $data['created_by'] = $userId;

            $deliveryNote = DeliveryNote::create($data);

            foreach ($items as $item) {
                $ordered = (int) ($item['ordered_quantity'] ?? $item['quantity'] ?? 1);
                $delivered = (int) ($item['delivered_quantity'] ?? $ordered);
                $remaining = max(0, $ordered - $delivered);

                DeliveryNoteItem::create([
                    'delivery_note_id' => $deliveryNote->id,
                    'product_id' => $item['product_id'],
                    'ordered_quantity' => $ordered,
                    'delivered_quantity' => $delivered,
                    'remaining_quantity' => $remaining,
                ]);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $deliveryNote->company_id,
                'action' => 'delivery_note.created',
                'module' => 'sales',
                'record_id' => $deliveryNote->id,
                'new_values' => ['delivery_number' => $deliveryNote->delivery_number],
            ]);

            return $deliveryNote->load(['customer', 'items.product']);
        });
    }

    public function dispatch(int $id, ?int $userId = null): DeliveryNote
    {
        return DB::transaction(function () use ($id, $userId) {
            $note = DeliveryNote::findOrFail($id);
            $note->status = 'dispatched';
            $note->save();

            if ($note->sales_order_id) {
                SalesOrder::where('id', $note->sales_order_id)->update(['status' => 'processing']);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $note->company_id,
                'action' => 'delivery_note.dispatched',
                'module' => 'sales',
                'record_id' => $note->id,
            ]);

            return $note;
        });
    }

    /**
     * Mark Delivery as Delivered: Deducts reserved stock and records stock movement.
     */
    public function deliver(int $id, ?int $userId = null): DeliveryNote
    {
        return DB::transaction(function () use ($id, $userId) {
            $note = DeliveryNote::with(['items', 'salesOrder'])->findOrFail($id);

            if ($note->status === 'delivered') {
                throw new Exception("Delivery note has already been delivered.");
            }

            $warehouseId = $note->warehouse_id;

            // Deduct stock for each item from reserved stock
            foreach ($note->items as $item) {
                $this->stockService->deductReservedStock(
                    $item->product_id,
                    $warehouseId,
                    $item->delivered_quantity,
                    $note->delivery_number,
                    'delivery_out',
                    "Delivered under Delivery Note #{$note->delivery_number}",
                    $userId,
                    $note->company_id
                );
            }

            $note->status = 'delivered';
            $note->save();

            // Update Sales Order status
            if ($note->sales_order_id) {
                SalesOrder::where('id', $note->sales_order_id)->update(['status' => 'delivered']);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $note->company_id,
                'action' => 'delivery_note.delivered',
                'module' => 'sales',
                'record_id' => $note->id,
                'new_values' => ['status' => 'delivered', 'stock_deducted' => true],
            ]);

            return $note;
        });
    }
}
