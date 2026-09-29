<?php

namespace App\Services;

use App\Models\CrmCampaign;
use App\Models\CrmCampaignContact;
use App\Models\CrmCampaignEvent;

class CampaignTrackingService
{
    public function recordEvent(int $campaignId, string $eventType, ?int $contactId = null, ?int $leadId = null, array $metadata = []): CrmCampaignEvent
    {
        $campaign = CrmCampaign::findOrFail($campaignId);

        $event = CrmCampaignEvent::create([
            'campaign_id' => $campaignId,
            'event_type' => $eventType,
            'contact_id' => $contactId,
            'lead_id' => $leadId,
            'metadata' => $metadata,
        ]);

        // Update campaign contact status
        $query = CrmCampaignContact::where('campaign_id', $campaignId);
        if ($contactId) {
            $query->where('contact_id', $contactId);
        } elseif ($leadId) {
            $query->where('lead_id', $leadId);
        }
        $campaignContact = $query->first();

        $now = now();
        switch ($eventType) {
            case 'opened':
                $campaign->increment('opened_count');
                if ($campaignContact) {
                    $campaignContact->update(['opened_at' => $now, 'status' => 'opened']);
                }
                break;

            case 'clicked':
                $campaign->increment('clicked_count');
                if ($campaignContact) {
                    $campaignContact->update(['clicked_at' => $now, 'status' => 'clicked']);
                }
                break;

            case 'converted':
                $campaign->increment('converted_count');
                if ($campaignContact) {
                    $campaignContact->update(['status' => 'converted']);
                }
                break;

            case 'bounced':
                if ($campaignContact) {
                    $campaignContact->update(['status' => 'bounced']);
                }
                break;
        }

        // Recalculate ROI
        $this->recalculateRoi($campaign);

        return $event;
    }

    public function recalculateRoi(CrmCampaign $campaign): void
    {
        if ($campaign->cost > 0) {
            $roi = (($campaign->revenue - $campaign->cost) / $campaign->cost) * 100;
            $campaign->update(['roi' => round($roi, 2)]);
        }
    }
}
