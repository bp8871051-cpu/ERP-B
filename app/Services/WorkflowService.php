<?php

namespace App\Services;

use App\Models\ErpNotification;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowApproval;
use App\Models\WorkflowComment;
use App\Models\WorkflowLog;
use App\Models\WorkflowRequest;
use App\Models\WorkflowStep;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function getDashboardData(User $user, ?string $filter = null, ?string $module = null, ?string $search = null)
    {
        $companyId = $user->company_id ?: 1;

        // Metrics
        $pendingApprovals = WorkflowRequest::where('company_id', $companyId)->where('status', 'pending')->count();
        $approvedRequests = WorkflowRequest::where('company_id', $companyId)->where('status', 'approved')->count();
        $rejectedRequests = WorkflowRequest::where('company_id', $companyId)->where('status', 'rejected')->count();
        $myRequests = WorkflowRequest::where('company_id', $companyId)->where('requester_id', $user->id)->count();
        $overdueRequests = WorkflowRequest::where('company_id', $companyId)->where('status', 'pending')->whereDate('due_date', '<', Carbon::today())->count();

        // Requests query
        $reqQuery = WorkflowRequest::where('company_id', $companyId)
            ->with([
                'requester:id,name,email,avatar,role,department_id',
                'workflow:id,name,module',
                'currentStep.approverUser:id,name,avatar,role',
                'approvals.approver:id,name,avatar,role',
                'approvals.step',
                'comments.user:id,name,avatar',
                'logs.user:id,name',
            ])
            ->orderBy('created_at', 'desc');

        if ($filter === 'my_requests') {
            $reqQuery->where('requester_id', $user->id);
        } elseif ($filter && in_array($filter, ['pending', 'approved', 'rejected', 'changes_requested'])) {
            $reqQuery->where('status', $filter);
        } elseif ($filter === 'overdue') {
            $reqQuery->where('status', 'pending')->whereDate('due_date', '<', Carbon::today());
        }

        if ($module && $module !== 'all') {
            $reqQuery->where('module', $module);
        }

        if ($search) {
            $reqQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('requester', fn($rq) => $rq->where('name', 'like', "%{$search}%"));
            });
        }

        $requests = $reqQuery->take(50)->get()->map(function ($req) {
            $totalSteps = $req->workflow ? WorkflowStep::where('workflow_id', $req->workflow_id)->count() : 3;
            $currentStepOrder = $req->currentStep ? $req->currentStep->step_order : ($req->status === 'approved' ? $totalSteps : 1);

            return [
                'id' => $req->id,
                'reference' => $req->reference_number,
                'title' => $req->title,
                'module' => $req->module,
                'amount' => $req->amount ? (float) $req->amount : null,
                'amountFormatted' => $req->amount ? ('$' . number_format($req->amount, 2)) : 'N/A',
                'status' => $req->status,
                'data' => $req->data ?: [],
                'requester' => [
                    'id' => $req->requester?->id,
                    'name' => $req->requester?->name ?? 'Requester',
                    'email' => $req->requester?->email,
                    'avatar' => $req->requester?->avatar,
                    'role' => $req->requester?->role,
                ],
                'currentStep' => $req->currentStep ? [
                    'id' => $req->currentStep->id,
                    'order' => $req->currentStep->step_order,
                    'name' => $req->currentStep->name,
                    'role' => $req->currentStep->approver_role,
                    'approver' => $req->currentStep->approverUser ? [
                        'name' => $req->currentStep->approverUser->name,
                        'avatar' => $req->currentStep->approverUser->avatar,
                    ] : null,
                ] : null,
                'progress' => [
                    'current' => $currentStepOrder,
                    'total' => $totalSteps ?: 3,
                    'percent' => $req->status === 'approved' ? 100 : ($totalSteps > 0 ? round((($currentStepOrder - 1) / $totalSteps) * 100) : 33),
                ],
                'approvals' => $req->approvals->map(fn($app) => [
                    'id' => $app->id,
                    'stepName' => $app->step?->name ?? 'Approval Step',
                    'approver' => $app->approver?->name ?? 'Approver',
                    'action' => $app->action,
                    'comments' => $app->comments,
                    'date' => $app->action_taken_at ? $app->action_taken_at->format('M d, Y h:i A') : null,
                ]),
                'comments' => $req->comments->map(fn($comm) => [
                    'id' => $comm->id,
                    'user' => [
                        'name' => $comm->user?->name ?? 'User',
                        'avatar' => $comm->user?->avatar,
                    ],
                    'comment' => $comm->comment,
                    'createdAt' => $comm->created_at->diffForHumans(),
                ]),
                'logs' => $req->logs->map(fn($l) => [
                    'id' => $l->id,
                    'user' => $l->user?->name ?? 'System',
                    'action' => $l->action,
                    'description' => $l->description,
                    'date' => $l->created_at->format('M d, Y h:i A'),
                ]),
                'createdDate' => $req->created_at->format('M d, Y'),
                'dueDate' => $req->due_date ? $req->due_date->format('M d, Y') : null,
                'isOverdue' => $req->due_date && $req->due_date->isPast() && $req->status === 'pending',
            ];
        });

        // Configured Workflows list
        $workflows = Workflow::where('company_id', $companyId)
            ->with(['steps' => fn($q) => $q->orderBy('step_order', 'asc'), 'creator:id,name'])
            ->withCount('requests')
            ->get()
            ->map(function ($wf) {
                return [
                    'id' => $wf->id,
                    'name' => $wf->name,
                    'description' => $wf->description,
                    'module' => $wf->module,
                    'trigger' => $wf->trigger,
                    'status' => $wf->status,
                    'stepsCount' => $wf->steps->count(),
                    'steps' => $wf->steps->map(fn($st) => [
                        'id' => $st->id,
                        'order' => $st->step_order,
                        'name' => $st->name,
                        'approverRole' => $st->approver_role,
                        'slaHours' => $st->sla_hours,
                    ]),
                    'requestsCount' => $wf->requests_count,
                    'createdAt' => $wf->created_at->format('M d, Y'),
                ];
            });

        return [
            'metrics' => [
                'pendingApprovals' => $pendingApprovals,
                'approved' => $approvedRequests,
                'rejected' => $rejectedRequests,
                'myRequests' => $myRequests,
                'overdue' => $overdueRequests,
            ],
            'requests' => $requests,
            'workflows' => $workflows,
            'modules' => [
                'Purchase Request',
                'Expense Claim',
                'Leave Application',
                'Contract Approval',
                'Invoice Authorization',
                'Hardware Requisition',
            ],
        ];
    }

    public function createWorkflow(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;

        return DB::transaction(function () use ($companyId, $user, $data) {
            $workflow = Workflow::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'module' => $data['module'],
                'trigger' => $data['trigger'] ?? 'on_submit',
                'status' => $data['status'] ?? 'active',
                'created_by' => $user->id,
            ]);

            $steps = $data['steps'] ?? [
                ['name' => 'Department Manager', 'approver_role' => 'Manager', 'sla_hours' => 24],
                ['name' => 'Finance Director', 'approver_role' => 'Finance Manager', 'sla_hours' => 48],
                ['name' => 'Executive Sign-off', 'approver_role' => 'Admin', 'sla_hours' => 72],
            ];

            foreach ($steps as $idx => $st) {
                WorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'step_order' => $idx + 1,
                    'name' => $st['name'],
                    'approver_role' => $st['approver_role'] ?? 'Manager',
                    'approver_user_id' => $st['approver_user_id'] ?? null,
                    'type' => $st['type'] ?? 'sequential',
                    'sla_hours' => $st['sla_hours'] ?? 24,
                ]);
            }

            return $workflow->load('steps');
        });
    }

    public function createRequest(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;

        $workflow = Workflow::with(['steps' => fn($q) => $q->orderBy('step_order', 'asc')])
            ->find($data['workflow_id'] ?? null);

        if (!$workflow) {
            $workflow = Workflow::with(['steps' => fn($q) => $q->orderBy('step_order', 'asc')])
                ->where('company_id', $companyId)
                ->first();
        }

        $firstStep = $workflow?->steps->first();
        $refNo = 'REQ-2026-' . strtoupper(substr(uniqid(), -6));

        return DB::transaction(function () use ($companyId, $user, $workflow, $firstStep, $refNo, $data) {
            $request = WorkflowRequest::create([
                'company_id' => $companyId,
                'workflow_id' => $workflow?->id ?: 1,
                'requester_id' => $user->id,
                'reference_number' => $refNo,
                'title' => $data['title'] ?? 'Enterprise Approval Request',
                'module' => $workflow?->module ?? ($data['module'] ?? 'Purchase Request'),
                'amount' => $data['amount'] ?? null,
                'data' => $data['data'] ?? [],
                'current_step_id' => $firstStep?->id,
                'status' => 'pending',
                'due_date' => now()->addDays(5),
            ]);

            WorkflowLog::create([
                'workflow_request_id' => $request->id,
                'user_id' => $user->id,
                'action' => 'SUBMITTED',
                'description' => "Request {$refNo} submitted by {$user->name}",
            ]);

            ErpNotification::create([
                'company_id' => $companyId,
                'user_id' => 1, // Admin or approver
                'type' => 'workflow',
                'title' => 'New Approval Request',
                'message' => "{$user->name} submitted request {$refNo} for {$request->title}",
                'link' => '/app/applications/workflows',
                'icon' => 'GitPullRequest',
            ]);

            return $request->load(['requester', 'workflow', 'currentStep']);
        });
    }

    public function approveRequest(int $id, User $user, ?string $comments = null)
    {
        return DB::transaction(function () use ($id, $user, $comments) {
            $request = WorkflowRequest::with(['workflow.steps' => fn($q) => $q->orderBy('step_order', 'asc'), 'currentStep'])
                ->findOrFail($id);

            $currentStep = $request->currentStep;

            WorkflowApproval::create([
                'workflow_request_id' => $request->id,
                'workflow_step_id' => $currentStep?->id ?: 1,
                'approver_id' => $user->id,
                'action' => 'approved',
                'comments' => $comments ?: 'Approved by ' . $user->name,
                'action_taken_at' => now(),
            ]);

            WorkflowLog::create([
                'workflow_request_id' => $request->id,
                'user_id' => $user->id,
                'action' => 'APPROVED_STEP',
                'description' => "Step '{$currentStep?->name}' approved by {$user->name}",
            ]);

            // Find next step
            $nextStep = $request->workflow?->steps
                ->where('step_order', '>', $currentStep?->step_order ?? 0)
                ->first();

            if ($nextStep) {
                $request->update([
                    'current_step_id' => $nextStep->id,
                ]);
            } else {
                // Final approval reached
                $request->update([
                    'status' => 'approved',
                    'current_step_id' => null,
                ]);

                WorkflowLog::create([
                    'workflow_request_id' => $request->id,
                    'user_id' => $user->id,
                    'action' => 'COMPLETED',
                    'description' => "All approval stages completed. Request marked as APPROVED.",
                ]);

                // Notify requester
                ErpNotification::create([
                    'company_id' => $request->company_id,
                    'user_id' => $request->requester_id,
                    'type' => 'workflow',
                    'title' => 'Request Approved',
                    'message' => "Your request {$request->reference_number} has been fully approved!",
                    'link' => '/app/applications/workflows',
                    'icon' => 'CheckCircle',
                ]);
            }

            return $request->load(['requester', 'workflow', 'currentStep', 'approvals']);
        });
    }

    public function rejectRequest(int $id, User $user, ?string $comments = null)
    {
        return DB::transaction(function () use ($id, $user, $comments) {
            $request = WorkflowRequest::findOrFail($id);

            WorkflowApproval::create([
                'workflow_request_id' => $request->id,
                'workflow_step_id' => $request->current_step_id ?: 1,
                'approver_id' => $user->id,
                'action' => 'rejected',
                'comments' => $comments ?: 'Rejected by ' . $user->name,
                'action_taken_at' => now(),
            ]);

            $request->update([
                'status' => 'rejected',
            ]);

            WorkflowLog::create([
                'workflow_request_id' => $request->id,
                'user_id' => $user->id,
                'action' => 'REJECTED',
                'description' => "Request rejected by {$user->name}: " . ($comments ?: 'No comments provided.'),
            ]);

            ErpNotification::create([
                'company_id' => $request->company_id,
                'user_id' => $request->requester_id,
                'type' => 'workflow',
                'title' => 'Request Rejected',
                'message' => "Your request {$request->reference_number} was rejected.",
                'link' => '/app/applications/workflows',
                'icon' => 'XCircle',
            ]);

            return $request->load(['requester', 'workflow', 'approvals']);
        });
    }

    public function requestChanges(int $id, User $user, string $comments)
    {
        return DB::transaction(function () use ($id, $user, $comments) {
            $request = WorkflowRequest::findOrFail($id);

            WorkflowApproval::create([
                'workflow_request_id' => $request->id,
                'workflow_step_id' => $request->current_step_id ?: 1,
                'approver_id' => $user->id,
                'action' => 'changes_requested',
                'comments' => $comments,
                'action_taken_at' => now(),
            ]);

            $request->update([
                'status' => 'changes_requested',
            ]);

            WorkflowLog::create([
                'workflow_request_id' => $request->id,
                'user_id' => $user->id,
                'action' => 'CHANGES_REQUESTED',
                'description' => "Changes requested by {$user->name}: {$comments}",
            ]);

            ErpNotification::create([
                'company_id' => $request->company_id,
                'user_id' => $request->requester_id,
                'type' => 'workflow',
                'title' => 'Changes Requested on Workflow',
                'message' => "{$user->name} requested adjustments on {$request->reference_number}",
                'link' => '/app/applications/workflows',
                'icon' => 'AlertCircle',
            ]);

            return $request->load(['requester', 'workflow', 'approvals']);
        });
    }

    public function addComment(int $id, User $user, string $comment)
    {
        $comm = WorkflowComment::create([
            'workflow_request_id' => $id,
            'user_id' => $user->id,
            'comment' => $comment,
        ]);

        return $comm->load('user');
    }
}
