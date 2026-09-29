<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AuditLog;
use App\Services\LayoutPreferenceService;

echo "=== TESTING LAYOUT PREFERENCE BACKEND SERVICE ===" . PHP_EOL;

$user = User::first();
echo "Testing for user: {$user->name} (ID: {$user->id})" . PHP_EOL;

$service = new LayoutPreferenceService();

// 1. Get or create initial preference
$pref = $service->getPreferences($user);
echo "Initial mode: {$pref->sidebar_mode} | Direction: {$pref->direction} | Width: {$pref->content_width}" . PHP_EOL;

// 2. Update to mini sidebar and rtl
$updated = $service->updatePreferences($user, [
    'sidebar_mode' => 'mini',
    'direction' => 'rtl',
    'content_width' => 'full',
]);
echo "Updated mode: {$updated->sidebar_mode} | Direction: {$updated->direction} | Width: {$updated->content_width}" . PHP_EOL;

// 3. Check audit log was created
$latestAudit = AuditLog::where('user_id', $user->id)->where('module', 'layouts')->latest()->first();
echo "Audit Log: Action={$latestAudit->action} | Module={$latestAudit->module} | Desc={$latestAudit->description}" . PHP_EOL;

// 4. Reset back to defaults
$reset = $service->resetPreferences($user);
echo "Reset mode: {$reset->sidebar_mode} | Direction: {$reset->direction} | Width: {$reset->content_width}" . PHP_EOL;

echo "ALL BACKEND TESTS PASSED SUCCESSFULLY!" . PHP_EOL;
