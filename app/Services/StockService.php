<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get or create a stock row for product and warehouse.
     */
    public function getOrCreateStock(int $productId, int $warehouseId, ?int $companyId = null): Stock
    {
        return Stock::firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'company_id' => $companyId,
                'quantity' => 0,
                'available_quantity' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'average_cost' => 0,
            ]
        );
    }

    /**
     * Increase stock (e.g. Purchase, Opening Stock, Return, Manual Add).
     */
    public function increaseStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        float $unitCost = 0,
        string $reference = '',
        string $type = 'in',
        string $notes = '',
        ?int $userId = null,
        ?int $companyId = null
    ): Stock {
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $unitCost, $reference, $type, $notes, $userId, $companyId) {
            $stock = $this->getOrCreateStock($productId, $warehouseId, $companyId);
            $product = Product::find($productId);

            if ($unitCost <= 0 && $product) {
                $unitCost = (float) ($product->purchase_price ?? $product->cost_price ?? 0);
            }

            // Calculate new average cost
            $currentTotalQty = $stock->available_quantity + $stock->reserved_quantity;
            $currentTotalVal = $currentTotalQty * (float) $stock->average_cost;
            $newAdditionVal = $quantity * $unitCost;
            $newTotalQty = $currentTotalQty + $quantity;
            $newAvgCost = $newTotalQty > 0 ? ($currentTotalVal + $newAdditionVal) / $newTotalQty : $unitCost;

            $stock->available_quantity += $quantity;
            $stock->average_cost = round($newAvgCost, 2);
            $stock->last_movement_at = Carbon::now();
            $stock->save();

            // Record stock movement ledger entry
            $this->createMovement([
                'company_id' => $companyId ?? $stock->company_id,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'in',
                'movement_type' => $type,
                'quantity' => $quantity,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'balance_quantity' => $stock->available_quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            return $stock;
        });
    }

    /**
     * Decrease stock (e.g. Issue, Sale, Damage, Manual Remove).
     */
    public function decreaseStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        string $reference = '',
        string $type = 'out',
        string $notes = '',
        ?int $userId = null,
        ?int $companyId = null,
        bool $allowNegative = false
    ): Stock {
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $reference, $type, $notes, $userId, $companyId, $allowNegative) {
            $stock = $this->getOrCreateStock($productId, $warehouseId, $companyId);
            $product = Product::find($productId);

            if (!$allowNegative && $stock->available_quantity < $quantity) {
                throw new Exception("Insufficient stock available in this warehouse. Available: {$stock->available_quantity}, Requested: {$quantity}");
            }

            $unitCost = (float) ($stock->average_cost > 0 ? $stock->average_cost : ($product->purchase_price ?? $product->cost_price ?? 0));

            $stock->available_quantity -= $quantity;
            $stock->last_movement_at = Carbon::now();
            $stock->save();

            // Record stock movement ledger entry
            $this->createMovement([
                'company_id' => $companyId ?? $stock->company_id,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'out',
                'movement_type' => $type,
                'quantity' => $quantity,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'balance_quantity' => $stock->available_quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            return $stock;
        });
    }

    /**
     * Reserve stock for order/shipment.
     */
    public function reserveStock(int $productId, int $warehouseId, int $quantity): Stock
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $stock = $this->getOrCreateStock($productId, $warehouseId);
            if ($stock->available_quantity < $quantity) {
                throw new Exception("Insufficient available stock to reserve.");
            }
            $stock->available_quantity -= $quantity;
            $stock->reserved_quantity += $quantity;
            $stock->save();
            return $stock;
        });
    }

    /**
     * Release previously reserved stock.
     */
    public function releaseStock(int $productId, int $warehouseId, int $quantity): Stock
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $stock = $this->getOrCreateStock($productId, $warehouseId);
            $toRelease = min($stock->reserved_quantity, $quantity);
            $stock->reserved_quantity -= $toRelease;
            $stock->available_quantity += $toRelease;
            $stock->save();
            return $stock;
        });
    }

    /**
     * Deduct reserved stock upon delivery completion and create stock movement.
     */
    public function deductReservedStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        string $reference = '',
        string $type = 'delivery_out',
        string $notes = '',
        ?int $userId = null,
        ?int $companyId = null
    ): Stock {
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than zero.");
        }

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $reference, $type, $notes, $userId, $companyId) {
            $stock = $this->getOrCreateStock($productId, $warehouseId, $companyId);
            $product = Product::find($productId);

            // Deduct from reserved stock (or available if unreserved)
            if ($stock->reserved_quantity >= $quantity) {
                $stock->reserved_quantity -= $quantity;
            } else {
                $remaining = $quantity - $stock->reserved_quantity;
                $stock->reserved_quantity = 0;
                $stock->available_quantity -= $remaining;
            }
            $stock->last_movement_at = Carbon::now();
            $stock->save();

            $unitCost = (float) ($stock->average_cost > 0 ? $stock->average_cost : ($product->purchase_price ?? $product->cost_price ?? 0));

            // Record movement OUT
            $this->createMovement([
                'company_id' => $companyId ?? $stock->company_id,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'out',
                'movement_type' => $type,
                'quantity' => $quantity,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'balance_quantity' => $stock->available_quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            return $stock;
        });
    }

    /**
     * Apply stock adjustment.
     */
    public function applyAdjustment(int $adjustmentId, ?int $userId = null): StockAdjustment
    {
        return DB::transaction(function () use ($adjustmentId, $userId) {
            $adjustment = StockAdjustment::with(['items.product', 'warehouse'])->findOrFail($adjustmentId);

            if ($adjustment->status === 'applied') {
                throw new Exception("Adjustment has already been applied.");
            }

            foreach ($adjustment->items as $item) {
                $stock = $this->getOrCreateStock($item->product_id, $adjustment->warehouse_id, $adjustment->company_id);
                $diff = $item->difference; // actual - system

                if ($diff > 0) {
                    // Increase
                    $this->increaseStock(
                        $item->product_id,
                        $adjustment->warehouse_id,
                        $diff,
                        (float) $item->unit_cost,
                        $adjustment->reference_number,
                        'adjustment',
                        $item->notes ?? $adjustment->reason ?? 'Stock adjustment increase',
                        $userId,
                        $adjustment->company_id
                    );
                } elseif ($diff < 0) {
                    // Decrease
                    $this->decreaseStock(
                        $item->product_id,
                        $adjustment->warehouse_id,
                        abs($diff),
                        $adjustment->reference_number,
                        'adjustment',
                        $item->notes ?? $adjustment->reason ?? 'Stock adjustment decrease',
                        $userId,
                        $adjustment->company_id,
                        true // allow if physical count was less
                    );
                }
            }

            $adjustment->status = 'approved';
            $adjustment->approved_by = $userId ?? $adjustment->approved_by;
            $adjustment->approved_at = Carbon::now();
            $adjustment->save();

            // Record audit log
            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $adjustment->company_id,
                'action' => 'stock.adjusted',
                'module' => 'inventory',
                'record_id' => $adjustment->id,
                'old_values' => ['status' => 'pending'],
                'new_values' => [
                    'status' => 'applied',
                    'reference' => $adjustment->reference_number,
                    'items_count' => $adjustment->items->count(),
                ],
                'created_at' => Carbon::now(),
            ]);

            return $adjustment;
        });
    }

    /**
     * Process stock transfer workflow.
     */
    public function transferStock(int $transferId, string $action, ?int $userId = null): StockTransfer
    {
        return DB::transaction(function () use ($transferId, $action, $userId) {
            $transfer = StockTransfer::with(['items.product', 'fromWarehouse', 'toWarehouse'])->findOrFail($transferId);

            switch ($action) {
                case 'approve':
                case 'dispatch':
                    if (!in_array($transfer->status, ['draft', 'pending', 'approved'])) {
                        throw new Exception("Transfer cannot be dispatched from current status: {$transfer->status}");
                    }

                    // Deduct from source warehouse
                    foreach ($transfer->items as $item) {
                        $this->decreaseStock(
                            $item->product_id,
                            $transfer->from_warehouse_id,
                            $item->quantity,
                            $transfer->transfer_number,
                            'transfer_out',
                            "Stock Transfer to {$transfer->toWarehouse->name}: {$transfer->reason}",
                            $userId,
                            $transfer->company_id
                        );
                    }

                    $transfer->status = 'in_transit';
                    $transfer->approved_by = $userId;
                    $transfer->approved_at = Carbon::now();
                    $transfer->save();
                    break;

                case 'receive':
                    if ($transfer->status !== 'in_transit') {
                        throw new Exception("Only transfers 'in_transit' can be received.");
                    }

                    // Add to destination warehouse
                    foreach ($transfer->items as $item) {
                        $this->increaseStock(
                            $item->product_id,
                            $transfer->to_warehouse_id,
                            $item->quantity,
                            (float) $item->unit_cost,
                            $transfer->transfer_number,
                            'transfer_in',
                            "Stock Transfer received from {$transfer->fromWarehouse->name}",
                            $userId,
                            $transfer->company_id
                        );
                    }

                    $transfer->status = 'received';
                    $transfer->received_by = $userId;
                    $transfer->received_at = Carbon::now();
                    $transfer->save();
                    break;

                case 'cancel':
                    if ($transfer->status === 'received') {
                        throw new Exception("Received transfers cannot be cancelled.");
                    }

                    // If already dispatched, refund back to source warehouse
                    if ($transfer->status === 'in_transit') {
                        foreach ($transfer->items as $item) {
                            $this->increaseStock(
                                $item->product_id,
                                $transfer->from_warehouse_id,
                                $item->quantity,
                                (float) $item->unit_cost,
                                $transfer->transfer_number,
                                'transfer_in',
                                "Restored cancelled stock transfer",
                                $userId,
                                $transfer->company_id
                            );
                        }
                    }

                    $transfer->status = 'cancelled';
                    $transfer->save();
                    break;

                default:
                    throw new Exception("Invalid transfer action.");
            }

            // Record audit log
            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $transfer->company_id,
                'action' => 'stock.transferred',
                'module' => 'inventory',
                'record_id' => $transfer->id,
                'old_values' => ['action' => $action],
                'new_values' => [
                    'status' => $transfer->status,
                    'transfer_number' => $transfer->transfer_number,
                ],
                'created_at' => Carbon::now(),
            ]);

            return $transfer;
        });
    }

    /**
     * Create an immutable stock movement ledger entry.
     */
    public function createMovement(array $data): StockMovement
    {
        return StockMovement::create($data);
    }
}
