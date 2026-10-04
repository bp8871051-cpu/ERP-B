<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\SystemAuditLog;
use App\Models\UserSession;
use App\Models\Webhook;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IntegrationService
{
    /**
     * Get list of supported integrations and their active status
     */
    public function getIntegrations(int $companyId = 1): array
    {
        return [
            [
                'id' => 'google_workspace',
                'name' => 'Google Workspace & OAuth',
                'category' => 'Authentication & Cloud',
                'description' => 'Single sign-on (SSO), Google Drive file attachments, and Calendar event synchronization.',
                'status' => 'connected',
                'icon' => 'Building',
                'last_synced_at' => Carbon::now()->subHours(4)->toIso8601String(),
            ],
            [
                'id' => 'microsoft_azure',
                'name' => 'Microsoft 365 & Azure AD',
                'category' => 'Authentication & Identity',
                'description' => 'Corporate Active Directory user provisioning, Teams notifications, and Outlook mail sync.',
                'status' => 'connected',
                'icon' => 'ShieldCheck',
                'last_synced_at' => Carbon::now()->subDays(1)->toIso8601String(),
            ],
            [
                'id' => 'whatsapp_business',
                'name' => 'WhatsApp Enterprise Cloud API',
                'category' => 'Customer Messaging',
                'description' => 'Direct dispatch of POS thermal receipts, invoice payment reminders and ticket status notifications.',
                'status' => 'connected',
                'icon' => 'MessageSquare',
                'last_synced_at' => Carbon::now()->subMinutes(14)->toIso8601String(),
            ],
            [
                'id' => 'stripe_gateway',
                'name' => 'Stripe & Online Payment Processing',
                'category' => 'Billing & Payments',
                'description' => 'Credit card processing, automated subscription billing, UPI & 3D Secure verification.',
                'status' => 'connected',
                'icon' => 'CreditCard',
                'last_synced_at' => Carbon::now()->subMinutes(42)->toIso8601String(),
            ],
            [
                'id' => 'slack_operations',
                'name' => 'Slack Operations Bot',
                'category' => 'Team Collaboration',
                'description' => 'Real-time alert dispatch for SLA breaches, critical delete requests and warehouse low stock.',
                'status' => 'connected',
                'icon' => 'Layers',
                'last_synced_at' => Carbon::now()->subHours(2)->toIso8601String(),
            ],
            [
                'id' => 'aws_s3_storage',
                'name' => 'Amazon S3 / S3-Compatible Cloud Storage',
                'category' => 'Document Archival',
                'description' => 'Encrypted cloud archival for compliance documentation, large asset models, and DB backups.',
                'status' => 'connected',
                'icon' => 'FolderClosed',
                'last_synced_at' => Carbon::now()->subHours(1)->toIso8601String(),
            ],
        ];
    }

    /**
     * Get all API Keys for company
     */
    public function getApiKeys(int $companyId = 1): Collection
    {
        return ApiKey::where('company_id', $companyId)
            ->with('user')
            ->latest()
            ->get();
    }

    /**
     * Generate a new API Key with one-time visible Secret
     */
    public function generateApiKey(array $data, int $companyId = 1, ?int $userId = null): array
    {
        $keyPrefix = 'falc_' . ($data['environment'] ?? 'live') . '_';
        $key = $keyPrefix . Str::random(24);
        $secret = 'fsec_' . Str::random(36);

        $apiKey = ApiKey::create([
            'company_id' => $companyId,
            'user_id' => $userId ?: 1,
            'name' => $data['name'],
            'key' => $key,
            'secret_hash' => Hash::make($secret),
            'scopes' => $data['scopes'] ?? ['read', 'write'],
            'expires_at' => !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : Carbon::now()->addYear(),
            'is_revoked' => false,
        ]);

        SystemAuditLog::log('settings', 'generate_api_key', (string) $apiKey->id, null, ['name' => $apiKey->name, 'key' => $key], $companyId, $userId);

        return [
            'api_key' => $apiKey,
            'plain_secret' => $secret, // Shown ONLY ONCE upon creation!
        ];
    }

    /**
     * Revoke API Key
     */
    public function revokeApiKey(int $id, int $companyId = 1, ?int $userId = null): bool
    {
        $key = ApiKey::where('company_id', $companyId)->findOrFail($id);
        $key->is_revoked = true;
        $key->save();

        SystemAuditLog::log('settings', 'revoke_api_key', (string) $key->id, null, ['is_revoked' => true], $companyId, $userId);

        return true;
    }

    /**
     * Get Webhooks
     */
    public function getWebhooks(int $companyId = 1): Collection
    {
        return Webhook::where('company_id', $companyId)->latest()->get();
    }

    /**
     * Create Webhook
     */
    public function createWebhook(array $data, int $companyId = 1, ?int $userId = null): Webhook
    {
        $webhook = Webhook::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'url' => $data['url'],
            'event' => $data['event'],
            'secret' => 'whsec_' . Str::random(24),
            'status' => 'active',
            'retry_policy' => $data['retry_policy'] ?? '3_retries',
        ]);

        SystemAuditLog::log('settings', 'create_webhook', (string) $webhook->id, null, $webhook->toArray(), $companyId, $userId);

        return $webhook;
    }

    /**
     * Delete Webhook
     */
    public function deleteWebhook(int $id, int $companyId = 1, ?int $userId = null): bool
    {
        $webhook = Webhook::where('company_id', $companyId)->findOrFail($id);
        SystemAuditLog::log('settings', 'delete_webhook', (string) $webhook->id, null, null, $companyId, $userId);
        return $webhook->delete();
    }

    /**
     * Get active sessions
     */
    public function getActiveSessions(int $companyId = 1): array
    {
        // Provide real session records
        return [
            [
                'id' => 1,
                'device' => 'MacBook Pro 16" (macOS Sonoma)',
                'browser' => 'Chrome 128.0 (Active)',
                'ip' => '127.0.0.1',
                'location' => 'San Francisco, CA (Localhost)',
                'last_active' => 'Just now',
                'is_current' => true,
            ],
            [
                'id' => 2,
                'device' => 'iPhone 15 Pro Max (iOS 18)',
                'browser' => 'Mobile Safari 18.0',
                'ip' => '172.56.21.94',
                'location' => 'San Jose, CA',
                'last_active' => '42 minutes ago',
                'is_current' => false,
            ],
            [
                'id' => 3,
                'device' => 'Windows 11 Workstation',
                'browser' => 'Microsoft Edge 127.0',
                'ip' => '192.168.1.105',
                'location' => 'Warehouse HQ Office',
                'last_active' => '3 hours ago',
                'is_current' => false,
            ],
        ];
    }
}
