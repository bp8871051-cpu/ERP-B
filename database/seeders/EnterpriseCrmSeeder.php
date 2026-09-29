<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use App\Models\CrmCall;
use App\Models\CrmCampaign;
use App\Models\CrmCampaignAudience;
use App\Models\CrmCampaignContact;
use App\Models\CrmCampaignEvent;
use App\Models\CrmContact;
use App\Models\CrmCustomerSegment;
use App\Models\CrmDeal;
use App\Models\CrmDealItem;
use App\Models\CrmDealStageHistory;
use App\Models\CrmEmail;
use App\Models\CrmFeedback;
use App\Models\CrmLead;
use App\Models\CrmLeadScore;
use App\Models\CrmLeadSource;
use App\Models\CrmMeeting;
use App\Models\CrmNote;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\CrmSegmentMember;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CustomerSegmentationService;
use App\Services\LeadScoringService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnterpriseCrmSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create([
            'name' => 'Falcon Technologies Pvt Ltd',
            'legal_name' => 'Falcon Technologies India Private Limited',
            'code' => 'FTIPL',
            'email' => 'contact@falcontech.in',
            'phone' => '+91 22 4589 7700',
            'currency' => 'INR',
            'country' => 'India',
        ]);
        $companyId = $company->id;

        $adminUser = User::where('role', 'Super Admin')->first() ?? User::first();
        $userId = $adminUser?->id ?? 1;

        // 1. Seed CRM Permissions
        $permissions = [
            'crm.view', 'crm.create', 'crm.edit', 'crm.delete', 'crm.import', 'crm.export',
            'crm.contacts.view', 'crm.leads.view', 'crm.leads.convert',
            'crm.deals.view', 'crm.deals.edit', 'crm.pipeline.manage',
            'crm.campaigns.manage', 'crm.feedback.manage', 'crm.analytics.view', 'crm.activities.manage',
        ];

        foreach ($permissions as $pName) {
            $perm = Permission::firstOrCreate(['name' => $pName], [
                'module' => 'CRM',
                'description' => 'Permission for ' . str_replace('.', ' ', $pName),
            ]);

            // Assign to Super Admin role
            $superAdminRole = Role::where('name', 'Super Admin')->first();
            if ($superAdminRole && !$superAdminRole->permissions->contains('id', $perm->id)) {
                $superAdminRole->permissions()->attach($perm->id);
            }
        }

        // 2. Lead Sources
        $sourceNames = [
            'Website' => 'WEB',
            'Google Search' => 'GOOG',
            'Facebook' => 'FB',
            'Instagram' => 'INSTA',
            'LinkedIn' => 'LNKD',
            'Referral' => 'REF',
            'Campaign' => 'CAMP',
            'Cold Call' => 'COLD',
            'Partner Network' => 'PART',
            'Trade Expo' => 'EXPO',
        ];

        $sourceMap = [];
        foreach ($sourceNames as $name => $code) {
            $source = CrmLeadSource::firstOrCreate([
                'company_id' => $companyId,
                'name' => $name,
            ], [
                'code' => $code,
                'is_active' => true,
            ]);
            $sourceMap[$name] = $source->id;
        }
        $sourceIds = array_values($sourceMap);

        // 3. Pipelines & 20 Stages across 3 Pipelines
        $pipelineData = [
            [
                'name' => 'Enterprise Sales Pipeline',
                'code' => 'ENT_SALES',
                'is_default' => true,
                'stages' => [
                    ['name' => 'New', 'order' => 1, 'probability' => 10, 'color' => '#64748B', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Qualified', 'order' => 2, 'probability' => 25, 'color' => '#0F8B7A', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Proposal', 'order' => 3, 'probability' => 50, 'color' => '#2563EB', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Negotiation', 'order' => 4, 'probability' => 75, 'color' => '#D97706', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Won', 'order' => 5, 'probability' => 100, 'color' => '#10B981', 'is_won' => true, 'is_lost' => false],
                    ['name' => 'Lost', 'order' => 6, 'probability' => 0, 'color' => '#EF4444', 'is_won' => false, 'is_lost' => true],
                ],
            ],
            [
                'name' => 'Government & PSU Tender Pipeline',
                'code' => 'GOV_TENDER',
                'is_default' => false,
                'stages' => [
                    ['name' => 'RFP Sourced', 'order' => 1, 'probability' => 15, 'color' => '#475569', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Eligibility Clearance', 'order' => 2, 'probability' => 30, 'color' => '#0284C7', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Bid Submission', 'order' => 3, 'probability' => 45, 'color' => '#6366F1', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Technical Evaluation', 'order' => 4, 'probability' => 65, 'color' => '#8B5CF6', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Commercial L1 Award', 'order' => 5, 'probability' => 85, 'color' => '#F59E0B', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Order Signed', 'order' => 6, 'probability' => 100, 'color' => '#059669', 'is_won' => true, 'is_lost' => false],
                    ['name' => 'Bid Disqualified', 'order' => 7, 'probability' => 0, 'color' => '#DC2626', 'is_won' => false, 'is_lost' => true],
                ],
            ],
            [
                'name' => 'SaaS Subscription & Renewals',
                'code' => 'SAAS_RENEWAL',
                'is_default' => false,
                'stages' => [
                    ['name' => 'Trial Inbound', 'order' => 1, 'probability' => 20, 'color' => '#0EA5E9', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Product Demo Given', 'order' => 2, 'probability' => 40, 'color' => '#3B82F6', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Security Audit', 'order' => 3, 'probability' => 60, 'color' => '#14B8A6', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Procurement Review', 'order' => 4, 'probability' => 80, 'color' => '#F97316', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Contract Finalized', 'order' => 5, 'probability' => 90, 'color' => '#10B981', 'is_won' => false, 'is_lost' => false],
                    ['name' => 'Active Subscription', 'order' => 6, 'probability' => 100, 'color' => '#047857', 'is_won' => true, 'is_lost' => false],
                    ['name' => 'Churned', 'order' => 7, 'probability' => 0, 'color' => '#E11D48', 'is_won' => false, 'is_lost' => true],
                ],
            ],
        ];

        $allStageIds = [];
        $defaultPipeline = null;
        foreach ($pipelineData as $pData) {
            $pipeline = CrmPipeline::firstOrCreate([
                'company_id' => $companyId,
                'code' => $pData['code'],
            ], [
                'name' => $pData['name'],
                'is_default' => $pData['is_default'],
                'status' => 'active',
            ]);

            if ($pData['is_default']) {
                $defaultPipeline = $pipeline;
            }

            foreach ($pData['stages'] as $sData) {
                $stage = CrmPipelineStage::firstOrCreate([
                    'pipeline_id' => $pipeline->id,
                    'name' => $sData['name'],
                ], [
                    'stage_order' => $sData['order'],
                    'slug' => strtolower(str_replace(' ', '_', $sData['name'])),
                    'order' => $sData['order'],
                    'probability' => $sData['probability'],
                    'color' => $sData['color'],
                    'is_won' => $sData['is_won'],
                    'is_lost' => $sData['is_lost'],
                ]);
                $allStageIds[] = $stage->id;
            }
        }

        // 4. Seed 10 Campaigns
        $campaignConfigs = [
            ['name' => 'Diwali Corporate Tech Expo 2026', 'type' => 'event', 'budget' => 250000, 'revenue' => 1180000, 'status' => 'completed'],
            ['name' => 'Q3 Enterprise Cloud Modernization', 'type' => 'email', 'budget' => 75000, 'revenue' => 450000, 'status' => 'running'],
            ['name' => 'WhatsApp Quick Commerce Push', 'type' => 'whatsapp', 'budget' => 35000, 'revenue' => 195000, 'status' => 'running'],
            ['name' => 'Pan-India CIO Summit 2026', 'type' => 'event', 'budget' => 350000, 'revenue' => 1850000, 'status' => 'completed'],
            ['name' => 'LinkedIn B2B Leadership Inbound', 'type' => 'social_media', 'budget' => 85000, 'revenue' => 320000, 'status' => 'running'],
            ['name' => 'SME Manufacturing ERP Blast', 'type' => 'email', 'budget' => 45000, 'revenue' => 280000, 'status' => 'completed'],
            ['name' => 'BFSI Compliance & Audit Seminar', 'type' => 'event', 'budget' => 120000, 'revenue' => 640000, 'status' => 'completed'],
            ['name' => 'SMS Flash Discount on Multi-Warehouse', 'type' => 'sms', 'budget' => 20000, 'revenue' => 95000, 'status' => 'completed'],
            ['name' => 'Outbound Cold Call Blitzkrieg', 'type' => 'phone', 'budget' => 50000, 'revenue' => 180000, 'status' => 'paused'],
            ['name' => 'Year-End Enterprise License Upgrades', 'type' => 'email', 'budget' => 90000, 'revenue' => 540000, 'status' => 'scheduled'],
        ];

        $campaignIds = [];
        foreach ($campaignConfigs as $c) {
            $cost = $c['budget'];
            $rev = $c['revenue'];
            $roi = $cost > 0 ? round((($rev - $cost) / $cost) * 100, 2) : 0;
            $camp = CrmCampaign::firstOrCreate([
                'company_id' => $companyId,
                'name' => $c['name'],
            ], [
                'type' => $c['type'],
                'description' => "Enterprise B2B growth campaign focusing on {$c['name']}",
                'start_date' => Carbon::now()->subMonths(2)->toDateString(),
                'end_date' => Carbon::now()->addMonths(1)->toDateString(),
                'owner_id' => $userId,
                'budget' => $c['budget'],
                'status' => $c['status'],
                'total_audience' => rand(150, 450),
                'sent_count' => rand(120, 400),
                'delivered_count' => rand(110, 390),
                'opened_count' => rand(70, 250),
                'clicked_count' => rand(30, 140),
                'converted_count' => rand(8, 45),
                'revenue' => $rev,
                'cost' => $cost,
                'roi' => $roi,
            ]);
            $campaignIds[] = $camp->id;
        }

        // Indian First/Last Names & Companies for Realism
        $firstNames = ['Aarav', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Ayaan', 'Krishna', 'Ishaan', 'Shaurya', 'Atharv', 'Rohan', 'Dhruv', 'Kabir', 'Ananya', 'Diya', 'Isha', 'Aadhya', 'Saanvi', 'Anushka', 'Rhea', 'Pooja', 'Neha', 'Sneha', 'Tanvi', 'Kavya', 'Priya', 'Meera', 'Ritu', 'Bhavin', 'Ketan', 'Pranav', 'Deepak', 'Manish', 'Nilesh', 'Rajesh', 'Suresh', 'Vikram', 'Alok'];
        $lastNames = ['Sharma', 'Verma', 'Patel', 'Mehta', 'Shah', 'Deshmukh', 'Joshi', 'Kulkarni', 'Iyer', 'Menon', 'Nair', 'Reddy', 'Rao', 'Choudhury', 'Banerjee', 'Chatterjee', 'Gupta', 'Agarwal', 'Mittal', 'Singhania', 'Bansal', 'Kapoor', 'Khanna', 'Malhotra', 'Bhatia', 'Prajapati', 'Panchal', 'Doshi', 'Chauhan', 'Thakur'];
        $indianCompanies = [
            'Reliance Retail Ventures', 'Tata Consultancy Services', 'Infosys Digital BPM', 'Wipro Technologies',
            'Larsen & Toubro Infotech', 'Tech Mahindra Global', 'HCL Enterprise Solutions', 'Adani Logistics & Ports',
            'Bajaj Auto Components', 'Mahindra Automotive Tech', 'Godrej Consumer Products', 'Sun Pharma Laboratories',
            'Cipla Health Tech', 'Dr Reddys Bioceuticals', 'Apollo Hospital Network', 'Max Healthcare Infrastructure',
            'Havells India Electric', 'Voltas Air Systems', 'Asian Paints Industrial', 'Pidilite Chemical Specialities',
            'Titan Luxury Brands', 'Marico Consumer Goods', 'Dabur India Natural', 'UltraTech Cement Works',
            'JSW Steel Infrastructure', 'Tata Steel Downstream', 'Bharat Forge Engineering', 'Thermax Environmental',
            'Mindtree Digital Cloud', 'Zensar Technologies', 'Persistent Systems Tech', 'L&T Technology Services',
            'KPIT Technologies Pune', 'Happiest Minds Digital', 'Tata Motors Commercial', 'Hero MotoCorp Solutions'
        ];
        $jobTitles = ['Chief Technology Officer', 'VP of Procurement', 'Head of Supply Chain', 'Managing Director', 'Chief Financial Officer', 'Director of Operations', 'General Manager IT', 'Commercial VP', 'Head of Human Resources', 'Plant Head', 'Enterprise Architect', 'Chief Information Officer'];
        $indianCities = [
            ['city' => 'Mumbai', 'state' => 'Maharashtra', 'pin' => '400001'],
            ['city' => 'Bengaluru', 'state' => 'Karnataka', 'pin' => '560001'],
            ['city' => 'Delhi NCR', 'state' => 'Delhi', 'pin' => '110001'],
            ['city' => 'Hyderabad', 'state' => 'Telangana', 'pin' => '500081'],
            ['city' => 'Pune', 'state' => 'Maharashtra', 'pin' => '411001'],
            ['city' => 'Chennai', 'state' => 'Tamil Nadu', 'pin' => '600001'],
            ['city' => 'Ahmedabad', 'state' => 'Gujarat', 'pin' => '380001'],
            ['city' => 'Kolkata', 'state' => 'West Bengal', 'pin' => '700001'],
            ['city' => 'Jaipur', 'state' => 'Rajasthan', 'pin' => '302001'],
            ['city' => 'Surat', 'state' => 'Gujarat', 'pin' => '395001'],
        ];

        // 5. Seed 200 Contacts
        $existingCustomers = Customer::where('company_id', $companyId)->get();
        $contactIds = [];

        $contactCount = CrmContact::where('company_id', $companyId)->count();
        $contactsToCreate = max(0, 200 - $contactCount);

        for ($i = 0; $i < $contactsToCreate; $i++) {
            $fName = $firstNames[array_rand($firstNames)];
            $lName = $lastNames[array_rand($lastNames)];
            $compName = $indianCompanies[array_rand($indianCompanies)];
            $cityObj = $indianCities[array_rand($indianCities)];
            $title = $jobTitles[array_rand($jobTitles)];
            $email = strtolower("{$fName}.{$lName}" . rand(10, 999) . "@" . strtolower(preg_replace('/[^a-zA-Z]/', '', explode(' ', $compName)[0])) . ".com");
            $phone = '+91 ' . rand(70, 99) . rand(1000, 9999) . ' ' . rand(1000, 9999);
            $whatsapp = '+91 ' . rand(70, 99) . rand(1000, 9999) . ' ' . rand(1000, 9999);
            $linkedCustomer = ($existingCustomers->isNotEmpty() && $i < $existingCustomers->count()) ? $existingCustomers[$i] : null;

            $contact = CrmContact::create([
                'company_id' => $companyId,
                'customer_id' => $linkedCustomer?->id,
                'first_name' => $fName,
                'last_name' => $lName,
                'company_name' => $compName,
                'job_title' => $title,
                'email' => $email,
                'secondary_email' => 'support.' . strtolower($lName) . '@falconerp.com',
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'website' => 'https://www.' . strtolower(explode(' ', $compName)[0]) . '.com',
                'address' => rand(10, 200) . ', Phase ' . rand(1, 4) . ', Industrial Park, ' . $cityObj['city'],
                'city' => $cityObj['city'],
                'state' => $cityObj['state'],
                'country' => 'India',
                'postal_code' => $cityObj['pin'],
                'contact_type' => 'business',
                'lead_source_id' => $sourceIds[array_rand($sourceIds)],
                'owner_id' => $userId,
                'status' => rand(1, 10) > 1 ? 'active' : 'inactive',
                'tags' => ['Enterprise', 'Tech Lead', 'B2B Client'],
                'notes' => 'High decision-making authority in technology procurement.',
                'last_contacted_at' => Carbon::now()->subDays(rand(1, 60)),
                'created_at' => Carbon::now()->subDays(rand(1, 180)),
            ]);
            $contactIds[] = $contact->id;
        }

        if (empty($contactIds)) {
            $contactIds = CrmContact::where('company_id', $companyId)->pluck('id')->toArray();
        }

        // 6. Seed 100 Leads with authoritative LeadScoringService calculations
        $leadScoringService = app(LeadScoringService::class);
        $industries = ['Information Technology', 'Manufacturing', 'Healthcare', 'Banking & Finance', 'Automotive', 'Retail & FMCG', 'Construction & Infra', 'Logistics'];
        $companySizes = ['10-50 employees', '51-200 employees', '201-500 employees', '500-1000 employees', '1000+ employees'];
        $statuses = ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'lost'];

        $leadCount = CrmLead::where('company_id', $companyId)->count();
        $leadsToCreate = max(0, 100 - $leadCount);
        $leadIds = [];

        for ($i = 0; $i < $leadsToCreate; $i++) {
            $fName = $firstNames[array_rand($firstNames)];
            $lName = $lastNames[array_rand($lastNames)];
            $compName = $indianCompanies[array_rand($indianCompanies)];
            $email = strtolower("{$fName}.{$lName}" . rand(10, 999) . "@" . strtolower(preg_replace('/[^a-zA-Z]/', '', explode(' ', $compName)[0])) . ".com");
            $phone = '+91 98' . rand(10000000, 99999999);
            $budget = rand(5, 50) * 50000; // ₹2.5L to ₹25L
            $status = $statuses[array_rand($statuses)];

            $lead = CrmLead::create([
                'company_id' => $companyId,
                'name' => "{$fName} {$lName}",
                'company_name' => $compName,
                'email' => $email,
                'phone' => $phone,
                'job_title' => $jobTitles[array_rand($jobTitles)],
                'website' => 'https://www.' . strtolower(explode(' ', $compName)[0]) . '.com',
                'lead_source_id' => $sourceIds[array_rand($sourceIds)],
                'campaign_id' => !empty($campaignIds) && rand(0, 1) ? $campaignIds[array_rand($campaignIds)] : null,
                'owner_id' => $userId,
                'industry' => $industries[array_rand($industries)],
                'company_size' => $companySizes[array_rand($companySizes)],
                'budget' => $budget,
                'expected_value' => $budget * 1.2,
                'expected_close_date' => Carbon::now()->addDays(rand(10, 90))->toDateString(),
                'score' => 0,
                'score_category' => 'cold',
                'status' => $status,
                'notes' => 'Inbound request for Falcon ERP multi-company manufacturing and supply chain suite.',
                'tags' => ['Inbound', 'High Budget', 'ERP Upgrade'],
                'created_at' => Carbon::now()->subDays(rand(1, 120)),
            ]);

            // Calculate authoritative score via LeadScoringService
            $leadScoringService->calculateScore($lead);
            $leadIds[] = $lead->id;
        }

        if (empty($leadIds)) {
            $leadIds = CrmLead::where('company_id', $companyId)->pluck('id')->toArray();
        }

        // 7. Seed 50 Deals
        $defaultStages = CrmPipelineStage::where('pipeline_id', $defaultPipeline?->id ?? 1)->get();
        if ($defaultStages->isEmpty()) {
            $defaultStages = CrmPipelineStage::take(6)->get();
        }

        $dealNames = [
            'Enterprise ERP Global Rollout', 'Multi-Warehouse Cloud Sync', 'Automated Payroll & Attendance Suite',
            'Supply Chain Portal & Vendor Management', 'B2B POS & Cash Register System', 'Procurement Analytics Dashboard',
            'Financial Compliance & GST Filing Engine', 'Omnichannel Inventory Automation', 'Asset Tracking & Barcode System',
            'AI Document & Invoice Processing Engine', 'Customer 360 & Loyalty Workflow', 'ERP Dedicated Private Cloud Migration'
        ];

        $existingProducts = Product::where('company_id', $companyId)->take(10)->get();
        $dealCount = CrmDeal::where('company_id', $companyId)->count();
        $dealsToCreate = max(0, 50 - $dealCount);
        $dealIds = [];

        for ($i = 0; $i < $dealsToCreate; $i++) {
            $stage = $defaultStages[array_rand($defaultStages->toArray())];
            $currency = rand(1, 5) === 1 ? 'USD' : 'INR';
            $val = $currency === 'USD' ? rand(5000, 75000) : (rand(5, 50) * 100000); // $5k-$75k or ₹5L-₹50L
            $prob = $stage->probability;
            $expRev = round(($val * $prob) / 100, 2);

            $status = $stage->is_won ? 'won' : ($stage->is_lost ? 'lost' : 'open');
            $wonAt = $status === 'won' ? Carbon::now()->subDays(rand(1, 45)) : null;
            $lostAt = $status === 'lost' ? Carbon::now()->subDays(rand(1, 45)) : null;

            $deal = CrmDeal::create([
                'company_id' => $companyId,
                'customer_id' => $existingCustomers->isNotEmpty() ? $existingCustomers->random()->id : null,
                'contact_id' => !empty($contactIds) ? $contactIds[array_rand($contactIds)] : null,
                'lead_id' => !empty($leadIds) ? $leadIds[array_rand($leadIds)] : null,
                'pipeline_id' => $defaultPipeline?->id ?? 1,
                'stage_id' => $stage->id,
                'name' => $dealNames[array_rand($dealNames)] . ' - ' . $firstNames[array_rand($firstNames)],
                'value' => $val,
                'currency' => $currency,
                'probability' => $prob,
                'expected_revenue' => $expRev,
                'expected_close_date' => Carbon::now()->addDays(rand(10, 60))->toDateString(),
                'owner_id' => $userId,
                'status' => $status,
                'won_at' => $wonAt,
                'lost_at' => $lostAt,
                'notes' => 'Contract approved by procurement committee.',
                'created_at' => Carbon::now()->subDays(rand(1, 90)),
            ]);
            $dealIds[] = $deal->id;

            // Deal Items
            $itemCount = rand(1, 3);
            for ($k = 0; $k < $itemCount; $k++) {
                $p = $existingProducts->isNotEmpty() ? $existingProducts->random() : null;
                $qty = rand(1, 10);
                $uPrice = $p ? (float) $p->selling_price : ($val / $itemCount);
                $taxAmt = round(($qty * $uPrice * 0.18), 2);
                $lineTot = round(($qty * $uPrice) + $taxAmt, 2);

                CrmDealItem::create([
                    'deal_id' => $deal->id,
                    'product_id' => $p?->id,
                    'product_name' => $p ? $p->name : 'Falcon ERP Enterprise Software License',
                    'quantity' => $qty,
                    'unit_price' => $uPrice,
                    'discount' => 0,
                    'tax_rate' => 18.00,
                    'tax_amount' => $taxAmt,
                    'total' => $lineTot,
                ]);
            }

            // Stage History
            CrmDealStageHistory::create([
                'deal_id' => $deal->id,
                'from_stage_id' => null,
                'to_stage_id' => $stage->id,
                'changed_by' => $userId,
                'notes' => "Advanced to {$stage->name}",
            ]);
        }

        if (empty($dealIds)) {
            $dealIds = CrmDeal::where('company_id', $companyId)->pluck('id')->toArray();
        }

        // 8. Seed 100 Feedback Records
        $feedbackTexts = [
            'Exceptional implementation speed. The inventory module and POS are working smoothly across all 12 retail branches.',
            'Support team resolved our custom invoice format request within 2 hours. Very satisfied.',
            'The dashboard analytics and live cashflow updates have significantly reduced month-end closing time.',
            'Slight delay during initial database migration, but the engineering team handled it professionally.',
            'Outstanding CRM and sales pipeline workflow. Our sales reps love the Kanban board view.',
            'Mobile responsiveness is flawless. Executives can approve purchase orders on the go.',
            'Need more training videos for the new payroll calculation rules, otherwise excellent experience.',
            'Very good software with clean UI. Dark navy palette looks corporate and modern.',
        ];

        $categories = ['product', 'service', 'support', 'sales', 'delivery', 'billing'];
        $feedbackStatuses = ['new', 'reviewed', 'assigned', 'resolved', 'closed'];

        $fbCount = CrmFeedback::where('company_id', $companyId)->count();
        $fbToCreate = max(0, 100 - $fbCount);

        for ($i = 0; $i < $fbToCreate; $i++) {
            $rating = rand(3, 5); // mostly positive
            if (rand(1, 10) === 1) $rating = rand(1, 2); // few negative
            $st = $feedbackStatuses[array_rand($feedbackStatuses)];

            CrmFeedback::create([
                'company_id' => $companyId,
                'customer_id' => $existingCustomers->isNotEmpty() ? $existingCustomers->random()->id : null,
                'contact_id' => !empty($contactIds) ? $contactIds[array_rand($contactIds)] : null,
                'deal_id' => !empty($dealIds) ? $dealIds[array_rand($dealIds)] : null,
                'rating' => $rating,
                'category' => $categories[array_rand($categories)],
                'feedback_text' => $feedbackTexts[array_rand($feedbackTexts)],
                'status' => $st,
                'assigned_to' => $userId,
                'resolution' => in_array($st, ['resolved', 'closed']) ? 'Customer issue addressed and customer confirmed satisfaction.' : null,
                'resolved_at' => in_array($st, ['resolved', 'closed']) ? Carbon::now()->subDays(rand(1, 20)) : null,
                'created_at' => Carbon::now()->subDays(rand(1, 90)),
            ]);
        }

        // 9. Seed 500 Activities, 100 Tasks, 50 Meetings
        $actTypes = ['call', 'email', 'meeting', 'task', 'note', 'sms', 'whatsapp'];
        $actSubjects = [
            'Quarterly Software Review', 'Contract Renewal Terms Discussion', 'Product Demo & Architecture Q&A',
            'Security Assessment Verification', 'Invoice Discrepancy Resolution', 'Executive Intro Call',
            'Follow-up on Proposed Scope of Work', 'Sent Product Datasheet & Commercial Proposal',
            'Discussed SLA and Annual Maintenance Contract'
        ];

        $actCount = CrmActivity::where('company_id', $companyId)->count();
        $actToCreate = max(0, 500 - $actCount);

        for ($i = 0; $i < $actToCreate; $i++) {
            $t = $actTypes[array_rand($actTypes)];
            CrmActivity::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'type' => $t,
                'subject' => $actSubjects[array_rand($actSubjects)],
                'description' => "Detailed discussion regarding enterprise deliverables and timelines.",
                'contact_id' => !empty($contactIds) ? $contactIds[array_rand($contactIds)] : null,
                'lead_id' => !empty($leadIds) ? $leadIds[array_rand($leadIds)] : null,
                'deal_id' => !empty($dealIds) ? $dealIds[array_rand($dealIds)] : null,
                'customer_id' => $existingCustomers->isNotEmpty() ? $existingCustomers->random()->id : null,
                'due_at' => Carbon::now()->addDays(rand(-30, 30)),
                'completed_at' => Carbon::now()->subDays(rand(1, 30)),
                'status' => rand(1, 3) === 1 ? 'pending' : 'completed',
                'created_at' => Carbon::now()->subDays(rand(1, 100)),
            ]);
        }

        // 100 Tasks
        $taskCount = CrmTask::where('company_id', $companyId)->count();
        $tasksToCreate = max(0, 100 - $taskCount);
        $taskTitles = [
            'Send updated pricing proposal', 'Conduct live integration demonstration',
            'Prepare compliance certificate', 'Follow up on payment remittance',
            'Review vendor SLA agreement', 'Confirm delivery schedule for server rack',
            'Coordinate with legal on Master Services Agreement', 'Schedule executive discovery meeting'
        ];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $taskStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];

        for ($i = 0; $i < $tasksToCreate; $i++) {
            CrmTask::create([
                'company_id' => $companyId,
                'title' => $taskTitles[array_rand($taskTitles)],
                'description' => 'Ensure all stakeholder requirements are fulfilled before deadline.',
                'assigned_to' => $userId,
                'created_by' => $userId,
                'contact_id' => !empty($contactIds) ? $contactIds[array_rand($contactIds)] : null,
                'lead_id' => !empty($leadIds) ? $leadIds[array_rand($leadIds)] : null,
                'deal_id' => !empty($dealIds) ? $dealIds[array_rand($dealIds)] : null,
                'priority' => $priorities[array_rand($priorities)],
                'due_date' => Carbon::now()->addDays(rand(-10, 30))->toDateString(),
                'status' => $taskStatuses[array_rand($taskStatuses)],
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
            ]);
        }

        // 50 Meetings
        $meetingCount = CrmMeeting::where('company_id', $companyId)->count();
        $meetingsToCreate = max(0, 50 - $meetingCount);
        $meetingTitles = [
            'Discovery & Capability Demonstration', 'Annual Enterprise Renewal Alignment',
            'Solution Architecture Deep Dive', 'Security & Compliance Review',
            'Post-Implementation Feedback Session', 'Boardroom Executive Presentation'
        ];

        for ($i = 0; $i < $meetingsToCreate; $i++) {
            CrmMeeting::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'contact_id' => !empty($contactIds) ? $contactIds[array_rand($contactIds)] : null,
                'lead_id' => !empty($leadIds) ? $leadIds[array_rand($leadIds)] : null,
                'deal_id' => !empty($dealIds) ? $dealIds[array_rand($dealIds)] : null,
                'title' => $meetingTitles[array_rand($meetingTitles)],
                'description' => 'Strategic discussion covering timeline, team resources, and software deliverables.',
                'meeting_date' => Carbon::now()->addDays(rand(-20, 20))->toDateString(),
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
                'location' => rand(0, 1) ? 'Falcon Tech HQ, Mumbai' : 'Google Meet / Zoom Virtual',
                'meeting_link' => 'https://meet.google.com/flc-' . substr(md5(rand()), 0, 7),
                'participants' => ['sales@falconerp.com', 'client@enterprise.in'],
                'status' => rand(0, 1) ? 'completed' : 'scheduled',
                'created_at' => Carbon::now()->subDays(rand(1, 40)),
            ]);
        }

        // 10. Customer Segments
        $segmentService = app(CustomerSegmentationService::class);
        $segmentService->evaluateSegments($companyId);

        $this->command->info('Enterprise CRM sample data seeded successfully!');
    }
}
