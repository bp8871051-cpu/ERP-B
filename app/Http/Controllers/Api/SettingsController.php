<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IntegrationService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;
    protected IntegrationService $integrationService;

    public function __construct(SettingsService $settingsService, IntegrationService $integrationService)
    {
        $this->settingsService = $settingsService;
        $this->integrationService = $integrationService;
    }

    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    public function index(): JsonResponse
    {
        $data = $this->settingsService->getAllSettings();
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $updated = $this->settingsService->updateGroupSettings('general', $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $updated, 'message' => 'General company settings updated.']);
    }

    public function updateSecurity(Request $request): JsonResponse
    {
        $updated = $this->settingsService->updateGroupSettings('security', $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $updated, 'message' => 'Security settings updated.']);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $updated = $this->settingsService->updateGroupSettings('notifications', $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $updated, 'message' => 'Notification triggers and SMTP settings saved.']);
    }

    public function testSmtp(Request $request): JsonResponse
    {
        $result = $this->settingsService->testSmtpConnection($request->all());
        return response()->json(['status' => $result['success'] ? 'success' : 'error', 'message' => $result['message']]);
    }

    // --- Integrations ---
    public function integrations(Request $request): JsonResponse
    {
        $integrations = $this->integrationService->getIntegrations($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $integrations]);
    }

    // --- API Keys ---
    public function apiKeys(Request $request): JsonResponse
    {
        $keys = $this->integrationService->getApiKeys($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $keys]);
    }

    public function storeApiKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $res = $this->integrationService->generateApiKey($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json([
            'status' => 'success',
            'data' => $res['api_key'],
            'plain_secret' => $res['plain_secret'],
            'message' => 'API Key generated. Copy secret now as it will not be shown again.',
        ]);
    }

    public function revokeApiKey(Request $request, int $id): JsonResponse
    {
        $this->integrationService->revokeApiKey($id, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'API key revoked.']);
    }

    // --- Webhooks ---
    public function webhooks(Request $request): JsonResponse
    {
        $webhooks = $this->integrationService->getWebhooks($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $webhooks]);
    }

    public function storeWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'event' => 'required|string',
            'retry_policy' => 'nullable|string',
        ]);

        $webhook = $this->integrationService->createWebhook($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $webhook, 'message' => 'Webhook registered.']);
    }

    public function deleteWebhook(Request $request, int $id): JsonResponse
    {
        $this->integrationService->deleteWebhook($id, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Webhook deleted.']);
    }

    // --- Sessions ---
    public function sessions(Request $request): JsonResponse
    {
        $sessions = $this->integrationService->getActiveSessions($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $sessions]);
    }
}
