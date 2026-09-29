<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmLead;
use App\Services\CrmReportService;
use App\Services\LeadConversionService;
use App\Services\LeadScoringService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmLeadController extends Controller
{
    protected LeadService $leadService;
    protected LeadScoringService $scoringService;
    protected LeadConversionService $conversionService;
    protected CrmReportService $reportService;

    public function __construct(
        LeadService $leadService,
        LeadScoringService $scoringService,
        LeadConversionService $conversionService,
        CrmReportService $reportService
    ) {
        $this->leadService = $leadService;
        $this->scoringService = $scoringService;
        $this->conversionService = $conversionService;
        $this->reportService = $reportService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $paginator = $this->leadService->list($request->all(), $companyId);

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
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'lead_source_id' => 'nullable|exists:crm_lead_sources,id',
            'campaign_id' => 'nullable|exists:crm_campaigns,id',
            'owner_id' => 'nullable|exists:users,id',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'budget' => 'nullable|numeric|min:0',
            'expected_value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'status' => 'nullable|string|in:new,contacted,qualified,unqualified,converted,lost',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['owner_id'] = $validated['owner_id'] ?? $request->user()?->id ?? 1;
        $validated['status'] = $validated['status'] ?? 'new';

        $lead = CrmLead::create($validated);

        // Authoritatively calculate initial lead score via LeadScoringService
        $this->scoringService->calculateScore($lead);

        CrmAuditLog::log(
            'Lead Created',
            CrmLead::class,
            $lead->id,
            null,
            $lead->toArray(),
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Lead created successfully',
            'data' => $lead->fresh(['contact', 'customer', 'owner', 'leadSource', 'campaign']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $details = $this->leadService->getDetails($lead);

        return response()->json([
            'status' => 'success',
            'data' => $details,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'job_title' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'lead_source_id' => 'nullable|exists:crm_lead_sources,id',
            'campaign_id' => 'nullable|exists:crm_campaigns,id',
            'owner_id' => 'nullable|exists:users,id',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'budget' => 'nullable|numeric|min:0',
            'expected_value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'status' => 'nullable|string|in:new,contacted,qualified,unqualified,converted,lost',
            'lost_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $oldValues = $lead->toArray();
        $lead->update($validated);

        // Recalculate score upon update
        $this->scoringService->calculateScore($lead);

        CrmAuditLog::log(
            'Lead Updated',
            CrmLead::class,
            $lead->id,
            $oldValues,
            $lead->toArray(),
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Lead updated successfully',
            'data' => $lead->fresh(['contact', 'customer', 'owner', 'leadSource', 'campaign', 'scoreItems']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $lead->delete();

        CrmAuditLog::log(
            'Lead Deleted',
            CrmLead::class,
            $id,
            null,
            null,
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Lead deleted successfully',
        ]);
    }

    public function convert(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $result = $this->conversionService->convert($lead, $request->all(), $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Lead converted successfully',
            'data' => $result,
        ]);
    }

    public function score(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $scoreResult = $this->scoringService->calculateScore($lead);

        return response()->json([
            'status' => 'success',
            'data' => $scoreResult,
        ]);
    }

    public function export(Request $request)
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        return $this->reportService->exportLeadsCsv($companyId);
    }
}
