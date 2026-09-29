<?php

namespace App\Services;

use App\Models\CrmAuditLog;
use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmDealItem;
use App\Models\CrmDealStageHistory;
use App\Models\CrmLead;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadConversionService
{
    public function convert(CrmLead $lead, array $data, ?int $userId = null): array
    {
        if ($lead->status === 'converted') {
            throw ValidationException::withMessages([
                'lead' => ['This lead has already been converted.'],
            ]);
        }

        return DB::transaction(function () use ($lead, $data, $userId) {
            $companyId = $lead->company_id;

            // 1. Resolve or Create Customer (Reuse existing if matches name or email)
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('company_id', $companyId)->find($data['customer_id']);
            }

            if (!$customer && (!empty($data['create_customer']) || empty($data['customer_id']))) {
                // Check if customer exists by email or company name
                $existingCustomer = Customer::where('company_id', $companyId)
                    ->where(function ($q) use ($lead) {
                        if ($lead->email) {
                            $q->where('email', $lead->email);
                        }
                        if ($lead->company_name) {
                            $q->orWhere('company_name', $lead->company_name)
                              ->orWhere('name', $lead->company_name);
                        }
                    })
                    ->first();

                if ($existingCustomer) {
                    $customer = $existingCustomer;
                } else {
                    // Create new customer
                    $customerCode = 'CUST-' . strtoupper(uniqid());
                    $customer = Customer::create([
                        'company_id' => $companyId,
                        'customer_code' => $customerCode,
                        'name' => $lead->name ?? $lead->company_name ?? 'Valued Customer',
                        'company_name' => $lead->company_name,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'website' => $lead->website,
                        'status' => 'active',
                    ]);
                }
            }

            // 2. Resolve or Create Contact (Reuse existing if matches email or phone)
            $contact = null;
            if (!empty($data['contact_id'])) {
                $contact = CrmContact::where('company_id', $companyId)->find($data['contact_id']);
            }

            if (!$contact) {
                $existingContact = CrmContact::where('company_id', $companyId)
                    ->where(function ($q) use ($lead) {
                        if ($lead->email) {
                            $q->where('email', $lead->email);
                        }
                        if ($lead->phone) {
                            $q->orWhere('phone', $lead->phone);
                        }
                    })
                    ->first();

                if ($existingContact) {
                    $contact = $existingContact;
                    if ($customer && !$contact->customer_id) {
                        $contact->update(['customer_id' => $customer->id]);
                    }
                } else {
                    $nameParts = explode(' ', $lead->name, 2);
                    $firstName = $nameParts[0] ?? $lead->name;
                    $lastName = $nameParts[1] ?? '';

                    $contact = CrmContact::create([
                        'company_id' => $companyId,
                        'customer_id' => $customer?->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'company_name' => $lead->company_name,
                        'job_title' => $lead->job_title,
                        'email' => $lead->email,
                        'phone' => $lead->phone,
                        'website' => $lead->website,
                        'lead_source_id' => $lead->lead_source_id,
                        'owner_id' => $lead->owner_id ?? $userId,
                        'status' => 'active',
                        'contact_type' => 'business',
                        'notes' => $lead->notes,
                    ]);
                }
            }

            // 3. Optional Deal Creation
            $deal = null;
            if (!empty($data['create_deal'])) {
                $pipeline = CrmPipeline::where('company_id', $companyId)
                    ->when(!empty($data['pipeline_id']), fn($q) => $q->where('id', $data['pipeline_id']))
                    ->first();

                if (!$pipeline) {
                    $pipeline = CrmPipeline::where('company_id', $companyId)->first();
                }

                $stage = null;
                if (!empty($data['stage_id'])) {
                    $stage = CrmPipelineStage::where('pipeline_id', $pipeline?->id)->find($data['stage_id']);
                }
                if (!$stage && $pipeline) {
                    $stage = $pipeline->stages()->orderBy('stage_order')->first();
                }

                $dealValue = (float) ($data['deal_value'] ?? $lead->expected_value ?? $lead->budget ?? 0);
                $probability = $stage ? (int) $stage->probability : 20;
                $expectedRevenue = round(($dealValue * $probability) / 100, 2);

                $deal = CrmDeal::create([
                    'company_id' => $companyId,
                    'customer_id' => $customer?->id,
                    'contact_id' => $contact->id,
                    'lead_id' => $lead->id,
                    'pipeline_id' => $pipeline?->id,
                    'stage_id' => $stage?->id,
                    'name' => $data['deal_name'] ?? ($lead->name . ' - Opportunity'),
                    'value' => $dealValue,
                    'currency' => $data['currency'] ?? 'INR',
                    'probability' => $probability,
                    'expected_revenue' => $expectedRevenue,
                    'expected_close_date' => $data['expected_close_date'] ?? $lead->expected_close_date ?? now()->addMonth()->toDateString(),
                    'owner_id' => $data['owner_id'] ?? $lead->owner_id ?? $userId,
                    'status' => 'open',
                    'notes' => 'Converted from lead #' . $lead->id,
                ]);

                if ($stage) {
                    CrmDealStageHistory::create([
                        'deal_id' => $deal->id,
                        'from_stage_id' => null,
                        'to_stage_id' => $stage->id,
                        'changed_by' => $userId,
                        'notes' => 'Initial stage upon lead conversion',
                    ]);
                }
            }

            // 4. Update Lead Record
            $lead->update([
                'status' => 'converted',
                'contact_id' => $contact->id,
                'customer_id' => $customer?->id,
                'converted_at' => now(),
                'converted_by' => $userId,
            ]);

            // 5. Audit Log
            CrmAuditLog::log(
                'Lead Converted',
                CrmLead::class,
                $lead->id,
                ['status' => 'new'],
                [
                    'status' => 'converted',
                    'customer_id' => $customer?->id,
                    'contact_id' => $contact->id,
                    'deal_id' => $deal?->id,
                ],
                $companyId,
                $userId
            );

            return [
                'lead' => $lead->fresh(['contact', 'customer']),
                'customer' => $customer,
                'contact' => $contact,
                'deal' => $deal,
            ];
        });
    }
}
