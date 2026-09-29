<?php

namespace App\Services;

use App\Jobs\CampaignSendJob;
use App\Models\CrmAuditLog;
use App\Models\CrmCampaign;
use App\Models\CrmCampaignContact;
use App\Models\CrmCampaignEvent;
use Illuminate\Pagination\LengthAwarePaginator;

class CampaignService
{
    protected CampaignAudienceService $audienceService;

    public function __construct(CampaignAudienceService $audienceService)
    {
        $this->audienceService = $audienceService;
    }

    public function list(array $params, ?int $companyId = null): LengthAwarePaginator
    {
        $query = CrmCampaign::query()->with(['owner']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('type', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $perPage = max(5, min(100, (int) ($params['per_page'] ?? 15)));
        return $query->latest()->paginate($perPage);
    }

    public function getDetails(CrmCampaign $campaign): array
    {
        $campaign->load(['owner', 'audiences']);

        $contacts = CrmCampaignContact::where('campaign_id', $campaign->id)
            ->with(['contact', 'lead', 'customer'])
            ->latest()
            ->paginate(15);

        $events = CrmCampaignEvent::where('campaign_id', $campaign->id)
            ->with(['contact', 'lead'])
            ->latest()
            ->take(50)
            ->get();

        // Calculate authoritative metrics & ROI
        $cost = (float) ($campaign->cost > 0 ? $campaign->cost : $campaign->budget);
        $revenue = (float) $campaign->revenue;
        $roi = $cost > 0 ? round((($revenue - $cost) / $cost) * 100, 2) : 0.0;
        $conversionRate = $campaign->total_audience > 0 
            ? round(($campaign->converted_count / $campaign->total_audience) * 100, 1) 
            : 0.0;

        return [
            'campaign' => $campaign,
            'metrics' => [
                'total_audience' => $campaign->total_audience,
                'sent_count' => $campaign->sent_count,
                'delivered_count' => $campaign->delivered_count,
                'opened_count' => $campaign->opened_count,
                'clicked_count' => $campaign->clicked_count,
                'converted_count' => $campaign->converted_count,
                'revenue' => $revenue,
                'cost' => $cost,
                'roi' => $roi,
                'conversion_rate' => $conversionRate,
            ],
            'contacts' => $contacts,
            'events' => $events,
        ];
    }

    public function launch(CrmCampaign $campaign, ?int $userId = null): CrmCampaign
    {
        // Snapshot audience if not done
        if ($campaign->total_audience === 0 && !empty($campaign->target_audience)) {
            $this->audienceService->snapshotAudience($campaign, (array) $campaign->target_audience);
        }

        // Dispatch via Laravel Queue Job
        CampaignSendJob::dispatch($campaign->id);

        $campaign->update(['status' => 'running']);

        CrmAuditLog::log(
            'Campaign Launched',
            CrmCampaign::class,
            $campaign->id,
            ['status' => 'scheduled'],
            ['status' => 'running'],
            $campaign->company_id,
            $userId
        );

        return $campaign->fresh();
    }

    public function pause(CrmCampaign $campaign, ?int $userId = null): CrmCampaign
    {
        $campaign->update(['status' => 'paused']);

        CrmAuditLog::log(
            'Campaign Paused',
            CrmCampaign::class,
            $campaign->id,
            ['status' => 'running'],
            ['status' => 'paused'],
            $campaign->company_id,
            $userId
        );

        return $campaign->fresh();
    }
}
