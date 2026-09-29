<?php

namespace App\Jobs;

use App\Models\CrmCampaign;
use App\Models\CrmCampaignContact;
use App\Models\CrmAuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampaignSendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;

    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function handle(): void
    {
        $campaign = CrmCampaign::find($this->campaignId);
        if (!$campaign) {
            Log::warning("Campaign {$this->campaignId} not found for CampaignSendJob.");
            return;
        }

        $campaign->update(['status' => 'running']);

        $pendingContacts = CrmCampaignContact::where('campaign_id', $this->campaignId)
            ->where('status', 'pending')
            ->get();

        foreach ($pendingContacts as $contact) {
            CampaignEmailJob::dispatch($campaign->id, $contact->id);
        }

        CrmAuditLog::log(
            'Campaign Launched',
            CrmCampaign::class,
            $campaign->id,
            null,
            ['status' => 'running', 'queued_count' => $pendingContacts->count()],
            $campaign->company_id,
            $campaign->owner_id
        );

        Log::info("Campaign {$this->campaignId} dispatched {$pendingContacts->count()} email jobs.");
    }
}
