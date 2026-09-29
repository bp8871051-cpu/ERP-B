<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'brand', 'unit', 'defaultWarehouse', 'stocks.warehouse']);

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($catId = $request->query('category_id')) {
            $query->where('category_id', $catId);
        }
        if ($brandId = $request->query('brand_id')) {
            $query->where('brand_id', $brandId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($warehouseId = $request->query('warehouse_id')) {
            $query->whereHas('stocks', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $allowedSorts = ['name', 'sku', 'purchase_price', 'selling_price', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'barcode' => 'nullable|string|max:100',
            'product_code' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|in:inclusive,exclusive',
            'alert_quantity' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'default_warehouse_id' => 'nullable|exists:warehouses,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'track_inventory' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
            'opening_stock' => 'nullable|integer|min:0',
            'opening_warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $product = DB::transaction(function () use ($validated, $request) {
            $costPrice = $validated['purchase_price'] ?? $validated['cost_price'] ?? 0;
            $productData = array_merge($validated, [
                'company_id' => $request->user()?->company_id ?? 1,
                'cost_price' => $costPrice,
                'purchase_price' => $costPrice,
                'created_by' => $request->user()?->id,
            ]);
            unset($productData['opening_stock'], $productData['opening_warehouse_id']);

            $product = Product::create($productData);

            // Handle initial opening stock if provided
            $openingStock = (int) ($validated['opening_stock'] ?? 0);
            $warehouseId = $validated['opening_warehouse_id'] ?? $validated['default_warehouse_id'] ?? null;
            if ($openingStock > 0 && $warehouseId) {
                $this->stockService->increaseStock(
                    $product->id,
                    $warehouseId,
                    $openingStock,
                    (float) $costPrice,
                    'OPENING-' . $product->sku,
                    'opening',
                    'Initial opening stock',
                    $request->user()?->id,
                    $product->company_id
                );
            }

            // Audit Log
            AuditLog::create([
                'user_id' => $request->user()?->id,
                'company_id' => $product->company_id,
                'action' => 'product.created',
                'module' => 'inventory',
                'record_id' => $product->id,
                'old_values' => null,
                'new_values' => ['name' => $product->name, 'sku' => $product->sku],
                'created_at' => Carbon::now(),
            ]);

            return $product;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => $product->load(['category', 'brand', 'unit', 'stocks.warehouse']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with([
            'category',
            'brand',
            'unit',
            'defaultWarehouse',
            'stocks.warehouse',
            'movements' => fn ($q) => $q->with('warehouse', 'creator')->latest()->take(20),
            'adjustmentItems.adjustment.warehouse',
            'transferItems.transfer.fromWarehouse',
            'transferItems.transfer.toWarehouse',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:100|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100',
            'product_code' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'nullable|exists:units,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|in:inclusive,exclusive',
            'alert_quantity' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'maximum_stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'default_warehouse_id' => 'nullable|exists:warehouses,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'track_inventory' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        $oldValues = $product->only(['name', 'sku', 'selling_price', 'status']);
        $product->update(array_merge($validated, [
            'updated_by' => $request->user()?->id,
        ]));

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'company_id' => $product->company_id,
            'action' => 'product.updated',
            'module' => 'inventory',
            'record_id' => $product->id,
            'old_values' => $oldValues,
            'new_values' => $product->only(['name', 'sku', 'selling_price', 'status']),
            'created_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product->load(['category', 'brand', 'unit', 'stocks.warehouse']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $hasMovements = StockMovement::where('product_id', $product->id)->exists();
        if ($hasMovements) {
            // Soft delete
            $product->delete();
            $message = 'Product archived (soft deleted) as historical stock movements exist.';
        } else {
            $product->stocks()->delete();
            $product->delete();
            $message = 'Product deleted successfully.';
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'company_id' => $product->company_id,
            'action' => 'product.deleted',
            'module' => 'inventory',
            'record_id' => $product->id,
            'old_values' => ['name' => $product->name, 'sku' => $product->sku],
            'new_values' => null,
            'created_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    public function duplicate(Request $request, int $id): JsonResponse
    {
        $original = Product::findOrFail($id);
        $newSku = $original->sku . '-COPY-' . strtoupper(Str::random(4));

        $copy = $original->replicate([
            'sku', 'barcode', 'created_at', 'updated_at', 'deleted_at'
        ]);
        $copy->name = $original->name . ' (Copy)';
        $copy->sku = $newSku;
        $copy->created_by = $request->user()?->id;
        $copy->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product duplicated successfully',
            'data' => $copy->load(['category', 'brand', 'unit']),
        ], 201);
    }

    public function stockHistory(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $query = StockMovement::with(['warehouse', 'creator'])
            ->where('product_id', $product->id);

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($type = $request->query('movement_type')) {
            $query->where(function ($q) use ($type) {
                $q->where('movement_type', $type)->orWhere('type', $type);
            });
        }
        if ($search = $request->query('search')) {
            $query->where('reference', 'like', "%{$search}%");
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $movements = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'total_stock' => $product->total_stock,
            ],
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'products_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'SKU', 'Name', 'Category', 'Brand', 'Unit', 'Cost Price', 'Selling Price', 'Total Stock', 'Status']);

            Product::with(['category', 'brand', 'unit', 'stocks'])->chunk(200, function ($products) use ($handle) {
                foreach ($products as $p) {
                    fputcsv($handle, [
                        $p->id,
                        $p->sku,
                        $p->name,
                        $p->category?->name ?? 'N/A',
                        $p->brand?->name ?? 'N/A',
                        $p->unit?->short_name ?? $p->unit?->name ?? 'N/A',
                        $p->purchase_price ?? $p->cost_price,
                        $p->selling_price,
                        $p->total_stock,
                        $p->status,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
