<?php

namespace App\Services;

use App\Models\CrmAuditLog;
use App\Models\CrmDeal;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use Illuminate\Support\Facades\DB;

class PipelineService
{
    protected DealService $dealService;

    public function __construct(DealService $dealService)
    {
        $this->dealService = $dealService;
    }

    public function getKanbanData(?int $pipelineId = null, ?int $companyId = 1): array
    {
        $query = CrmPipeline::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $pipeline = $pipelineId
            ? $query->where('id', $pipelineId)->first()
            : $query->where('is_default', true)->first() ?? $query->first();

        if (!$pipeline) {
            // Auto create standard default pipeline if none exists
            $pipeline = $this->createDefaultPipeline($companyId ?? 1);
        }

        $stages = CrmPipelineStage::where('pipeline_id', $pipeline->id)
            ->orderBy('stage_order')
            ->get();

        $stageData = [];
        $totalDeals = 0;
        $totalPipelineValue = 0.0;
        $weightedPipeline = 0.0;
        $wonValue = 0.0;
        $lostValue = 0.0;
        $wonDealsCount = 0;

        foreach ($stages as $stage) {
            $dealsQuery = CrmDeal::where('pipeline_id', $pipeline->id)
                ->where('stage_id', $stage->id)
                ->with(['customer', 'contact', 'owner', 'items']);

            if ($companyId) {
                $dealsQuery->where('company_id', $companyId);
            }

            $deals = $dealsQuery->latest()->get();
            $stageValue = $deals->sum('value');
            $stageWeighted = $deals->sum('expected_revenue');

            $totalDeals += $deals->count();
            $totalPipelineValue += $stageValue;
            $weightedPipeline += $stageWeighted;

            if ($stage->is_won) {
                $wonValue += $stageValue;
                $wonDealsCount += $deals->count();
            } elseif ($stage->is_lost) {
                $lostValue += $stageValue;
            }

            $stageData[] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'order' => $stage->stage_order,
                'probability' => $stage->probability,
                'color' => $stage->color,
                'is_won' => (bool) $stage->is_won,
                'is_lost' => (bool) $stage->is_lost,
                'total_deals' => $deals->count(),
                'total_value' => (float) $stageValue,
                'deals' => $deals,
            ];
        }

        $conversionRate = $totalDeals > 0 ? round(($wonDealsCount / $totalDeals) * 100, 1) : 0.0;

        $allPipelines = CrmPipeline::when($companyId, fn($q) => $q->where('company_id', $companyId))->get(['id', 'name', 'is_default']);

        return [
            'pipeline' => $pipeline,
            'all_pipelines' => $allPipelines,
            'summary' => [
                'total_deals' => $totalDeals,
                'total_pipeline_value' => round($totalPipelineValue, 2),
                'weighted_pipeline' => round($weightedPipeline, 2),
                'won_value' => round($wonValue, 2),
                'lost_value' => round($lostValue, 2),
                'conversion_rate' => $conversionRate,
            ],
            'stages' => $stageData,
        ];
    }

    public function reorderStages(int $pipelineId, array $stageOrders): void
    {
        DB::transaction(function () use ($pipelineId, $stageOrders) {
            foreach ($stageOrders as $item) {
                CrmPipelineStage::where('pipeline_id', $pipelineId)
                    ->where('id', $item['id'])
                    ->update(['stage_order' => $item['order']]);
            }
        });
    }

    public function createDefaultPipeline(int $companyId): CrmPipeline
    {
        return DB::transaction(function () use ($companyId) {
            $pipeline = CrmPipeline::create([
                'company_id' => $companyId,
                'name' => 'Standard Sales Pipeline',
                'code' => 'STANDARD',
                'is_default' => true,
                'status' => 'active',
            ]);

            $stages = [
                ['name' => 'New', 'order' => 1, 'probability' => 10, 'color' => '#64748B', 'is_won' => false, 'is_lost' => false],
                ['name' => 'Qualified', 'order' => 2, 'probability' => 25, 'color' => '#0F8B7A', 'is_won' => false, 'is_lost' => false],
                ['name' => 'Proposal', 'order' => 3, 'probability' => 50, 'color' => '#2563EB', 'is_won' => false, 'is_lost' => false],
                ['name' => 'Negotiation', 'order' => 4, 'probability' => 75, 'color' => '#D97706', 'is_won' => false, 'is_lost' => false],
                ['name' => 'Won', 'order' => 5, 'probability' => 100, 'color' => '#10B981', 'is_won' => true, 'is_lost' => false],
                ['name' => 'Lost', 'order' => 6, 'probability' => 0, 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true],
            ];

            foreach ($stages as $s) {
                CrmPipelineStage::create([
                    'pipeline_id' => $pipeline->id,
                    'name' => $s['name'],
                    'stage_order' => $s['order'],
                    'order' => $s['order'],
                    'slug' => strtolower($s['name']),
                    'probability' => $s['probability'],
                    'color' => $s['color'],
                    'is_won' => $s['is_won'],
                    'is_lost' => $s['is_lost'],
                ]);
            }

            return $pipeline;
        });
    }
}
