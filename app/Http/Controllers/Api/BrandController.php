<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Brand::withCount('products');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $brands = $query->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $brands,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $slug = Str::slug($validated['name']);
        if (Brand::where('slug', $slug)->exists()) {
            $slug .= '-' . strtolower(Str::random(4));
        }

        $brand = Brand::create(array_merge($validated, ['slug' => $slug]));

        return response()->json([
            'status' => 'success',
            'message' => 'Brand created successfully',
            'data' => $brand->loadCount('products'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $brand = Brand::withCount('products')->with('products')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $brand,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:brands,name,' . $brand->id,
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $brand->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Brand updated successfully',
            'data' => $brand->loadCount('products'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::withCount('products')->findOrFail($id);

        if ($brand->products_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete brand '{$brand->name}' because {$brand->products_count} products are associated with it.",
            ], 422);
        }

        $brand->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Brand deleted successfully',
        ]);
    }
}
