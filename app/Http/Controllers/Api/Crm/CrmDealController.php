<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmDeal;
use App\Models\CrmPipelineStage;
use App\Services\CrmReportService;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmDealController extends Controller
{
    protected DealService $dealService;
    protected CrmReportService $reportService;

    public function __construct(DealService $dealService, CrmReportService $reportService)
    {
        $this->dealService = $dealService;
        $this->reportService = $reportService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);

        $query = CrmDeal::query()
            ->with(['customer', 'contact', 'pipeline', 'stage', 'owner', 'items']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($request->search)) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('contact', fn($c) => $c->where('first_name', 'like', "%{$s}%"));
            });
        }

        if (!empty($request->stage_id)) {
            $query->where('stage_id', $request->stage_id);
        }

        if (!empty($request->pipeline_id)) {
            $query->where('pipeline_id', $request->pipeline_id);
        }

        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        if (!empty($request->owner_id)) {
            $query->where('owner_id', $request->owner_id);
        }

        $perPage = max(5, min(100, (int) ($request->per_page ?? 15)));
        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'pipeline_id' => 'required|exists:crm_pipelines,id',
            'stage_id' => 'required|exists:crm_pipeline_stages,id',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'probability' => 'nullable|integer|between:0,100',
            'expected_close_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:open,won,lost',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['owner_id'] = $validated['owner_id'] ?? $request->user()?->id ?? 1;
        $validated['currency'] = $validated['currency'] ?? 'INR';
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $deal = CrmDeal::create($validated);
        $deal = $this->dealService->calculateAndSaveDeal($deal, $items, $validated);

        CrmAuditLog::log(
            'Deal Created',
            CrmDeal::class,
            $deal->id,
            null,
            $deal->toArray(),
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Deal created successfully',
            'data' => $deal,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $deal = CrmDeal::where('company_id', $companyId)
            ->with(['customer', 'contact', 'pipeline', 'stage', 'owner', 'items', 'stageHistory.fromStage', 'stageHistory.toStage', 'stageHistory.changedByUser', 'activities', 'tasks', 'calls', 'emails', 'salesOrder'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $deal,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $deal = CrmDeal::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'pipeline_id' => 'sometimes|required|exists:crm_pipelines,id',
            'stage_id' => 'sometimes|required|exists:crm_pipeline_stages,id',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'probability' => 'nullable|integer|between:0,100',
            'expected_close_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:open,won,lost',
            'lost_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $updatedDeal = $this->dealService->calculateAndSaveDeal($deal, $items, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Deal updated successfully',
            'data' => $updatedDeal,
        ]);
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stage_id' => 'required|exists:crm_pipeline_stages,id',
            'notes' => 'nullable|string|max:255',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $deal = CrmDeal::where('company_id', $companyId)->findOrFail($id);

        $updatedDeal = $this->dealService->updateStage(
            $deal,
            (int) $request->input('stage_id'),
            $request->user()?->id,
            $request->input('notes')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Deal stage updated successfully',
            'data' => $updatedDeal,
        ]);
    }

    public function createSalesOrder(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $deal = CrmDeal::where('company_id', $companyId)->findOrFail($id);

        $salesOrder = $this->dealService->createSalesOrderFromDeal($deal, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Sales Order generated successfully from deal #' . $deal->id,
            'data' => [
                'deal' => $deal->fresh(),
                'sales_order' => $salesOrder->load(['customer', 'items']),
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $deal = CrmDeal::where('company_id', $companyId)->findOrFail($id);

        $deal->delete();

        CrmAuditLog::log(
            'Deal Deleted',
            CrmDeal::class,
            $id,
            null,
            null,
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Deal deleted successfully',
        ]);
    }

    public function export(Request $request)
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        return $this->reportService->exportDealsCsv($companyId);
    }
}
