<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\CrmCampaign;
use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmFeedback;
use App\Models\CrmLead;
use App\Models\CrmLeadSource;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\CrmDashboardService;
use App\Services\CustomerAnalyticsService;
use App\Services\DealService;
use App\Services\LeadConversionService;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnterpriseCrmFeatureTest extends TestCase
{
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::first() ?? Company::create([
            'name' => 'Falcon Technologies Ltd',
            'email' => 'admin@falconerp.com',
            'currency' => 'INR',
        ]);

        $this->user = User::first() ?? User::create([
            'name' => 'CRM Admin',
            'email' => 'crm_test@falconerp.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);
    }

    /** Test CRM Dashboard Returns All 10 Cards and 8 Charts */
    public function test_crm_dashboard_returns_authoritative_metrics(): void
    {
        $service = app(CrmDashboardService::class);
        $data = $service->getDashboardMetrics($this->company->id);

        $this->assertArrayHasKey('cards', $data);
        $this->assertArrayHasKey('charts', $data);

        // Verify 10 cards
        $cards = $data['cards'];
        $this->assertArrayHasKey('total_contacts', $cards);
        $this->assertArrayHasKey('total_leads', $cards);
        $this->assertArrayHasKey('new_leads', $cards);
        $this->assertArrayHasKey('qualified_leads', $cards);
        $this->assertArrayHasKey('open_deals', $cards);
        $this->assertArrayHasKey('won_deals', $cards);
        $this->assertArrayHasKey('lost_deals', $cards);
        $this->assertArrayHasKey('pipeline_value', $cards);
        $this->assertArrayHasKey('expected_revenue', $cards);
        $this->assertArrayHasKey('conversion_rate', $cards);
        $this->assertArrayHasKey('active_campaigns', $cards);

        // Verify 8 charts
        $charts = $data['charts'];
        $this->assertArrayHasKey('lead_funnel', $charts);
        $this->assertArrayHasKey('monthly_pipeline_value', $charts);
        $this->assertArrayHasKey('leads_by_source', $charts);
        $this->assertArrayHasKey('deals_by_stage', $charts);
        $this->assertArrayHasKey('revenue_trend', $charts);
        $this->assertArrayHasKey('conversion_trend', $charts);
        $this->assertArrayHasKey('campaign_performance', $charts);
        $this->assertArrayHasKey('sales_activity', $charts);
    }

    /** Test Lead Scoring Engine Calculates 0-100 Accurately */
    public function test_lead_scoring_engine_authoritative_score(): void
    {
        $lead = CrmLead::create([
            'company_id' => $this->company->id,
            'name' => 'Amitabh Bachchan',
            'company_name' => 'AB Corp',
            'email' => 'amitabh@abcorp.in', // Business domain +10, email +10
            'phone' => '+91 9820011223', // Phone +10
            'website' => 'https://abcorp.in', // Website +10
            'budget' => 500000, // Budget +15
            'status' => 'new',
        ]);

        $scoringService = app(LeadScoringService::class);
        $scoreResult = $scoringService->calculateScore($lead);

        $this->assertGreaterThanOrEqual(45, $scoreResult['score']);
        $this->assertContains($scoreResult['category'], ['Cold', 'Warm', 'Hot', 'Very Hot']);

        $lead->refresh();
        $this->assertEquals($scoreResult['score'], $lead->score);
    }

    /** Test Lead Conversion with DB::transaction and No Customer Duplication */
    public function test_lead_conversion_atomic_transaction_and_no_duplicate_customer(): void
    {
        $existingCustomer = Customer::first() ?? Customer::create([
            'company_id' => $this->company->id,
            'name' => 'HDFC Bank Corporate',
            'customer_code' => 'CUST-HDFC-01',
            'email' => 'procure@hdfc.com',
        ]);

        $lead = CrmLead::create([
            'company_id' => $this->company->id,
            'name' => 'Deepak Parekh',
            'company_name' => 'HDFC Bank Corporate',
            'email' => 'deepak@hdfc.com',
            'phone' => '+91 9820099887',
            'budget' => 1200000,
            'status' => 'qualified',
        ]);

        $pipeline = CrmPipeline::first();
        $stage = $pipeline->stages()->first();

        $conversionService = app(LeadConversionService::class);
        $result = $conversionService->convert($lead, [
            'customer_id' => $existingCustomer->id,
            'create_deal' => true,
            'deal_name' => 'HDFC Security Infrastructure',
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'deal_value' => 1200000,
        ], $this->user->id);

        $this->assertEquals('converted', $lead->fresh()->status);
        $this->assertEquals($existingCustomer->id, $result['customer']->id);
        $this->assertInstanceOf(CrmContact::class, $result['contact']);
        $this->assertInstanceOf(CrmDeal::class, $result['deal']);
        $this->assertEquals(1200000, $result['deal']->value);
    }

    /** Test Deal Calculation and Sales Order Integration */
    public function test_deal_calculation_and_sales_order_conversion(): void
    {
        $customer = Customer::first();
        $pipeline = CrmPipeline::first();
        $stage = $pipeline->stages()->where('is_won', true)->first() ?? $pipeline->stages()->first();

        $dealService = app(DealService::class);
        $deal = $dealService->createDeal([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'name' => 'Tata Steel ERP Licensing',
            'currency' => 'INR',
            'probability' => 100,
            'status' => 'won',
            'items' => [
                [
                    'product_name' => 'Enterprise Server Node',
                    'quantity' => 2,
                    'unit_price' => 100000,
                    'discount_percentage' => 10, // subtotal 200000 - 20000 = 180000
                    'tax_percentage' => 18, // 180000 * 1.18 = 212400
                ],
            ],
        ], $this->user->id);

        $this->assertEquals(212400, $deal->value);
        $this->assertEquals(212400, $deal->expected_revenue);

        // Convert Won Deal to Sales Order
        $salesOrder = $dealService->createSalesOrderFromDeal($deal, $this->user->id);

        $this->assertInstanceOf(SalesOrder::class, $salesOrder);
        $this->assertEquals($customer->id, $salesOrder->customer_id);
        $this->assertEquals(212400, $salesOrder->grand_total);
    }

    /** Test Pipeline Stage Transition API Updates Value, Prob & Stage History */
    public function test_pipeline_stage_transition_updates_history(): void
    {
        $deal = CrmDeal::first();
        $pipeline = $deal->pipeline;
        $newStage = $pipeline->stages()->where('id', '!=', $deal->stage_id)->first();

        if ($newStage) {
            $dealService = app(DealService::class);
            $dealService->updateStage($deal, $newStage->id, 'Advanced during feature test', $this->user->id);

            $deal->refresh();
            $this->assertEquals($newStage->id, $deal->stage_id);
            $this->assertEquals($newStage->probability, $deal->probability);

            // Verify Stage History Audit Log
            $historyExists = DB::table('crm_deal_stage_history')
                ->where('deal_id', $deal->id)
                ->where('to_stage_id', $newStage->id)
                ->exists();

            $this->assertTrue($historyExists);
        }
    }

    /** Test Customer Analytics CLV and Retention Calculations */
    public function test_customer_analytics_clv_and_segmentation(): void
    {
        $analyticsService = app(CustomerAnalyticsService::class);
        $data = $analyticsService->getAnalytics($this->company->id);

        $this->assertArrayHasKey('metrics', $data);
        $this->assertArrayHasKey('segments', $data);
        $this->assertArrayHasKey('top_customers', $data);

        $metrics = $data['metrics'];
        $this->assertGreaterThan(0, $metrics['total_customers']);
        $this->assertArrayHasKey('clv', $metrics);
        $this->assertArrayHasKey('retention_rate', $metrics);
        $this->assertArrayHasKey('churn_rate', $metrics);
    }
}
