<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Stock::with(['product.category', 'product.brand', 'product.unit', 'warehouse']);

        if ($search = $request->query('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($status = $request->query('status')) {
            if ($status === 'out_of_stock') {
                $query->where('available_quantity', '<=', 0);
            } elseif ($status === 'low_stock') {
                $query->where('available_quantity', '>', 0)->where('available_quantity', '<=', 20);
            } elseif ($status === 'healthy') {
                $query->where('available_quantity', '>', 20);
            }
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $stocks = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $stocks->items(),
            'meta' => [
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $stock = Stock::with(['product.category', 'product.unit', 'warehouse'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $stock,
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['product.unit', 'warehouse', 'creator']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($type = $request->query('movement_type')) {
            $query->where(function ($q) use ($type) {
                $q->where('movement_type', $type)->orWhere('type', $type);
            });
        }

        if ($fromDate = $request->query('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate = $request->query('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $perPage = min((int) $request->query('per_page', 25), 100);
        $movements = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    public function opening(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $stock = $this->stockService->increaseStock(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity'],
                (float) ($validated['unit_cost'] ?? 0),
                $validated['reference'] ?? ('OPEN-' . date('YmdHis')),
                'opening',
                $validated['notes'] ?? 'Opening Stock Entry',
                $request->user()?->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Opening stock added successfully',
                'data' => $stock->load(['product', 'warehouse']),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $stock = $this->stockService->increaseStock(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity'],
                (float) ($validated['unit_cost'] ?? 0),
                $validated['reference'] ?? ('RCV-' . date('YmdHis')),
                'purchase',
                $validated['notes'] ?? 'Stock Received',
                $request->user()?->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Stock received and inventory increased successfully',
                'data' => $stock->load(['product', 'warehouse']),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function issue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $stock = $this->stockService->decreaseStock(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity'],
                $validated['reference'] ?? ('ISS-' . date('YmdHis')),
                'sale',
                $validated['notes'] ?? 'Stock Issued',
                $request->user()?->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Stock issued and inventory deducted successfully',
                'data' => $stock->load(['product', 'warehouse']),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}
