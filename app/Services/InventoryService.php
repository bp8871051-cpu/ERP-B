<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\InventoryAlert;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    protected InventoryDashboardService $dashboardService;

    public function __construct(InventoryDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getDashboardData(?int $companyId = null, array $filters = []): array
    {
        return $this->dashboardService->getDashboardData($companyId ?? 1, $filters);
    }

    /**
     * Add Stock to a specific warehouse
     */
    public function addStock(
        int $companyId,
        int $productId,
        int $warehouseId,
        int $quantity,
        float $unitCost,
        ?string $notes = null,
        ?int $userId = null,
        ?string $referenceType = 'manual_entry',
        ?string $reference = null
    ): Stock {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Quantity to add must be greater than zero.");
        }

        return DB::transaction(function () use ($companyId, $productId, $warehouseId, $quantity, $unitCost, $notes, $userId, $referenceType, $reference) {
            $stock = Stock::where('product_id', $productId)->where('warehouse_id', $warehouseId)->first();
            if (!$stock) {
                $stock = Stock::create([
                    'company_id' => $companyId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'available_quantity' => 0,
                    'reserved_quantity' => 0,
                    'average_cost' => $unitCost,
                ]);
            }

            $oldQty = $stock->quantity;
            $newQty = $oldQty + $quantity;

            // Weighted average cost calculation
            $oldTotalVal = $oldQty * (float) $stock->average_cost;
            $addedVal = $quantity * $unitCost;
            $newAvgCost = $newQty > 0 ? round(($oldTotalVal + $addedVal) / $newQty, 2) : $unitCost;

            $stock->quantity = $newQty;
            $stock->available_quantity = $stock->available_quantity + $quantity;
            $stock->average_cost = $newAvgCost;
            $stock->last_movement_at = Carbon::now();
            $stock->save();

            $refStr = $reference ?: ('STK-ADD-' . strtoupper(substr(uniqid(), -6)));

            // 1. Log to inventory_transactions
            InventoryTransaction::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'purchase_in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference_type' => $referenceType,
                'reference' => $refStr,
                'performed_by' => $userId,
                'notes' => $notes,
            ]);

            // 2. Log to stock_movements
            StockMovement::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'in',
                'movement_type' => 'in',
                'quantity' => $quantity,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'balance_quantity' => $newQty,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference' => $refStr,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            return $stock;
        });
    }

    /**
     * Remove Stock from a warehouse
     */
    public function removeStock(
        int $companyId,
        int $productId,
        int $warehouseId,
        int $quantity,
        ?string $notes = null,
        ?int $userId = null,
        ?string $referenceType = 'sales_out',
        ?string $reference = null
    ): Stock {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("Quantity to remove must be greater than zero.");
        }

        return DB::transaction(function () use ($companyId, $productId, $warehouseId, $quantity, $notes, $userId, $referenceType, $reference) {
            $stock = Stock::where('product_id', $productId)->where('warehouse_id', $warehouseId)->first();
            if (!$stock || $stock->available_quantity < $quantity) {
                throw new InvalidArgumentException("Insufficient available stock in warehouse. Available: " . ($stock ? $stock->available_quantity : 0));
            }

            $stock->quantity -= $quantity;
            $stock->available_quantity -= $quantity;
            $stock->last_movement_at = Carbon::now();
            $stock->save();

            $unitCost = (float) $stock->average_cost;
            $refStr = $reference ?: ('STK-REM-' . strtoupper(substr(uniqid(), -6)));

            InventoryTransaction::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'sales_out',
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference_type' => $referenceType,
                'reference' => $refStr,
                'performed_by' => $userId,
                'notes' => $notes,
            ]);

            StockMovement::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'out',
                'movement_type' => 'out',
                'quantity' => $quantity,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'balance_quantity' => $stock->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => round($quantity * $unitCost, 2),
                'reference' => $refStr,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            return $stock;
        });
    }

    /**
     * Centralized Stock Adjustment
     */
    public function adjustStock(
        int $companyId,
        int $warehouseId,
        array $items, // array of ['product_id' => ..., 'actual_quantity' => ..., 'reason' => ...]
        ?string $reason = null,
        ?int $userId = null
    ): StockAdjustment {
        return DB::transaction(function () use ($companyId, $warehouseId, $items, $reason, $userId) {
            $ref = 'ADJ-' . strtoupper(substr(uniqid(), -6));
            $adjustment = StockAdjustment::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'reference_number' => $ref,
                'adjustment_type' => 'both',
                'reason' => $reason ?? 'Physical audit discrepancy adjustment',
                'status' => 'approved',
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => Carbon::now(),
            ]);

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $actualQty = (int) $item['actual_quantity'];

                $stock = Stock::where('product_id', $productId)->where('warehouse_id', $warehouseId)->first();
                $systemQty = $stock ? $stock->quantity : 0;
                $diff = $actualQty - $systemQty;

                StockAdjustmentItem::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id' => $productId,
                    'system_quantity' => $systemQty,
                    'actual_quantity' => $actualQty,
                    'difference' => $diff,
                    'unit_cost' => $stock ? $stock->average_cost : 1000,
                    'notes' => $item['notes'] ?? null,
                ]);

                if ($stock) {
                    $stock->quantity = $actualQty;
                    $stock->available_quantity = max(0, $actualQty - $stock->reserved_quantity);
                    $stock->last_movement_at = Carbon::now();
                    $stock->save();
                }

                InventoryTransaction::create([
                    'company_id' => $companyId,
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'type' => 'stock_adjustment',
                    'quantity' => $diff,
                    'unit_cost' => $stock ? $stock->average_cost : 1000,
                    'total_cost' => abs($diff) * ($stock ? $stock->average_cost : 1000),
                    'reference_type' => 'StockAdjustment',
                    'reference' => $ref,
                    'performed_by' => $userId,
                    'notes' => "Adjusted from $systemQty to $actualQty ($diff)",
                ]);
            }

            return $adjustment;
        });
    }

    /**
     * Inter-warehouse Stock Transfer
     */
    public function transferStock(
        int $companyId,
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items, // array of ['product_id' => ..., 'quantity' => ...]
        ?string $reason = null,
        ?int $userId = null
    ): StockTransfer {
        return DB::transaction(function () use ($companyId, $fromWarehouseId, $toWarehouseId, $items, $reason, $userId) {
            $ref = 'TRF-' . strtoupper(substr(uniqid(), -6));
            $transfer = StockTransfer::create([
                'company_id' => $companyId,
                'transfer_number' => $ref,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'status' => 'completed',
                'reason' => $reason ?? 'Inter-hub rebalancing',
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => Carbon::now(),
                'received_by' => $userId,
                'received_at' => Carbon::now(),
            ]);

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $qty = (int) $item['quantity'];

                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_cost' => 1200,
                ]);

                // Deduct from source warehouse
                $sourceStock = Stock::where('product_id', $productId)->where('warehouse_id', $fromWarehouseId)->first();
                if ($sourceStock) {
                    $sourceStock->quantity = max(0, $sourceStock->quantity - $qty);
                    $sourceStock->available_quantity = max(0, $sourceStock->available_quantity - $qty);
                    $sourceStock->last_movement_at = Carbon::now();
                    $sourceStock->save();
                }

                // Add to destination warehouse
                $destStock = Stock::where('product_id', $productId)->where('warehouse_id', $toWarehouseId)->first();
                if (!$destStock) {
                    $destStock = Stock::create([
                        'company_id' => $companyId,
                        'product_id' => $productId,
                        'warehouse_id' => $toWarehouseId,
                        'quantity' => 0,
                        'available_quantity' => 0,
                        'reserved_quantity' => 0,
                        'average_cost' => $sourceStock ? $sourceStock->average_cost : 1200,
                    ]);
                }
                $destStock->quantity += $qty;
                $destStock->available_quantity += $qty;
                $destStock->last_movement_at = Carbon::now();
                $destStock->save();

                // Transactions
                InventoryTransaction::create([
                    'company_id' => $companyId,
                    'product_id' => $productId,
                    'warehouse_id' => $fromWarehouseId,
                    'type' => 'stock_transfer_out',
                    'quantity' => -$qty,
                    'unit_cost' => $sourceStock ? $sourceStock->average_cost : 1200,
                    'total_cost' => $qty * ($sourceStock ? $sourceStock->average_cost : 1200),
                    'reference_type' => 'StockTransfer',
                    'reference' => $ref,
                    'performed_by' => $userId,
                    'notes' => "Transferred out to Warehouse #$toWarehouseId",
                ]);

                InventoryTransaction::create([
                    'company_id' => $companyId,
                    'product_id' => $productId,
                    'warehouse_id' => $toWarehouseId,
                    'type' => 'stock_transfer_in',
                    'quantity' => $qty,
                    'unit_cost' => $destStock->average_cost,
                    'total_cost' => $qty * $destStock->average_cost,
                    'reference_type' => 'StockTransfer',
                    'reference' => $ref,
                    'performed_by' => $userId,
                    'notes' => "Transferred in from Warehouse #$fromWarehouseId",
                ]);
            }

            return $transfer;
        });
    }

    public function calculateAvailableStock(int $productId, ?int $warehouseId = null): int
    {
        $q = Stock::where('product_id', $productId);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        return (int) $q->sum('available_quantity');
    }

    public function calculateStockValue(int $productId, ?int $warehouseId = null): float
    {
        $q = Stock::where('product_id', $productId);
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        $stock = $q->first();
        if (!$stock) return 0.0;
        return (float) ($stock->quantity * $stock->average_cost);
    }
}
