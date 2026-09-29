<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockAdjustmentController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = StockAdjustment::with(['warehouse', 'creator', 'approver', 'items.product']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $adjustments = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $adjustments->items(),
            'meta' => [
                'current_page' => $adjustments->currentPage(),
                'last_page' => $adjustments->lastPage(),
                'per_page' => $adjustments->perPage(),
                'total' => $adjustments->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'reference_number' => 'nullable|string|max:100|unique:stock_adjustments,reference_number',
            'adjustment_type' => 'nullable|in:increase,decrease,both',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending,approved,applied',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.system_quantity' => 'nullable|integer',
            'items.*.actual_quantity' => 'required|integer|min:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'auto_apply' => 'nullable|boolean',
        ]);

        try {
            $adjustment = DB::transaction(function () use ($validated, $request) {
                $ref = $validated['reference_number'] ?? ('ADJ-' . strtoupper(Str::random(6)) . '-' . date('ymd'));
                $initialStatus = $validated['status'] ?? 'draft';

                $adjustment = StockAdjustment::create([
                    'company_id' => $request->user()?->company_id ?? 1,
                    'warehouse_id' => $validated['warehouse_id'],
                    'reference_number' => $ref,
                    'adjustment_type' => $validated['adjustment_type'] ?? 'both',
                    'reason' => $validated['reason'],
                    'status' => $initialStatus,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()?->id,
                ]);

                foreach ($validated['items'] as $itemData) {
                    $stock = Stock::where('product_id', $itemData['product_id'])
                        ->where('warehouse_id', $validated['warehouse_id'])
                        ->first();

                    $sysQty = $itemData['system_quantity'] ?? ($stock ? $stock->available_quantity : 0);
                    $actualQty = (int) $itemData['actual_quantity'];
                    $diff = $actualQty - $sysQty;

                    $product = Product::find($itemData['product_id']);
                    $cost = $itemData['unit_cost'] ?? ($product ? ($product->purchase_price ?? $product->cost_price) : 0);

                    StockAdjustmentItem::create([
                        'adjustment_id' => $adjustment->id,
                        'product_id' => $itemData['product_id'],
                        'system_quantity' => $sysQty,
                        'actual_quantity' => $actualQty,
                        'difference' => $diff,
                        'unit_cost' => $cost,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                // If marked for auto-apply or approved immediately
                if (!empty($validated['auto_apply']) || $initialStatus === 'applied' || $initialStatus === 'approved') {
                    $adjustment = $this->stockService->applyAdjustment($adjustment->id, $request->user()?->id);
                }

                return $adjustment;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment created successfully',
                'data' => $adjustment->load(['warehouse', 'items.product', 'creator']),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $adjustment = StockAdjustment::with(['warehouse', 'creator', 'approver', 'items.product'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $adjustment,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $adjustment = StockAdjustment::findOrFail($id);

        if (in_array($adjustment->status, ['applied', 'approved'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Applied or approved adjustments cannot be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'sometimes|required|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending',
        ]);

        $adjustment->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Adjustment updated successfully',
            'data' => $adjustment->load(['warehouse', 'items.product']),
        ]);
    }

    public function submit(int $id): JsonResponse
    {
        $adjustment = StockAdjustment::findOrFail($id);

        if ($adjustment->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Only draft adjustments can be submitted.'], 422);
        }

        $adjustment->update(['status' => 'pending']);

        return response()->json([
            'status' => 'success',
            'message' => 'Adjustment submitted for approval',
            'data' => $adjustment,
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $adjustment = $this->stockService->applyAdjustment($id, $request->user()?->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment approved and stock updated successfully',
                'data' => $adjustment->load(['warehouse', 'items.product', 'approver']),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $adjustment = StockAdjustment::findOrFail($id);

        if ($adjustment->status === 'applied') {
            return response()->json(['status' => 'error', 'message' => 'Applied adjustments cannot be rejected.'], 422);
        }

        $adjustment->update([
            'status' => 'rejected',
            'approved_by' => $request->user()?->id,
            'approved_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock adjustment rejected',
            'data' => $adjustment,
        ]);
    }
}
