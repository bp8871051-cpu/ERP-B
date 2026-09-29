<?php

namespace App\Jobs;

use App\Models\CrmCampaign;
use App\Models\CrmCampaignContact;
use App\Models\CrmCampaignEvent;
use App\Models\CrmEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;
    public int $campaignContactId;

    public function __construct(int $campaignId, int $campaignContactId)
    {
        $this->campaignId = $campaignId;
        $this->campaignContactId = $campaignContactId;
    }

    public function handle(): void
    {
        $campaign = CrmCampaign::find($this->campaignId);
        $campaignContact = CrmCampaignContact::with(['contact', 'lead', 'customer'])->find($this->campaignContactId);

        if (!$campaign || !$campaignContact) {
            return;
        }

        $recipientEmail = $campaignContact->contact?->email 
            ?? $campaignContact->lead?->email 
            ?? $campaignContact->customer?->email;

        if (!$recipientEmail) {
            $campaignContact->update(['status' => 'failed']);
            return;
        }

        // Simulate delivery via Laravel Mail system / store email record
        $now = now();
        $campaignContact->update([
            'status' => 'delivered',
            'sent_at' => $now,
        ]);

        $campaign->increment('sent_count');
        $campaign->increment('delivered_count');

        // Log CrmEmail record
        CrmEmail::create([
            'company_id' => $campaign->company_id,
            'user_id' => $campaign->owner_id ?? 1,
            'contact_id' => $campaignContact->contact_id,
            'lead_id' => $campaignContact->lead_id,
            'from_email' => 'campaigns@falconerp.com',
            'to_email' => $recipientEmail,
            'subject' => $campaign->name,
            'body_html' => $campaign->description ?? 'Special Campaign Offer from Falcon ERP',
            'status' => 'delivered',
            'sent_at' => $now,
        ]);

        // Record tracking event
        CrmCampaignEvent::create([
            'campaign_id' => $campaign->id,
            'event_type' => 'sent',
            'contact_id' => $campaignContact->contact_id,
            'lead_id' => $campaignContact->lead_id,
            'metadata' => [
                'recipient' => $recipientEmail,
                'sent_at' => $now->toDateTimeString(),
            ],
        ]);

        Log::info("Campaign {$this->campaignId}: Email sent to {$recipientEmail}.");
    }
}
