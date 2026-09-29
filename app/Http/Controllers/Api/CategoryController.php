<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::with(['parent', 'children'])->withCount('products');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $slug = Str::slug($validated['name']);
        if (Category::where('slug', $slug)->exists()) {
            $slug .= '-' . strtolower(Str::random(4));
        }

        $category = Category::create(array_merge($validated, ['slug' => $slug]));

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category->load(['parent', 'children'])->loadCount('products'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::with(['parent', 'children', 'products'])->withCount('products')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $category,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category->load(['parent', 'children'])->loadCount('products'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::withCount(['products', 'children'])->findOrFail($id);

        if ($category->products_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete category '{$category->name}' because {$category->products_count} product(s) are assigned to it.",
            ], 422);
        }

        if ($category->children_count > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot delete category '{$category->name}' because it contains sub-categories.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully',
        ]);
    }
}
