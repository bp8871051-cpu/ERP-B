<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmCampaign;
use App\Services\CampaignAudienceService;
use App\Services\CampaignService;
use App\Services\CampaignTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCampaignController extends Controller
{
    protected CampaignService $campaignService;
    protected CampaignAudienceService $audienceService;
    protected CampaignTrackingService $trackingService;

    public function __construct(
        CampaignService $campaignService,
        CampaignAudienceService $audienceService,
        CampaignTrackingService $trackingService
    ) {
        $this->campaignService = $campaignService;
        $this->audienceService = $audienceService;
        $this->trackingService = $trackingService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $paginator = $this->campaignService->list($request->all(), $companyId);

        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:email,whatsapp,sms,social_media,event,phone,other',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:draft,scheduled,running,paused,completed,cancelled',
            'target_audience' => 'nullable|array',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['owner_id'] = $validated['owner_id'] ?? $request->user()?->id ?? 1;
        $validated['status'] = $validated['status'] ?? 'draft';

        $campaign = CrmCampaign::create($validated);

        // Snapshot audience if filters supplied
        if (!empty($validated['target_audience'])) {
            $this->audienceService->snapshotAudience($campaign, $validated['target_audience']);
        }

        CrmAuditLog::log(
            'Campaign Created',
            CrmCampaign::class,
            $campaign->id,
            null,
            $campaign->toArray(),
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign created successfully',
            'data' => $campaign->fresh(['owner', 'audiences']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $campaign = CrmCampaign::where('company_id', $companyId)->findOrFail($id);

        $details = $this->campaignService->getDetails($campaign);

        return response()->json([
            'status' => 'success',
            'data' => $details,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $campaign = CrmCampaign::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:email,whatsapp,sms,social_media,event,phone,other',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'budget' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:draft,scheduled,running,paused,completed,cancelled',
            'target_audience' => 'nullable|array',
        ]);

        $campaign->update($validated);

        if (isset($validated['target_audience'])) {
            $this->audienceService->snapshotAudience($campaign, $validated['target_audience']);
        }

        $this->trackingService->recalculateRoi($campaign);

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign updated successfully',
            'data' => $campaign->fresh(['owner', 'audiences']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $campaign = CrmCampaign::where('company_id', $companyId)->findOrFail($id);

        $campaign->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign deleted successfully',
        ]);
    }

    public function launch(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $campaign = CrmCampaign::where('company_id', $companyId)->findOrFail($id);

        $launched = $this->campaignService->launch($campaign, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign launched. Emails dispatched via queue worker.',
            'data' => $launched,
        ]);
    }

    public function pause(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $campaign = CrmCampaign::where('company_id', $companyId)->findOrFail($id);

        $paused = $this->campaignService->pause($campaign, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign paused successfully',
            'data' => $paused,
        ]);
    }

    public function updateAudience(Request $request, int $id): JsonResponse
    {
        $request->validate(['filters' => 'required|array']);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $campaign = CrmCampaign::where('company_id', $companyId)->findOrFail($id);

        $count = $this->audienceService->snapshotAudience($campaign, $request->input('filters'));

        return response()->json([
            'status' => 'success',
            'message' => "Audience synchronized. {$count} contacts targeted.",
            'total_audience' => $count,
        ]);
    }
}
