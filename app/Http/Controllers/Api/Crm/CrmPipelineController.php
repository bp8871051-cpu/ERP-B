<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Services\PipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmPipelineController extends Controller
{
    protected PipelineService $pipelineService;

    public function __construct(PipelineService $pipelineService)
    {
        $this->pipelineService = $pipelineService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $pipelineId = $request->input('pipeline_id') ? (int) $request->input('pipeline_id') : null;

        $kanban = $this->pipelineService->getKanbanData($pipelineId, $companyId);

        return response()->json([
            'status' => 'success',
            'data' => $kanban,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;

        if (!empty($validated['is_default'])) {
            CrmPipeline::where('company_id', $companyId)->update(['is_default' => false]);
        }

        $pipeline = CrmPipeline::create($validated);

        // Add 5 default stages
        $stages = [
            ['name' => 'New', 'stage_order' => 1, 'order' => 1, 'probability' => 10, 'color' => '#64748B', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Qualified', 'stage_order' => 2, 'order' => 2, 'probability' => 25, 'color' => '#0F8B7A', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Proposal', 'stage_order' => 3, 'order' => 3, 'probability' => 50, 'color' => '#2563EB', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Negotiation', 'stage_order' => 4, 'order' => 4, 'probability' => 75, 'color' => '#D97706', 'is_won' => false, 'is_lost' => false],
            ['name' => 'Won', 'stage_order' => 5, 'order' => 5, 'probability' => 100, 'color' => '#10B981', 'is_won' => true, 'is_lost' => false],
            ['name' => 'Lost', 'stage_order' => 6, 'order' => 6, 'probability' => 0, 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $s) {
            $pipeline->stages()->create($s);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pipeline created successfully',
            'data' => $pipeline->load('stages'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $pipeline = CrmPipeline::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        if (!empty($validated['is_default'])) {
            CrmPipeline::where('company_id', $companyId)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $pipeline->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Pipeline updated successfully',
            'data' => $pipeline->fresh('stages'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $pipeline = CrmPipeline::where('company_id', $companyId)->findOrFail($id);

        $pipeline->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pipeline deleted successfully',
        ]);
    }

    public function addStage(Request $request, int $id): JsonResponse
    {
        $pipeline = CrmPipeline::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'stage_order' => 'nullable|integer',
            'probability' => 'required|integer|between:0,100',
            'color' => 'nullable|string|max:20',
            'is_won' => 'nullable|boolean',
            'is_lost' => 'nullable|boolean',
        ]);

        $maxOrder = $pipeline->stages()->max('stage_order') ?? 0;
        $validated['stage_order'] = $validated['stage_order'] ?? ($maxOrder + 1);
        $validated['order'] = $validated['stage_order'];
        $validated['color'] = $validated['color'] ?? '#0F8B7A';

        $stage = $pipeline->stages()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Stage added successfully',
            'data' => $stage,
        ], 201);
    }

    public function updateStage(Request $request, int $id, int $stageId): JsonResponse
    {
        $stage = CrmPipelineStage::where('pipeline_id', $id)->findOrFail($stageId);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'probability' => 'sometimes|required|integer|between:0,100',
            'color' => 'nullable|string|max:20',
            'is_won' => 'nullable|boolean',
            'is_lost' => 'nullable|boolean',
        ]);

        $stage->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Stage updated successfully',
            'data' => $stage,
        ]);
    }

    public function deleteStage(Request $request, int $id, int $stageId): JsonResponse
    {
        $stage = CrmPipelineStage::where('pipeline_id', $id)->findOrFail($stageId);
        $stage->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stage deleted successfully',
        ]);
    }

    public function reorderStages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:crm_pipeline_stages,id',
            'stages.*.order' => 'required|integer',
        ]);

        $this->pipelineService->reorderStages($id, $request->input('stages'));

        return response()->json([
            'status' => 'success',
            'message' => 'Stages reordered successfully',
        ]);
    }
}
