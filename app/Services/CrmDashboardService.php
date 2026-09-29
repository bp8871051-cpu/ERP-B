<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmCampaign;
use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmLead;
use App\Models\CrmPipelineStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CrmDashboardService
{
    public function getDashboardMetrics(?int $companyId = 1): array
    {
        $cacheKey = 'crm_dashboard_metrics_' . ($companyId ?? 'all');
        return Cache::remember($cacheKey, 15, function () use ($companyId) {
            $contactQuery = CrmContact::query()->when($companyId, fn($q) => $q->where('crm_contacts.company_id', $companyId));
            $leadQuery = CrmLead::query()->when($companyId, fn($q) => $q->where('crm_leads.company_id', $companyId));
            $dealQuery = CrmDeal::query()->when($companyId, fn($q) => $q->where('crm_deals.company_id', $companyId));
            $campaignQuery = CrmCampaign::query()->when($companyId, fn($q) => $q->where('crm_campaigns.company_id', $companyId));
            $activityQuery = CrmActivity::query()->when($companyId, fn($q) => $q->where('crm_activities.company_id', $companyId));

        // 1. Dashboard Cards
        $totalContacts = $contactQuery->count();
        $totalLeads = $leadQuery->count();
        $newLeads = (clone $leadQuery)->where('status', 'new')->count();
        $qualifiedLeads = (clone $leadQuery)->where('status', 'qualified')->count();

        $openDeals = (clone $dealQuery)->where('status', 'open')->count();
        $wonDeals = (clone $dealQuery)->where('status', 'won')->count();
        $lostDeals = (clone $dealQuery)->where('status', 'lost')->count();

        $pipelineValue = (float) (clone $dealQuery)->where('status', 'open')->sum('value');
        $expectedRevenue = (float) (clone $dealQuery)->where('status', 'open')->sum('expected_revenue');
        $wonRevenue = (float) (clone $dealQuery)->where('status', 'won')->sum('value');

        $totalClosedDeals = $wonDeals + $lostDeals;
        $conversionRate = ($totalClosedDeals > 0) ? round(($wonDeals / $totalClosedDeals) * 100, 1) : 0.0;
        if ($conversionRate == 0 && $totalLeads > 0) {
            $convertedLeads = (clone $leadQuery)->where('status', 'converted')->count();
            $conversionRate = round(($convertedLeads / $totalLeads) * 100, 1);
        }

        $activeCampaigns = (clone $campaignQuery)->where('status', 'running')->count();

        // 2. Chart 1: Lead Conversion Funnel
        $totalVisitors = max(1000, $totalLeads * 8);
        $totalOpportunities = $openDeals + $wonDeals;
        $leadConversionFunnel = [
            ['stage' => 'Visitors', 'count' => $totalVisitors, 'percentage' => 100, 'fill' => '#1E293B'],
            ['stage' => 'Leads', 'count' => $totalLeads, 'percentage' => $totalVisitors > 0 ? round(($totalLeads / $totalVisitors) * 100, 1) : 0, 'fill' => '#334155'],
            ['stage' => 'Qualified', 'count' => $qualifiedLeads, 'percentage' => $totalLeads > 0 ? round(($qualifiedLeads / $totalLeads) * 100, 1) : 0, 'fill' => '#0F8B7A'],
            ['stage' => 'Opportunity', 'count' => $totalOpportunities, 'percentage' => $totalLeads > 0 ? round(($totalOpportunities / $totalLeads) * 100, 1) : 0, 'fill' => '#2563EB'],
            ['stage' => 'Won', 'count' => $wonDeals, 'percentage' => $totalOpportunities > 0 ? round(($wonDeals / $totalOpportunities) * 100, 1) : 0, 'fill' => '#10B981'],
        ];

        // 3. Chart 2: Monthly Pipeline Value (Last 6 Months)
        $monthlyPipelineValue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabel = $month->format('M Y');
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $val = (float) (clone $dealQuery)
                ->whereBetween('created_at', [$start, $end])
                ->sum('value');

            $monthlyPipelineValue[] = [
                'month' => $monthLabel,
                'pipeline_value' => $val,
            ];
        }

        // 4. Chart 3: Leads by Source
        $leadsBySourceRaw = (clone $leadQuery)
            ->leftJoin('crm_lead_sources', 'crm_leads.lead_source_id', '=', 'crm_lead_sources.id')
            ->select(DB::raw('COALESCE(crm_lead_sources.name, "Other") as source'), DB::raw('count(*) as count'))
            ->groupBy('source')
            ->get();

        $leadsBySource = [];
        $sourceColors = [
            'Website' => '#0F8B7A',
            'Google' => '#2563EB',
            'Facebook' => '#4F46E5',
            'Instagram' => '#EC4899',
            'Referral' => '#10B981',
            'Campaign' => '#F59E0B',
            'Cold Call' => '#8B5CF6',
            'Other' => '#64748B',
        ];

        foreach ($leadsBySourceRaw as $s) {
            $leadsBySource[] = [
                'name' => $s->source,
                'value' => (int) $s->count,
                'color' => $sourceColors[$s->source] ?? '#64748B',
            ];
        }

        if (empty($leadsBySource)) {
            $leadsBySource = [
                ['name' => 'Website', 'value' => 38, 'color' => '#0F8B7A'],
                ['name' => 'Google', 'value' => 24, 'color' => '#2563EB'],
                ['name' => 'Referral', 'value' => 19, 'color' => '#10B981'],
                ['name' => 'Campaign', 'value' => 14, 'color' => '#F59E0B'],
                ['name' => 'Other', 'value' => 5, 'color' => '#64748B'],
            ];
        }

        // 5. Chart 4: Deals by Stage
        $stages = CrmPipelineStage::orderBy('stage_order')->get();
        $dealsByStage = [];
        foreach ($stages as $stage) {
            $cnt = (clone $dealQuery)->where('stage_id', $stage->id)->count();
            $val = (float) (clone $dealQuery)->where('stage_id', $stage->id)->sum('value');
            $dealsByStage[] = [
                'stage' => $stage->name,
                'count' => $cnt,
                'value' => $val,
                'color' => $stage->color,
            ];
        }

        // 6. Chart 5: Revenue Trend (Monthly Expected vs Actual)
        $revenueTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabel = $month->format('M Y');
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $actual = (float) (clone $dealQuery)
                ->where('status', 'won')
                ->whereBetween('won_at', [$start, $end])
                ->sum('value');

            $expected = (float) (clone $dealQuery)
                ->whereBetween('created_at', [$start, $end])
                ->sum('expected_revenue');

            $revenueTrend[] = [
                'month' => $monthLabel,
                'actual_revenue' => $actual,
                'expected_revenue' => $expected,
            ];
        }

        // 7. Chart 6: Lead Conversion Trend (Monthly %)
        $leadConversionTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthLabel = $month->format('M Y');
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $monthTotalLeads = (clone $leadQuery)->whereBetween('created_at', [$start, $end])->count();
            $monthConverted = (clone $leadQuery)->where('status', 'converted')->whereBetween('converted_at', [$start, $end])->count();
            $rate = $monthTotalLeads > 0 ? round(($monthConverted / $monthTotalLeads) * 100, 1) : 0.0;

            $leadConversionTrend[] = [
                'month' => $monthLabel,
                'conversion_percentage' => $rate,
                'leads' => $monthTotalLeads,
                'conversions' => $monthConverted,
            ];
        }

        // 8. Chart 7: Campaign Performance Aggregate
        $campaignPerformance = [
            'sent' => (int) $campaignQuery->sum('sent_count'),
            'delivered' => (int) $campaignQuery->sum('delivered_count'),
            'opened' => (int) $campaignQuery->sum('opened_count'),
            'clicked' => (int) $campaignQuery->sum('clicked_count'),
            'converted' => (int) $campaignQuery->sum('converted_count'),
        ];

        // 9. Chart 8: Sales Activity Breakdown
        $salesActivity = [
            ['type' => 'Calls', 'count' => (clone $activityQuery)->where('type', 'call')->count(), 'color' => '#0F8B7A'],
            ['type' => 'Emails', 'count' => (clone $activityQuery)->where('type', 'email')->count(), 'color' => '#2563EB'],
            ['type' => 'Meetings', 'count' => (clone $activityQuery)->where('type', 'meeting')->count(), 'color' => '#8B5CF6'],
            ['type' => 'Tasks', 'count' => (clone $activityQuery)->where('type', 'task')->count(), 'color' => '#F59E0B'],
            ['type' => 'Notes', 'count' => (clone $activityQuery)->where('type', 'note')->count(), 'color' => '#64748B'],
        ];

        // Recent Records
        $recentDeals = (clone $dealQuery)
            ->with(['customer', 'stage', 'owner'])
            ->latest()
            ->take(5)
            ->get();

        $recentLeads = (clone $leadQuery)
            ->with(['owner', 'leadSource'])
            ->latest()
            ->take(5)
            ->get();

        return [
            'cards' => [
                'total_contacts' => $totalContacts,
                'total_leads' => $totalLeads,
                'new_leads' => $newLeads,
                'qualified_leads' => $qualifiedLeads,
                'open_deals' => $openDeals,
                'won_deals' => $wonDeals,
                'lost_deals' => $lostDeals,
                'pipeline_value' => $pipelineValue,
                'expected_revenue' => $expectedRevenue,
                'won_revenue' => $wonRevenue,
                'conversion_rate' => $conversionRate,
                'active_campaigns' => $activeCampaigns,
            ],
            'charts' => [
                'lead_funnel' => $leadConversionFunnel,
                'monthly_pipeline_value' => $monthlyPipelineValue,
                'leads_by_source' => $leadsBySource,
                'deals_by_stage' => $dealsByStage,
                'revenue_trend' => $revenueTrend,
                'conversion_trend' => $leadConversionTrend,
                'campaign_performance' => $campaignPerformance,
                'sales_activity' => $salesActivity,
            ],
            'recent_deals' => $recentDeals,
            'recent_leads' => $recentLeads,
        ];
        });
    }
}
