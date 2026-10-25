<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\SystemAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreExecutiveController extends Controller
{
    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    /**
     * Get Store Executive Dashboard Metrics and Sections
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $today = Carbon::today()->format('Y-m-d');

        // Products and stock totals
        $products = Product::where('company_id', $companyId)->get();
        $totalStockItems = $products->count();
        $totalAvailableStock = (int) $products->sum('quantity');
        $lowStockItemsList = $products->filter(function ($p) {
            return $p->quantity <= ($p->alert_quantity ?: 10);
        })->values();
        $lowStockCount = $lowStockItemsList->count();

        // Stock movements today
        $todayMovements = StockMovement::where('company_id', $companyId)
            ->whereDate('created_at', $today)
            ->get();
        $todayInward = (int) $todayMovements->sum('quantity_in');
        $todayOutward = (int) $todayMovements->sum('quantity_out');

        // Pending Material Requests (linking Service Engineers and Project Engineers)
        $materialRequests = MaterialRequest::where('company_id', $companyId)
            ->with(['requester', 'items.product', 'warehouse', 'project', 'ticket'])
            ->latest()
            ->get();
        $pendingMaterialRequests = $materialRequests->where('status', 'pending')->values();

        // Pending Stock Transfers
        $pendingTransfers = StockTransfer::where('company_id', $companyId)
            ->whereIn('status', ['pending', 'in_transit'])
            ->with(['fromWarehouse', 'toWarehouse'])
            ->latest()
            ->get();

        // Damaged / Held Stock
        $damagedStockCount = StockMovement::where('company_id', $companyId)
            ->where('movement_type', 'damage')
            ->count();

        // Recent Stock Movements (last 15)
        $recentMovements = StockMovement::where('company_id', $companyId)
            ->with(['product', 'warehouse', 'creator'])
            ->latest()
            ->limit(15)
            ->get();

        // Inward and Outward specific lists
        $recentInwards = StockMovement::where('company_id', $companyId)
            ->where('quantity_in', '>', 0)
            ->with(['product', 'warehouse'])
            ->latest()
            ->limit(10)
            ->get();

        $recentOutwards = StockMovement::where('company_id', $companyId)
            ->where('quantity_out', '>', 0)
            ->with(['product', 'warehouse'])
            ->latest()
            ->limit(10)
            ->get();

        // Warehouse-wise Stock Summary
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        $warehouseSummary = $warehouses->map(function ($w) use ($companyId) {
            $movementIn = (int) StockMovement::where('company_id', $companyId)->where('warehouse_id', $w->id)->sum('quantity_in');
            $movementOut = (int) StockMovement::where('company_id', $companyId)->where('warehouse_id', $w->id)->sum('quantity_out');
            $netUnits = max(0, $movementIn - $movementOut);

            return [
                'id' => $w->id,
                'name' => $w->name,
                'code' => $w->code,
                'location' => $w->address ?? 'Vadodara Central Bay',
                'total_units' => $netUnits,
                'status' => 'Active',
            ];
        });

        // Physical Stock Count Differences / Adjustments
        $stockAdjustments = StockAdjustment::where('company_id', $companyId)
            ->with(['warehouse', 'creator'])
            ->latest()
            ->limit(10)
            ->get();

        // Recent Activities
        $activities = [];
        foreach ($recentMovements->take(6) as $m) {
            $activities[] = [
                'id' => 'mov_' . $m->id,
                'title' => ($m->quantity_in > 0 ? 'Stock Inward (+ ' : 'Stock Outward (- ') . ($m->quantity_in ?: $m->quantity_out) . ' units)',
                'description' => ($m->product?->name ?? 'Item') . ' (' . ($m->reference ?? 'Manual Entry') . ')',
                'time' => $m->created_at?->diffForHumans() ?: 'Recently',
                'type' => $m->quantity_in > 0 ? 'inward' : 'outward',
            ];
        }

        // Available lookups for quick actions
        $availableProducts = Product::where('company_id', $companyId)->select('id', 'name', 'sku', 'cost_price', 'selling_price')->get();
        $availableWarehouses = Warehouse::where('company_id', $companyId)->select('id', 'name', 'code')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'cards' => [
                    'total_stock_items' => $totalStockItems,
                    'total_available_stock' => $totalAvailableStock,
                    'today_stock_inward' => $todayInward,
                    'today_stock_outward' => $todayOutward,
                    'pending_material_requests' => $pendingMaterialRequests->count(),
                    'low_stock_items' => $lowStockCount,
                    'pending_stock_transfers' => $pendingTransfers->count(),
                    'damaged_held_stock' => $damagedStockCount,
                ],
                'sections' => [
                    'recent_stock_movements' => $recentMovements,
                    'pending_material_issue_requests' => $pendingMaterialRequests,
                    'recent_inward_transactions' => $recentInwards,
                    'recent_outward_transactions' => $recentOutwards,
                    'low_stock_alerts' => $lowStockItemsList->take(10),
                    'warehouse_wise_stock_summary' => $warehouseSummary,
                    'physical_stock_count_differences' => $stockAdjustments,
                    'recent_activities' => $activities,
                ],
                'lookups' => [
                    'products' => $availableProducts,
                    'warehouses' => $availableWarehouses,
                ],
            ],
        ]);
    }

    /**
     * Quick Action: Create Stock Inward Entry
     */
    public function createInward(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $product = Product::findOrFail($validated['product_id']);
        $product->increment('quantity', $validated['quantity']);

        $reference = $validated['reference'] ?? ('INW-' . date('Ymd') . '-' . rand(1000, 9999));
        $unitCost = $validated['unit_cost'] ?? ($product->cost ?? 0);

        $movement = StockMovement::create([
            'company_id' => $companyId,
            'product_id' => $product->id,
            'warehouse_id' => $validated['warehouse_id'],
            'type' => 'in',
            'movement_type' => 'in',
            'quantity' => $validated['quantity'],
            'quantity_in' => $validated['quantity'],
            'quantity_out' => 0,
            'balance_quantity' => $product->quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $unitCost * $validated['quantity'],
            'reference' => $reference,
            'notes' => $validated['notes'] ?? 'Manual stock inward entry by Store Executive',
            'created_by' => $user->id,
        ]);

        SystemAuditLog::log('inventory', 'stock_inward', (string) $movement->id, null, $movement->toArray(), $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Successfully recorded inward of {$validated['quantity']} units for {$product->name}.",
            'data' => $movement,
        ]);
    }

    /**
     * Quick Action: Create Stock Outward Entry
     */
    public function createOutward(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $product = Product::findOrFail($validated['product_id']);
        if ($product->quantity < $validated['quantity']) {
            return response()->json([
                'status' => 'error',
                'message' => "Insufficient stock. Available: {$product->quantity}, Requested: {$validated['quantity']}",
            ], 422);
        }

        $product->decrement('quantity', $validated['quantity']);

        $reference = $validated['reference'] ?? ('OUT-' . date('Ymd') . '-' . rand(1000, 9999));
        $unitCost = $product->cost ?? 0;

        $movement = StockMovement::create([
            'company_id' => $companyId,
            'product_id' => $product->id,
            'warehouse_id' => $validated['warehouse_id'],
            'type' => 'out',
            'movement_type' => 'out',
            'quantity' => $validated['quantity'],
            'quantity_in' => 0,
            'quantity_out' => $validated['quantity'],
            'balance_quantity' => $product->quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $unitCost * $validated['quantity'],
            'reference' => $reference,
            'notes' => $validated['notes'] ?? 'Stock outward dispatch by Store Executive',
            'created_by' => $user->id,
        ]);

        SystemAuditLog::log('inventory', 'stock_outward', (string) $movement->id, null, $movement->toArray(), $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Successfully issued outward of {$validated['quantity']} units for {$product->name}.",
            'data' => $movement,
        ]);
    }

    /**
     * Quick Action: Issue Material (Approves & issues material request from Service/Project Engineer)
     */
    public function issueMaterial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_request_id' => 'required|integer|exists:material_requests,id',
            'warehouse_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $materialRequest = MaterialRequest::with('items.product')->findOrFail($validated['material_request_id']);

        if ($materialRequest->status === 'issued') {
            return response()->json(['status' => 'error', 'message' => 'Material request is already fully issued.'], 422);
        }

        $warehouseId = $validated['warehouse_id'] ?? $materialRequest->warehouse_id ?? Warehouse::first()?->id;

        DB::transaction(function () use ($materialRequest, $warehouseId, $companyId, $user, $validated) {
            foreach ($materialRequest->items as $item) {
                $qtyToIssue = $item->quantity_requested - $item->quantity_issued;
                if ($qtyToIssue > 0) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('quantity', min($product->quantity, $qtyToIssue));

                        StockMovement::create([
                            'company_id' => $companyId,
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'type' => 'out',
                            'movement_type' => 'out',
                            'quantity' => $qtyToIssue,
                            'quantity_in' => 0,
                            'quantity_out' => $qtyToIssue,
                            'balance_quantity' => $product->quantity,
                            'unit_cost' => $product->cost ?? 0,
                            'total_cost' => ($product->cost ?? 0) * $qtyToIssue,
                            'reference' => 'ISSUE-' . $materialRequest->request_number,
                            'notes' => 'Issued against ' . $materialRequest->request_number . ' for ' . ($materialRequest->request_type ?? 'Requisition'),
                            'created_by' => $user->id,
                        ]);
                    }
                    $item->quantity_issued = $item->quantity_requested;
                    $item->save();
                }
            }

            $materialRequest->status = 'issued';
            $materialRequest->issued_by = $user->id;
            $materialRequest->issued_at = Carbon::now();
            if (!empty($validated['notes'])) {
                $materialRequest->notes = ($materialRequest->notes ? $materialRequest->notes . ' | ' : '') . $validated['notes'];
            }
            $materialRequest->save();
        });

        SystemAuditLog::log('inventory', 'issue_material_request', (string) $materialRequest->id, null, [
            'request_number' => $materialRequest->request_number,
            'issued_by' => $user->id,
        ], $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Material requisition {$materialRequest->request_number} successfully issued and stock updated.",
            'data' => $materialRequest->fresh(['items.product']),
        ]);
    }

    /**
     * Quick Action: Record Material Return
     */
    public function recordMaterialReturn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'return_reason' => 'required|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $product = Product::findOrFail($validated['product_id']);
        $product->increment('quantity', $validated['quantity']);

        $movement = StockMovement::create([
            'company_id' => $companyId,
            'product_id' => $product->id,
            'warehouse_id' => $validated['warehouse_id'],
            'type' => 'in',
            'movement_type' => 'return',
            'quantity' => $validated['quantity'],
            'quantity_in' => $validated['quantity'],
            'quantity_out' => 0,
            'balance_quantity' => $product->quantity,
            'unit_cost' => $product->cost ?? 0,
            'total_cost' => ($product->cost ?? 0) * $validated['quantity'],
            'reference' => 'RET-' . date('Ymd') . '-' . rand(100, 999),
            'notes' => 'Returned material to store: ' . $validated['return_reason'],
            'created_by' => $user->id,
        ]);

        SystemAuditLog::log('inventory', 'material_return', (string) $movement->id, null, $movement->toArray(), $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Returned {$validated['quantity']} units of {$product->name} back to inventory.",
            'data' => $movement,
        ]);
    }

    /**
     * Quick Action: Request Stock Transfer
     */
    public function requestTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|integer|exists:warehouses,id',
            'to_warehouse_id' => 'required|integer|different:from_warehouse_id|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $transferNo = 'TRF-' . date('Y') . '-' . rand(1000, 9999);

        $transfer = StockTransfer::create([
            'company_id' => $companyId,
            'transfer_number' => $transferNo,
            'from_warehouse_id' => $validated['from_warehouse_id'],
            'to_warehouse_id' => $validated['to_warehouse_id'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? 'Inter-warehouse transfer requested by Store Executive',
            'created_by' => $user->id,
        ]);

        SystemAuditLog::log('inventory', 'request_stock_transfer', (string) $transfer->id, null, $transfer->toArray(), $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Stock transfer request {$transferNo} created and awaiting dispatch.",
            'data' => $transfer,
        ]);
    }

    /**
     * Quick Action: Submit Stock Adjustment Request (Requires Manager Approval)
     */
    public function submitAdjustmentRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'adjustment_type' => 'required|string|in:increase,decrease,damage',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $refNo = 'ADJ-' . date('Y') . '-' . rand(1000, 9999);

        // Store Executive cannot self-approve restricted adjustments; status must be pending
        $adjustment = StockAdjustment::create([
            'company_id' => $companyId,
            'warehouse_id' => $validated['warehouse_id'],
            'reference_number' => $refNo,
            'adjustment_type' => $validated['adjustment_type'],
            'reason' => $validated['reason'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? 'Submitted by Store Executive for Manager approval',
            'created_by' => $user->id,
        ]);

        SystemAuditLog::log('inventory', 'submit_stock_adjustment', (string) $adjustment->id, null, $adjustment->toArray(), $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Stock adjustment request {$refNo} submitted for Manager approval. Store Executives cannot self-approve.",
            'data' => $adjustment,
        ]);
    }

    /**
     * Quick Action: Record Damaged Stock
     */
    public function recordDamagedStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'damage_reason' => 'required|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $product = Product::findOrFail($validated['product_id']);
        if ($product->quantity < $validated['quantity']) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient stock to quarantine as damaged.'], 422);
        }

        $product->decrement('quantity', $validated['quantity']);

        $movement = StockMovement::create([
            'company_id' => $companyId,
            'product_id' => $product->id,
            'warehouse_id' => $validated['warehouse_id'],
            'type' => 'out',
            'movement_type' => 'damage',
            'quantity' => $validated['quantity'],
            'quantity_in' => 0,
            'quantity_out' => $validated['quantity'],
            'balance_quantity' => $product->quantity,
            'unit_cost' => $product->cost ?? 0,
            'total_cost' => ($product->cost ?? 0) * $validated['quantity'],
            'reference' => 'DMG-' . date('Ymd') . '-' . rand(100, 999),
            'notes' => 'Damaged/quarantined stock: ' . $validated['damage_reason'],
            'created_by' => $user->id,
        ]);

        SystemAuditLog::log('inventory', 'record_damaged_stock', (string) $movement->id, null, $movement->toArray(), $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "{$validated['quantity']} units of {$product->name} quarantined as damaged stock.",
            'data' => $movement,
        ]);
    }
}
