<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::withCount('purchaseOrders');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('supplier_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $suppliers = $query->orderBy('name')->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json([
            'status' => 'success',
            'data' => $suppliers->items(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier_code' => 'nullable|string|max:100|unique:suppliers,supplier_code',
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'alternate_phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:100',
            'gst_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        if (empty($validated['supplier_code'])) {
            $validated['supplier_code'] = 'SUP-' . strtoupper(Str::random(6));
        }

        $supplier = Supplier::create($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'company_id' => $supplier->company_id,
            'action' => 'supplier.created',
            'module' => 'inventory',
            'record_id' => $supplier->id,
            'old_values' => null,
            'new_values' => ['name' => $supplier->name, 'code' => $supplier->supplier_code],
            'created_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::with(['purchaseOrders.items'])->withCount('purchaseOrders')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $supplier,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'supplier_code' => 'nullable|string|max:100|unique:suppliers,supplier_code,' . $supplier->id,
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'alternate_phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:100',
            'gst_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $supplier->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier updated successfully',
            'data' => $supplier,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'company_id' => $supplier->company_id,
            'action' => 'supplier.deleted',
            'module' => 'inventory',
            'record_id' => $supplier->id,
            'old_values' => ['name' => $supplier->name],
            'new_values' => null,
            'created_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier deleted successfully',
        ]);
    }
}
