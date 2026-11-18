<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmDuplicateLeadRequest;
use App\Models\CrmLead;
use App\Models\DeleteRequest;
use App\Models\Employee;
use App\Models\ErpNotification;
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
        $paginator = $this->leadService->list($request->all(), $companyId, $user);

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
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'other_link' => 'nullable|string|max:255',
            'lead_source_id' => 'nullable|exists:crm_lead_sources,id',
            'lead_source_name' => 'nullable|string|max:100',
            'other_source_details' => 'nullable|string',
            'is_duplicate_approved' => 'nullable|boolean',
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
            'documents' => 'nullable|array',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['status'] = $validated['status'] ?? 'new';

        // Super Admin and Managers can assign leads to employees or leave unassigned
        if ($user && ($user->isSuperAdmin() || $user->isManager())) {
            $assignedId = $validated['assigned_to'] ?? $validated['owner_id'] ?? null;
            $validated['assigned_to'] = $assignedId;
            $validated['owner_id'] = $assignedId ?: $user->id;
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
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'other_link' => 'nullable|string|max:255',
            'lead_source_id' => 'nullable|exists:crm_lead_sources,id',
            'lead_source_name' => 'nullable|string|max:100',
            'other_source_details' => 'nullable|string',
            'is_duplicate_approved' => 'nullable|boolean',
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
            'documents' => 'nullable|array',
        ]);

        // Security: Non-Super Admin and non-Managers cannot assign or reassign leads
        if (!$user || (!$user->isSuperAdmin() && !$user->isManager())) {
            unset($validated['assigned_to']);
            unset($validated['owner_id']);

            // Non-Super Admin / Non-Manager cannot modify email/phone if not assigned to this lead
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
     * Dedicated Super Admin and Manager Assignment & Reassignment Endpoint.
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isManager())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only Super Admin and Managers can assign or reassign leads.',
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

        // Send reassignment notification if assigned to someone new
        if ($newAssignedTo && $newAssignedTo !== $oldAssignedTo) {
            try {
                ErpNotification::create([
                    'user_id' => $newAssignedTo,
                    'type' => 'crm',
                    'title' => 'New Lead Assigned',
                    'message' => "Lead '{$lead->name}' has been assigned to you by {$user->name}.",
                    'link' => '/app/crm/leads',
                    'icon' => 'UserCheck',
                ]);
            } catch (\Throwable $e) {}
        }

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

        // If user is NOT Super Admin, deletion requires Super Admin approval
        if (!$user || !$user->isSuperAdmin()) {
            $reason = $request->input('reason', 'Deletion request initiated by ' . ($user?->name ?? 'User'));

            // Check if there is already a pending delete request for this lead
            $existing = DeleteRequest::where('company_id', $companyId)
                ->where('module', 'leads')
                ->where('record_id', $id)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => 'pending',
                    'message' => 'A deletion approval request for this lead is already pending with Super Admin.',
                    'data' => $existing,
                ]);
            }

            $deleteReq = DeleteRequest::create([
                'company_id' => $companyId,
                'user_id' => $user?->id,
                'module' => 'leads',
                'record_id' => $lead->id,
                'record_title' => $lead->name . ($lead->company_name ? " ({$lead->company_name})" : ''),
                'reason' => $reason,
                'status' => 'pending',
            ]);

            // Notify all Super Admins
            try {
                $superAdminIds = User::where('company_id', $companyId)
                    ->where('role', 'Super Admin')
                    ->pluck('id');

                foreach ($superAdminIds as $adminId) {
                    ErpNotification::create([
                        'user_id' => $adminId,
                        'type' => 'crm',
                        'title' => 'Lead Deletion Approval Requested',
                        'message' => "{$user->name} requested approval to delete Lead '{$lead->name}'. Reason: {$reason}",
                        'link' => '/app/system/delete-requests',
                        'icon' => 'Trash2',
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Failed sending lead delete request notification: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'pending_approval',
                'requires_approval' => true,
                'message' => 'Lead deletion request has been submitted to Super Admin for approval.',
                'data' => $deleteReq,
            ]);
        }

        // Direct deletion if Super Admin
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
        $user = $request->user();
        if (!$user || !$user->canImportExport()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access Denied: Only Super Admin and Sales Manager are authorized to export files.',
            ], 403);
        }
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user->company_id ?? 1);
        return $this->reportService->exportLeadsCsv($companyId, $user);
    }

    public function import(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->canImportExport()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Access Denied: Only Super Admin and Sales Manager are authorized to import files.',
            ], 403);
        }

        $request->validate([
            'rows' => 'required|array',
            'rows.*.name' => 'required|string',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user->company_id ?? 1);
        $imported = 0;
        $rows = $request->input('rows');

        foreach ($rows as $row) {
            $row['company_id'] = $companyId;
            $row['status'] = $row['status'] ?? 'new';
            $row['owner_id'] = $user->id;
            $lead = CrmLead::create($row);
            $this->scoringService->calculateScore($lead);
            $imported++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Successfully imported {$imported} leads.",
            'imported_count' => $imported,
        ]);
    }

    /**
     * Real-time duplicate check across phone, email, and name.
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);

        $phone = trim((string) $request->input('phone', ''));
        $email = trim((string) $request->input('email', ''));
        $name = trim((string) $request->input('name', ''));
        $excludeId = $request->input('exclude_id');

        if (empty($phone) && empty($email) && empty($name)) {
            return response()->json([
                'status' => 'success',
                'is_duplicate' => false,
            ]);
        }

        $query = CrmLead::where('company_id', $companyId)->with(['assignedUser', 'leadSource']);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // 1. Check Phone (clean digits, last 10 digits match or full match)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (!empty($cleanPhone) && strlen($cleanPhone) >= 7) {
            $phoneSuffix = substr($cleanPhone, -10);
            $existing = (clone $query)->where(function ($q) use ($phone, $phoneSuffix) {
                $q->where('phone', 'like', "%{$phoneSuffix}%")
                  ->orWhere('phone', $phone);
            })->first();

            if ($existing) {
                return response()->json([
                    'status' => 'success',
                    'is_duplicate' => true,
                    'matched_field' => 'phone',
                    'matched_value' => $existing->phone,
                    'existing_lead' => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'company_name' => $existing->company_name,
                        'phone' => $existing->phone,
                        'email' => $existing->email,
                        'status' => $existing->status,
                        'assigned_to' => $existing->assigned_to,
                        'assigned_user' => $existing->assignedUser ? [
                            'id' => $existing->assignedUser->id,
                            'name' => $existing->assignedUser->name,
                            'department' => $existing->assignedUser->department_name,
                        ] : null,
                        'created_at' => $existing->created_at ? $existing->created_at->format('d M Y') : '',
                    ],
                ]);
            }
        }

        // 2. Check Email (case-insensitive exact match)
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $existing = (clone $query)->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            if ($existing) {
                return response()->json([
                    'status' => 'success',
                    'is_duplicate' => true,
                    'matched_field' => 'email',
                    'matched_value' => $existing->email,
                    'existing_lead' => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'company_name' => $existing->company_name,
                        'phone' => $existing->phone,
                        'email' => $existing->email,
                        'status' => $existing->status,
                        'assigned_to' => $existing->assigned_to,
                        'assigned_user' => $existing->assignedUser ? [
                            'id' => $existing->assignedUser->id,
                            'name' => $existing->assignedUser->name,
                            'department' => $existing->assignedUser->department_name,
                        ] : null,
                        'created_at' => $existing->created_at ? $existing->created_at->format('d M Y') : '',
                    ],
                ]);
            }
        }

        // 3. Check Name (case-insensitive match for 3+ chars)
        if (!empty($name) && strlen($name) >= 3) {
            $existing = (clone $query)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if ($existing) {
                return response()->json([
                    'status' => 'success',
                    'is_duplicate' => true,
                    'matched_field' => 'name',
                    'matched_value' => $existing->name,
                    'existing_lead' => [
                        'id' => $existing->id,
                        'name' => $existing->name,
                        'company_name' => $existing->company_name,
                        'phone' => $existing->phone,
                        'email' => $existing->email,
                        'status' => $existing->status,
                        'assigned_to' => $existing->assigned_to,
                        'assigned_user' => $existing->assignedUser ? [
                            'id' => $existing->assignedUser->id,
                            'name' => $existing->assignedUser->name,
                            'department' => $existing->assignedUser->department_name,
                        ] : null,
                        'created_at' => $existing->created_at ? $existing->created_at->format('d M Y') : '',
                    ],
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'is_duplicate' => false,
        ]);
    }

    /**
     * Submit a duplicate lead approval request to Admin.
     */
    public function requestDuplicate(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);

        $validated = $request->validate([
            'existing_lead_id' => 'required|exists:crm_leads,id',
            'lead_data' => 'required|array',
            'matched_field' => 'nullable|string',
            'matched_value' => 'nullable|string',
            'reason' => 'nullable|string|max:1000',
        ]);

        $duplicateReq = CrmDuplicateLeadRequest::create([
            'company_id' => $companyId,
            'requested_by' => $user->id,
            'existing_lead_id' => $validated['existing_lead_id'],
            'matched_field' => $validated['matched_field'] ?? 'phone',
            'matched_value' => $validated['matched_value'] ?? '',
            'lead_data' => $validated['lead_data'],
            'reason' => $validated['reason'] ?? 'User requested to add duplicate lead with different context.',
            'status' => 'pending',
        ]);

        // Notify Admins and Super Admins about the duplicate request
        try {
            $adminIds = User::where('company_id', $companyId)
                ->where(function ($q) {
                    $q->where('role', 'Super Admin')
                      ->orWhere('role', 'Admin')
                      ->orWhere('role', 'like', '%Manager%');
                })
                ->pluck('id');

            $leadName = $validated['lead_data']['name'] ?? 'Lead';
            foreach ($adminIds as $adminId) {
                ErpNotification::create([
                    'user_id' => $adminId,
                    'type' => 'crm',
                    'title' => 'Duplicate Lead Approval Requested',
                    'message' => "{$user->name} requested approval to add duplicate lead '{$leadName}' (Matched existing #{$validated['existing_lead_id']}).",
                    'link' => '/app/crm/duplicate-leads',
                    'icon' => 'CopyCheck',
                ]);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Duplicate lead request sent to Admin for approval.',
            'data' => $duplicateReq,
        ], 201);
    }

    /**
     * Get list of duplicate lead requests for CRM Duplicate Leads page.
     */
    public function duplicateRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user?->company_id ?? 1);

        $query = CrmDuplicateLeadRequest::where('company_id', $companyId)
            ->with(['requester', 'reviewer', 'existingLead.assignedUser', 'createdLead'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $requests = $query->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Admin approves a duplicate lead request and creates the lead.
     */
    public function approveDuplicate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isManager())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only Super Admin and Managers can approve duplicate leads.',
            ], 403);
        }

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user->company_id ?? 1);
        $duplicateReq = CrmDuplicateLeadRequest::where('company_id', $companyId)->findOrFail($id);

        if ($duplicateReq->status === 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'This request has already been approved.',
            ], 422);
        }

        $leadData = $duplicateReq->lead_data;
        $leadData['company_id'] = $companyId;
        $leadData['is_duplicate_approved'] = true;
        $leadData['status'] = $leadData['status'] ?? 'new';
        $leadData['notes'] = ($leadData['notes'] ?? '') . "\n[System: Duplicate approved by {$user->name} on " . now()->format('d M Y H:i') . ". Original Lead ID: #{$duplicateReq->existing_lead_id}]";

        // Create the duplicate lead
        $lead = CrmLead::create($leadData);
        $this->scoringService->calculateScore($lead);

        // Update request status
        $duplicateReq->status = 'approved';
        $duplicateReq->reviewed_by = $user->id;
        $duplicateReq->reviewed_at = now();
        $duplicateReq->admin_notes = $request->input('admin_notes', 'Approved by ' . $user->name);
        $duplicateReq->created_lead_id = $lead->id;
        $duplicateReq->save();

        // Notify requester
        try {
            ErpNotification::create([
                'user_id' => $duplicateReq->requested_by,
                'type' => 'crm',
                'title' => 'Duplicate Lead Approved!',
                'message' => "Your duplicate lead request for '{$lead->name}' has been approved and added as Lead #{$lead->id}.",
                'link' => '/app/crm/leads',
                'icon' => 'CheckCircle',
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Duplicate lead approved and created successfully.',
            'data' => [
                'request' => $duplicateReq,
                'lead' => $lead,
            ],
        ]);
    }

    /**
     * Admin rejects a duplicate lead request.
     */
    public function rejectDuplicate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isManager())) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only Super Admin and Managers can reject duplicate leads.',
            ], 403);
        }

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user->company_id ?? 1);
        $duplicateReq = CrmDuplicateLeadRequest::where('company_id', $companyId)->findOrFail($id);

        $duplicateReq->status = 'rejected';
        $duplicateReq->reviewed_by = $user->id;
        $duplicateReq->reviewed_at = now();
        $duplicateReq->admin_notes = $request->input('admin_notes', 'Rejected by ' . $user->name);
        $duplicateReq->save();

        // Notify requester
        try {
            $reason = $duplicateReq->admin_notes;
            ErpNotification::create([
                'user_id' => $duplicateReq->requested_by,
                'type' => 'crm',
                'title' => 'Duplicate Lead Request Rejected',
                'message' => "Your request to add duplicate lead for '{$duplicateReq->matched_value}' was rejected. Reason: {$reason}",
                'link' => '/app/crm/duplicate-leads',
                'icon' => 'XCircle',
            ]);
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Duplicate lead request rejected.',
            'data' => $duplicateReq,
        ]);
    }

    /**
     * Send reassignment request to Admin.
     */
    public function requestReassign(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($user->company_id ?? 1);
        $lead = CrmLead::where('company_id', $companyId)->findOrFail($id);

        $targetUserId = $request->input('target_user_id');
        $reason = $request->input('reason', 'Reassignment requested');

        $targetUser = $targetUserId ? User::find($targetUserId) : null;

        // If user is Super Admin or Manager, execute direct reassignment
        if ($user->isSuperAdmin() || $user->isManager()) {
            $oldAssignedTo = $lead->assigned_to;
            $lead->assigned_to = $targetUserId;
            $lead->owner_id = $targetUserId;
            $lead->save();

            if ($targetUserId && $targetUserId !== $oldAssignedTo) {
                try {
                    ErpNotification::create([
                        'user_id' => $targetUserId,
                        'type' => 'crm',
                        'title' => 'Lead Reassigned to You',
                        'message' => "Lead '{$lead->name}' has been assigned to you by {$user->name}. Reason: {$reason}",
                        'link' => '/app/crm/leads',
                        'icon' => 'UserCheck',
                    ]);
                } catch (\Throwable $e) {}
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Lead reassigned successfully.',
                'data' => $lead,
            ]);
        }

        // Otherwise send notification to Admins
        try {
            $admins = User::where('company_id', $companyId)
                ->where(function ($q) {
                    $q->where('role', 'Super Admin')->orWhere('role', 'Admin');
                })->get();

            foreach ($admins as $admin) {
                ErpNotification::create([
                    'user_id' => $admin->id,
                    'type' => 'crm',
                    'title' => 'Lead Reassignment Requested',
                    'message' => "{$user->name} requested to reassign Lead '{$lead->name}' to " . ($targetUser ? $targetUser->name : 'Unassigned') . ". Reason: {$reason}",
                    'link' => '/app/crm/leads',
                    'icon' => 'UserCheck',
                ]);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Reassignment request sent to Admin.',
        ]);
    }
}
