<?php

namespace App\Services;

use App\Models\CrmCampaign;
use App\Models\CrmCampaignAudience;
use App\Models\CrmCampaignContact;
use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CampaignAudienceService
{
    /**
     * Evaluate filter criteria and snapshot contacts/leads into crm_campaign_contacts
     */
    public function snapshotAudience(CrmCampaign $campaign, array $filters = []): int
    {
        return DB::transaction(function () use ($campaign, $filters) {
            $companyId = $campaign->company_id;

            // Target Contacts query
            $contactsQuery = CrmContact::where('company_id', $companyId)->where('status', 'active');
            $leadsQuery = CrmLead::where('company_id', $companyId)->whereNotIn('status', ['converted', 'lost']);

            // Apply filters
            if (!empty($filters['lead_source_id'])) {
                $contactsQuery->where('lead_source_id', $filters['lead_source_id']);
                $leadsQuery->where('lead_source_id', $filters['lead_source_id']);
            }

            if (!empty($filters['lead_status'])) {
                $leadsQuery->where('status', $filters['lead_status']);
            }

            if (!empty($filters['owner_id'])) {
                $contactsQuery->where('owner_id', $filters['owner_id']);
                $leadsQuery->where('owner_id', $filters['owner_id']);
            }

            if (!empty($filters['city'])) {
                $contactsQuery->where('city', $filters['city']);
            }

            if (!empty($filters['industry'])) {
                $leadsQuery->where('industry', $filters['industry']);
            }

            $matchedContacts = $contactsQuery->take(500)->get();
            $matchedLeads = $leadsQuery->take(500)->get();

            // Clear previous pending snapshot for this campaign
            CrmCampaignContact::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->delete();

            $totalAdded = 0;

            foreach ($matchedContacts as $c) {
                if (!empty($c->email)) {
                    CrmCampaignContact::firstOrCreate([
                        'campaign_id' => $campaign->id,
                        'contact_id' => $c->id,
                    ], [
                        'customer_id' => $c->customer_id,
                        'status' => 'pending',
                    ]);
                    $totalAdded++;
                }
            }

            foreach ($matchedLeads as $l) {
                if (!empty($l->email)) {
                    CrmCampaignContact::firstOrCreate([
                        'campaign_id' => $campaign->id,
                        'lead_id' => $l->id,
                    ], [
                        'contact_id' => $l->contact_id,
                        'customer_id' => $l->customer_id,
                        'status' => 'pending',
                    ]);
                    $totalAdded++;
                }
            }

            // Save audience snapshot metadata
            CrmCampaignAudience::create([
                'campaign_id' => $campaign->id,
                'filter_criteria' => $filters,
                'total_count' => $totalAdded,
            ]);

            $campaign->update([
                'total_audience' => $totalAdded,
                'target_audience' => $filters,
            ]);

            return $totalAdded;
        });
    }
}
