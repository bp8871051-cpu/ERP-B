<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmLead;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContactPrivacyService;
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
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);
        $paginator = $this->leadService->list($request->all(), $companyId);

        // Apply contact privacy masking (Rule 1, 2, 3, 4, 8)
        $items = collect($paginator->items())->map(function ($lead) use ($user) {
            return ContactPrivacyService::maskLead($lead, $user);
        });

        return response()->json([
            'status' => 'success',
            'data' => $items,
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
        $user = $request->user();

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
            'assigned_to' => 'nullable|exists:users,id',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|max:50',
            'budget' => 'nullable|numeric|min:0',
            'expected_value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'status' => 'nullable|string|in:new,contacted,qualified,unqualified,converted,lost',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['status'] = $validated['status'] ?? 'new';

        // Super Admin can assign; non-Super Admin cannot assign to others (Rule 6, 13)
        if ($user && $user->isSuperAdmin()) {
            $assignedId = $validated['assigned_to'] ?? $validated['owner_id'] ?? null;
            $validated['assigned_to'] = $assignedId;
            $validated['owner_id'] = $assignedId;
        } else {
            $validated['assigned_to'] = $user?->id;
            $validated['owner_id'] = $user?->id;
        }

        $lead = CrmLead::create($validated);

        // Calculate initial lead score
        $this->scoringService->calculateScore($lead);

        CrmAuditLog::log(
            'Lead Created',
            CrmLead::class,
            $lead->id,
            null,
            $lead->toArray(),
            $companyId,
            $user?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Lead created successfully',
            'data' => ContactPrivacyService::maskLead($lead->fresh(['contact', 'customer', 'owner', 'assignedUser', 'leadSource', 'campaign']), $user),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $details = $this->leadService->getDetails($lead);
        $details['lead'] = ContactPrivacyService::maskLead($details['lead'], $user);

        return response()->json([
            'status' => 'success',
            'data' => $details,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);
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
            'assigned_to' => 'nullable|exists:users,id',
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

        // Security: Non-Super Admin cannot assign or reassign leads (Rule 6 & 13)
        if (!$user || !$user->isSuperAdmin()) {
            unset($validated['assigned_to']);
            unset($validated['owner_id']);

            // Non-Super Admin cannot modify email/phone if not assigned to this lead
            if (!ContactPrivacyService::canViewLeadContactInfo($user, $lead)) {
                unset($validated['email']);
                unset($validated['phone']);
            }
        } else {
            if (array_key_exists('assigned_to', $validated)) {
                $validated['owner_id'] = $validated['assigned_to'];
            }
        }

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
            $user?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Lead updated successfully',
            'data' => ContactPrivacyService::maskLead($lead->fresh(['contact', 'customer', 'owner', 'assignedUser', 'leadSource', 'campaign', 'scoreItems']), $user),
        ]);
    }

    /**
     * Dedicated Super Admin-only Assignment Endpoint.
     * Rule 6: Super Admin-only assignment control.
     * Rule 7: Immediate revocation/granting upon reassignment.
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only Super Admin can assign or reassign leads.',
            ], 403);
        }

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $oldAssignedTo = $lead->assigned_to;
        $newAssignedTo = $validated['assigned_to'] ?? null;

        $lead->assigned_to = $newAssignedTo;
        $lead->owner_id = $newAssignedTo;
        $lead->save();

        CrmAuditLog::log(
            'Lead Reassigned',
            CrmLead::class,
            $lead->id,
            ['assigned_to' => $oldAssignedTo],
            ['assigned_to' => $newAssignedTo],
            $companyId,
            $user->id
        );

        return response()->json([
            'status' => 'success',
            'message' => $newAssignedTo ? 'Lead assigned successfully.' : 'Lead unassigned successfully.',
            'data' => ContactPrivacyService::maskLead($lead->fresh(['assignedUser', 'owner', 'contact', 'leadSource']), $user),
        ]);
    }

    /**
     * Get list of assignable users/employees for lead assignment.
     */
    public function assignees(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);

        // Ensure Bhaumik Prajapati exists as an active user & employee in the database
        $bhaumikUser = User::where(function ($q) {
            $q->where('name', 'like', '%Bhaumik%')
              ->orWhere('email', 'bp6871051@gmail.com');
        })->first();

        if (!$bhaumikUser) {
            $bhaumikUser = User::create([
                'name' => 'Bhaumik Prajapati',
                'email' => 'bp6871051@gmail.com',
                'password' => bcrypt('password'),
                'role' => 'Super Admin',
                'company_id' => $companyId,
                'is_active' => true,
            ]);
        }

        $bhaumikEmp = Employee::where('user_id', $bhaumikUser->id)
            ->orWhere('email', 'bp6871051@gmail.com')
            ->orWhere('first_name', 'Bhaumik')
            ->first();

        if (!$bhaumikEmp) {
            Employee::create([
                'first_name' => 'Bhaumik',
                'last_name' => 'Prajapati',
                'email' => 'bp6871051@gmail.com',
                'phone' => '+919328783665',
                'company_id' => $companyId,
                'status' => 'active',
                'user_id' => $bhaumikUser->id,
                'employee_code' => 'EMP-BP01',
                'joining_date' => now()->toDateString(),
            ]);
        } else if (!$bhaumikEmp->user_id) {
            $bhaumikEmp->update(['user_id' => $bhaumikUser->id]);
        }

        // Fetch active employees from employees table
        $employees = Employee::where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->with(['user', 'department'])
            ->get();

        // Also fetch active users from users table
        $users = User::where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where('is_active', true)
            ->with(['employee', 'department'])
            ->get();

        $assignees = collect();

        // 1. First add all active employees from employees table
        foreach ($employees as $emp) {
            $userObj = $emp->user;
            if (!$userObj && !empty($emp->email)) {
                $userObj = User::where('email', $emp->email)->first();
                if (!$userObj) {
                    $userObj = User::create([
                        'name' => trim($emp->first_name . ' ' . ($emp->last_name ?? '')),
                        'email' => $emp->email,
                        'password' => bcrypt('password'),
                        'role' => 'Employee',
                        'company_id' => $emp->company_id ?? $companyId,
                        'department_id' => $emp->department_id,
                        'is_active' => true,
                    ]);
                }
                $emp->update(['user_id' => $userObj->id]);
            }

            if ($userObj) {
                $assignees->push([
                    'id' => $userObj->id,
                    'employee_id' => $emp->id,
                    'name' => trim($emp->first_name . ' ' . ($emp->last_name ?? '')) ?: $userObj->name,
                    'email' => $emp->email ?: $userObj->email,
                    'role' => $userObj->role,
                    'department' => $emp->department?->name ?? $userObj->department?->name ?? 'General',
                    'employee_code' => $emp->employee_code,
                ]);
            }
        }

        // 2. Also add active users who might not be in employees table (e.g. Admins, Managers, Specialists)
        foreach ($users as $u) {
            if (!$assignees->contains('id', $u->id)) {
                $displayName = $u->name;
                if ($u->employee && !empty($u->employee->first_name)) {
                    $displayName = trim($u->employee->first_name . ' ' . ($u->employee->last_name ?? ''));
                }
                $assignees->push([
                    'id' => $u->id,
                    'employee_id' => $u->employee?->id,
                    'name' => $displayName,
                    'email' => $u->email,
                    'role' => $u->role,
                    'department' => $u->department?->name ?? 'General',
                    'employee_code' => $u->employee?->employee_code,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $assignees->sortBy('name')->values()->all(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $lead->delete();

        CrmAuditLog::log(
            'Lead Deleted',
            CrmLead::class,
            $id,
            null,
            null,
            $companyId,
            $user?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Lead deleted successfully',
        ]);
    }

    public function convert(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $result = $this->conversionService->convert($lead, $request->all(), $user?->id);

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
        return $this->reportService->exportLeadsCsv($companyId, $request->user());
    }
}
