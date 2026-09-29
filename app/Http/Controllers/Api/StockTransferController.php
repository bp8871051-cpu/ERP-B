<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransferController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.product']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($fromId = $request->query('from_warehouse_id')) {
            $query->where('from_warehouse_id', $fromId);
        }

        if ($toId = $request->query('to_warehouse_id')) {
            $query->where('to_warehouse_id', $toId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $transfers = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'transfer_number' => 'nullable|string|max:100|unique:stock_transfers,transfer_number',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending,approved,in_transit',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'auto_dispatch' => 'nullable|boolean',
        ]);

        try {
            $transfer = DB::transaction(function () use ($validated, $request) {
                $transferNum = $validated['transfer_number'] ?? ('TRF-' . strtoupper(Str::random(6)) . '-' . date('ymd'));
                $initialStatus = $validated['status'] ?? 'pending';

                // Check source stock for each product
                foreach ($validated['items'] as $itemData) {
                    $stock = Stock::where('product_id', $itemData['product_id'])
                        ->where('warehouse_id', $validated['from_warehouse_id'])
                        ->first();

                    $available = $stock ? $stock->available_quantity : 0;
                    if ($available < $itemData['quantity']) {
                        $product = Product::find($itemData['product_id']);
                        throw new Exception("Insufficient stock for '{$product->name}' at source warehouse. Available: {$available}, Requested: {$itemData['quantity']}");
                    }
                }

                $transfer = StockTransfer::create([
                    'company_id' => $request->user()?->company_id ?? 1,
                    'transfer_number' => $transferNum,
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id' => $validated['to_warehouse_id'],
                    'status' => $initialStatus,
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()?->id,
                ]);

                foreach ($validated['items'] as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    $cost = $product ? ($product->purchase_price ?? $product->cost_price) : 0;

                    StockTransferItem::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $cost,
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                // If marked for auto-dispatch or in_transit immediately
                if (!empty($validated['auto_dispatch']) || $initialStatus === 'in_transit') {
                    $transfer = $this->stockService->transferStock($transfer->id, 'dispatch', $request->user()?->id);
                }

                return $transfer;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Stock transfer created successfully',
                'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']),
            ], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $transfer = StockTransfer::with([
            'fromWarehouse',
            'toWarehouse',
            'creator',
            'approver',
            'receiver',
            'items.product',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $transfer,
        ]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);
        if ($transfer->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Only draft transfers can be submitted.'], 422);
        }
        $transfer->update(['status' => 'pending']);
        return response()->json([
            'status' => 'success',
            'message' => 'Transfer submitted for approval',
            'data' => $transfer,
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);
        if (!in_array($transfer->status, ['draft', 'pending'])) {
            return response()->json(['status' => 'error', 'message' => 'Transfer is not awaiting approval.'], 422);
        }
        $transfer->update([
            'status' => 'approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Transfer approved successfully',
            'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'approver']),
        ]);
    }

    public function dispatch(Request $request, int $id): JsonResponse
    {
        try {
            $transfer = $this->stockService->transferStock($id, 'dispatch', $request->user()?->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Stock transfer dispatched and source inventory deducted',
                'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        try {
            $transfer = $this->stockService->transferStock($id, 'receive', $request->user()?->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Stock transfer received and destination inventory added',
                'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $transfer = $this->stockService->transferStock($id, 'cancel', $request->user()?->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Stock transfer cancelled and inventory restored',
                'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product']),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}
