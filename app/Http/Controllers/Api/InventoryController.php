<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryDashboardService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;
    protected InventoryDashboardService $dashboardService;

    public function __construct(InventoryService $inventoryService, InventoryDashboardService $dashboardService)
    {
        $this->inventoryService = $inventoryService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Complete Dashboard Metrics Payload
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $filters = $request->all();
        $data = $this->inventoryService->getDashboardData($companyId, $filters);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['kpi']]);
    }

    public function movement(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['stock_movement']]);
    }

    public function valueTrend(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['inventory_value_trend']]);
    }

    public function categoryDistribution(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['category_distribution']]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['top_products']]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['low_stock_alerts']]);
    }

    public function outOfStock(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['inventory_health'][3] ?? []]);
    }

    public function warehouses(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['warehouse_overview']]);
    }

    public function suppliers(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['top_suppliers']]);
    }

    public function forecast(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['forecast']]);
    }

    public function reorderRecommendations(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['reorder_recommendations']]);
    }

    public function activities(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['recent_activity']]);
    }

    public function purchaseImpact(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['recent_purchase_orders']]);
    }

    public function salesImpact(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['sales_impact']]);
    }

    public function reports(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $this->inventoryService->getDashboardData($companyId);
        return response()->json(['success' => true, 'data' => $data['reports']]);
    }

    /**
     * Add Stock Mutation
     */
    public function addStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $userId = $request->user()?->id ?? 1;

        $stock = $this->inventoryService->addStock(
            $companyId,
            $validated['product_id'],
            $validated['warehouse_id'],
            $validated['quantity'],
            (float) $validated['unit_cost'],
            $validated['notes'] ?? 'Manual stock addition via dashboard',
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully.',
            'data' => $stock,
        ]);
    }

    /**
     * Stock Adjustment Mutation
     */
    public function adjustStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.actual_quantity' => 'required|integer|min:0',
            'items.*.notes' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $userId = $request->user()?->id ?? 1;

        $adj = $this->inventoryService->adjustStock(
            $companyId,
            $validated['warehouse_id'],
            $validated['items'],
            $validated['reason'] ?? null,
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock adjustment recorded successfully.',
            'data' => $adj,
        ]);
    }

    /**
     * Stock Transfer Mutation
     */
    public function transferStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $userId = $request->user()?->id ?? 1;

        $transfer = $this->inventoryService->transferStock(
            $companyId,
            $validated['from_warehouse_id'],
            $validated['to_warehouse_id'],
            $validated['items'],
            $validated['reason'] ?? null,
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock transfer processed successfully.',
            'data' => $transfer,
        ]);
    }

    /**
     * Real CSV Export using Laravel Backend
     */
    public function export(Request $request): StreamedResponse
    {
        $type = $request->input('type', 'stock_summary'); // stock_summary, low_stock, movements
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;

        $filename = "inventory_{$type}_" . date('Y-m-d_His') . ".csv";

        return response()->streamDownload(function () use ($type, $companyId) {
            $handle = fopen('php://output', 'w');

            if ($type === 'low_stock') {
                fputcsv($handle, ['Product ID', 'Name', 'SKU', 'Current Stock', 'Alert Quantity', 'Cost Price']);
                $products = Product::where('company_id', $companyId)->get();
                foreach ($products as $p) {
                    $stock = Stock::where('product_id', $p->id)->sum('quantity');
                    if ($stock <= ($p->alert_quantity ?: 15)) {
                        fputcsv($handle, [$p->id, $p->name, $p->sku, $stock, $p->alert_quantity ?: 15, $p->cost_price]);
                    }
                }
            } else {
                fputcsv($handle, ['Product ID', 'Name', 'SKU', 'Category', 'Total Units', 'Unit Cost', 'Total Value (INR)']);
                $products = Product::with('category')->where('company_id', $companyId)->get();
                foreach ($products as $p) {
                    $stock = (int) Stock::where('product_id', $p->id)->sum('quantity');
                    $cost = (float) ($p->cost_price ?: 1200);
                    fputcsv($handle, [$p->id, $p->name, $p->sku, $p->category?->name ?? 'General', $stock, $cost, round($stock * $cost, 2)]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }
}
