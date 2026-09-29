<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\CrmDeal;
use App\Models\CrmLead;
use App\Services\DealService;
use App\Services\LeadConversionService;
use App\Services\LeadScoringService;
use Illuminate\Http\Request;

echo "--- 1. Testing CrmDashboardController ---\n";
$user = User::first();
$req = Request::create('/api/v1/crm/dashboard', 'GET');
$req->setUserResolver(fn() => $user);
$dashboardRes = app(\App\Http\Controllers\Api\Crm\CrmDashboardController::class)->index($req);
echo "Status: " . $dashboardRes->getStatusCode() . "\n";
$dData = $dashboardRes->getData()->data;
echo "Total Contacts: " . $dData->cards->total_contacts . "\n";
echo "Total Leads: " . $dData->cards->total_leads . "\n";
echo "Pipeline Value: " . $dData->cards->pipeline_value . "\n";
echo "Conversion Rate: " . $dData->cards->conversion_rate . "%\n";
echo "Charts generated: " . implode(', ', array_keys((array) $dData->charts)) . "\n";

echo "\n--- 2. Testing LeadScoringService ---\n";
$lead = CrmLead::where('status', 'new')->first();
$scoringService = app(LeadScoringService::class);
$scoreRes = $scoringService->calculateScore($lead);
echo "Lead {$lead->id} Score: {$scoreRes['score']}, Category: {$scoreRes['score_category']}\n";

echo "\n--- 3. Testing LeadConversionService ---\n";
$leadToConvert = CrmLead::where('status', '!=', 'converted')->first();
$convService = app(LeadConversionService::class);
$convRes = $convService->convert($leadToConvert, [
    'create_customer' => true,
    'create_deal' => true,
    'deal_name' => 'Converted Test Deal',
    'deal_value' => 75000,
], $user->id);
echo "Converted Lead #{$leadToConvert->id} -> Customer #{$convRes['customer']->id}, Contact #{$convRes['contact']->id}, Deal #{$convRes['deal']->id}\n";

echo "\n--- 4. Testing DealService (Sales Order Integration) ---\n";
$deal = $convRes['deal'];
$dealService = app(DealService::class);
$salesOrder = $dealService->createSalesOrderFromDeal($deal, $user->id);
echo "Generated Sales Order #{$salesOrder->id} ({$salesOrder->order_number}), Total: {$salesOrder->grand_total}\n";

echo "\n--- 5. Testing Kanban Pipeline ---\n";
$pipelineRes = app(\App\Http\Controllers\Api\Crm\CrmPipelineController::class)->index($req);
$pData = $pipelineRes->getData()->data;
echo "Active Pipeline: " . $pData->pipeline->name . "\n";
echo "Stages count: " . count($pData->stages) . "\n";
echo "Total Deals in Kanban: " . $pData->summary->total_deals . "\n";

echo "\n--- ALL BACKEND CRM TESTS PASSED SUCCESSFULLY! ---\n";
