<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::with(['manager', 'stocks.product']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('manager_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $warehouses = $query->orderBy('name')->get()->map(function ($wh) {
            return [
                'id' => $wh->id,
                'name' => $wh->name,
                'code' => $wh->code,
                'type' => $wh->type ?? 'main',
                'manager_name' => $wh->manager_name ?? $wh->manager?->name ?? 'Unassigned',
                'manager_id' => $wh->manager_id,
                'email' => $wh->email,
                'phone' => $wh->phone,
                'address' => $wh->address,
                'city' => $wh->city,
                'state' => $wh->state,
                'country' => $wh->country,
                'status' => $wh->status,
                'total_products' => $wh->stocks->where('quantity', '>', 0)->count(),
                'total_quantity' => (int) $wh->stocks->sum('quantity'),
                'total_stock_value' => $wh->total_stock_value,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $warehouses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'type' => 'nullable|in:main,branch,distribution,storage,virtual',
            'manager_name' => 'nullable|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        $warehouse = Warehouse::create($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'company_id' => $warehouse->company_id,
            'action' => 'warehouse.created',
            'module' => 'inventory',
            'record_id' => $warehouse->id,
            'old_values' => null,
            'new_values' => ['name' => $warehouse->name, 'code' => $warehouse->code],
            'created_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse created successfully',
            'data' => $warehouse,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = Warehouse::with([
            'manager',
            'stocks.product.category',
            'stocks.product.unit',
            'stockMovements' => fn ($q) => $q->with('product', 'creator')->latest()->take(15),
            'stockAdjustments' => fn ($q) => $q->latest()->take(10),
            'transfersFrom.toWarehouse',
            'transfersTo.fromWarehouse',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'type' => $warehouse->type,
                'manager_name' => $warehouse->manager_name ?? $warehouse->manager?->name,
                'manager' => $warehouse->manager,
                'email' => $warehouse->email,
                'phone' => $warehouse->phone,
                'address' => $warehouse->address,
                'city' => $warehouse->city,
                'state' => $warehouse->state,
                'country' => $warehouse->country,
                'status' => $warehouse->status,
                'total_products' => $warehouse->stocks->where('quantity', '>', 0)->count(),
                'total_quantity' => (int) $warehouse->stocks->sum('quantity'),
                'total_stock_value' => $warehouse->total_stock_value,
                'stocks' => $warehouse->stocks,
                'recent_movements' => $warehouse->stockMovements,
                'recent_adjustments' => $warehouse->stockAdjustments,
                'transfers_from' => $warehouse->transfersFrom,
                'transfers_to' => $warehouse->transfersTo,
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'type' => 'nullable|in:main,branch,distribution,storage,virtual',
            'manager_name' => 'nullable|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        $warehouse->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse updated successfully',
            'data' => $warehouse,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $warehouse = Warehouse::withCount('stocks')->findOrFail($id);

        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete warehouse '{$warehouse->name}' because positive stock quantities are currently located here. Transfer or adjust stock first.",
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Warehouse deleted successfully',
        ]);
    }
}
