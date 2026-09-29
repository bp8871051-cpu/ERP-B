<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$token = $user->createToken('http_all_test')->plainTextToken;

$endpoints = [
    '/api/dashboard',
    '/api/crm/dashboard',
    '/api/v1/crm/dashboard',
    '/api/v1/crm/contacts',
    '/api/v1/crm/leads',
    '/api/v1/crm/deals',
    '/api/v1/crm/pipelines',
    '/api/v1/crm/campaigns',
    '/api/v1/crm/feedback',
    '/api/v1/crm/activities',
    '/api/v1/crm/analytics/customers',
    '/api/hrm/dashboard',
    '/api/inventory/dashboard',
    '/api/sales/dashboard',
    '/api/finance/dashboard',
    '/api/pos/dashboard',
    '/api/procurement/dashboard',
    '/api/projects/dashboard',
    '/api/support/dashboard',
];

echo "Testing all key dashboard & CRM endpoints:\n";
$allPassed = true;

foreach ($endpoints as $endpoint) {
    $t0 = microtime(true);
    $ch = curl_init('http://127.0.0.1:8000' . $endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'X-Company-ID: 1',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $duration = round(microtime(true) - $t0, 3);
    curl_close($ch);

    $statusStr = ($httpCode === 200) ? "PASS" : "FAIL (HTTP $httpCode)";
    if ($httpCode !== 200) {
        $allPassed = false;
        echo "  [FAIL] $endpoint -> HTTP $httpCode ($duration s): " . substr($res, 0, 100) . "\n";
    } else {
        echo "  [PASS] $endpoint -> 200 OK ($duration s)\n";
    }
}

if ($allPassed) {
    echo "\n>>> ALL 19 ENDPOINTS PASSED CLEANLY! <<<\n";
} else {
    echo "\n>>> SOME ENDPOINTS FAILED! <<<\n";
}
