<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmContact;
use App\Services\ContactService;
use App\Services\CrmReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmContactController extends Controller
{
    protected ContactService $contactService;
    protected CrmReportService $reportService;

    public function __construct(ContactService $contactService, CrmReportService $reportService)
    {
        $this->contactService = $contactService;
        $this->reportService = $reportService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $paginator = $this->contactService->list($request->all(), $companyId);

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
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'job_title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'contact_type' => 'nullable|string|in:individual,business',
            'lead_source_id' => 'nullable|exists:crm_lead_sources,id',
            'owner_id' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['owner_id'] = $validated['owner_id'] ?? $request->user()?->id ?? 1;

        $contact = CrmContact::create($validated);

        CrmAuditLog::log(
            'Contact Created',
            CrmContact::class,
            $contact->id,
            null,
            $contact->toArray(),
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Contact created successfully',
            'data' => $contact->load(['customer', 'owner', 'leadSource']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $contact = CrmContact::where('company_id', $companyId)->findOrFail($id);

        $details = $this->contactService->getDetails($contact);

        return response()->json([
            'status' => 'success',
            'data' => $details,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $contact = CrmContact::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'job_title' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'contact_type' => 'nullable|string|in:individual,business',
            'lead_source_id' => 'nullable|exists:crm_lead_sources,id',
            'owner_id' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $oldValues = $contact->toArray();
        $contact->update($validated);

        CrmAuditLog::log(
            'Contact Updated',
            CrmContact::class,
            $contact->id,
            $oldValues,
            $contact->toArray(),
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Contact updated successfully',
            'data' => $contact->load(['customer', 'owner', 'leadSource']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $contact = CrmContact::where('company_id', $companyId)->findOrFail($id);

        $contact->delete();

        CrmAuditLog::log(
            'Contact Deleted',
            CrmContact::class,
            $id,
            null,
            null,
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Contact deleted successfully',
        ]);
    }

    public function export(Request $request)
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        return $this->reportService->exportContactsCsv($companyId);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'rows' => 'required|array',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $result = $this->reportService->importContacts($request->input('rows'), $companyId, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
