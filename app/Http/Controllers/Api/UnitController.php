<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $units = Unit::withCount('products')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $units,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'short_name' => 'nullable|string|max:20',
            'short_code' => 'nullable|string|max:20',
            'unit_type' => 'nullable|string|max:50',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'status' => 'nullable|in:active,inactive',
        ]);

        $short = $validated['short_code'] ?? $validated['short_name'] ?? strtolower(substr($validated['name'], 0, 3));
        $validated['short_code'] = $short;
        $validated['short_name'] = $short;
        $validated['company_id'] = $request->user()?->company_id ?? 1;

        $unit = Unit::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit created successfully',
            'data' => $unit->loadCount('products'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $unit = Unit::withCount('products')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $unit,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'short_name' => 'sometimes|required|string|max:20',
            'short_code' => 'nullable|string|max:20',
            'unit_type' => 'nullable|string|max:50',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'status' => 'nullable|in:active,inactive',
        ]);

        $unit->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit updated successfully',
            'data' => $unit->loadCount('products'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $unit = Unit::withCount('products')->findOrFail($id);

        if ($unit->products_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete unit '{$unit->name}' because {$unit->products_count} products are using it.",
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Unit deleted successfully',
        ]);
    }
}
