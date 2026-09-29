<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = [
    'crm_contacts',
    'crm_leads',
    'crm_lead_sources',
    'crm_lead_scores',
    'crm_pipelines',
    'crm_pipeline_stages',
    'crm_deals',
    'crm_deal_items',
    'crm_deal_stage_history',
    'crm_campaigns',
    'crm_campaign_audiences',
    'crm_campaign_contacts',
    'crm_campaign_events',
    'crm_feedback',
    'crm_activities',
    'crm_tasks',
    'crm_emails',
    'crm_calls',
    'crm_meetings',
    'crm_notes',
    'crm_customer_segments',
    'crm_segment_members',
    'crm_analytics_snapshots',
    'crm_audit_logs',
];

$output = [];
foreach ($tables as $t) {
    try {
        $cols = DB::select("DESCRIBE $t");
        $output[$t] = array_map(function($c) {
            return [
                'field' => $c->Field,
                'type' => $c->Type,
                'null' => $c->Null,
                'key' => $c->Key,
                'default' => $c->Default,
            ];
        }, $cols);
    } catch (\Throwable $e) {
        $output[$t] = 'ERROR: ' . $e->getMessage();
    }
}

file_put_contents(__DIR__ . '/crm_tables_schema.json', json_encode($output, JSON_PRETTY_PRINT));
echo "Successfully dumped schema of " . count($tables) . " CRM tables.\n";
