<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\Milestone;
use App\Models\Timesheet;
use App\Models\ProjectSiteReport;
use App\Models\ProjectEngineeringIssue;
use App\Models\ProjectTechnicalReview;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\SystemAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProjectEngineerController extends Controller
{
    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    /**
     * Get Project Engineer Dashboard Metrics and Sections
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();
        $userId = $user->id;
        $isSuperOrAdmin = in_array($user->role, ['Super Admin', 'Admin', 'Project Manager']);

        // Data Restriction: Project Engineers access only assigned projects
        if ($isSuperOrAdmin) {
            $assignedProjectIds = Project::where('company_id', $companyId)->pluck('id')->toArray();
        } else {
            $assignedProjectIds = ProjectMember::where('user_id', $userId)->pluck('project_id')->toArray();
            if (empty($assignedProjectIds)) {
                $assignedProjectIds = Project::where('company_id', $companyId)->take(2)->pluck('id')->toArray();
            }
        }

        $projects = Project::whereIn('id', $assignedProjectIds)
            ->with(['client', 'milestones', 'tasks'])
            ->get();

        // Tasks assigned to this project engineer
        $taskQuery = Task::where('company_id', $companyId)->whereIn('project_id', $assignedProjectIds);
        if (!$isSuperOrAdmin) {
            $taskQuery->where('assigned_to', $userId);
        }
        $tasks = $taskQuery->with('project')->latest()->get();

        $totalTasks = $tasks->count();
        $inProgressTasks = $tasks->where('status', 'in_progress')->count();
        $pendingTasks = $tasks->whereIn('status', ['pending', 'todo'])->count();
        $overdueTasks = $tasks->filter(function ($t) {
            return $t->due_date && Carbon::parse($t->due_date)->isPast() && !in_array($t->status, ['completed', 'done']);
        })->count();

        // Milestones for assigned projects
        $milestones = Milestone::whereIn('project_id', $assignedProjectIds)
            ->with('project')
            ->orderBy('due_date')
            ->get();
        $upcomingMilestones = $milestones->where('status', '!=', 'completed')->values();

        // Technical Reviews
        $technicalReviews = ProjectTechnicalReview::where('company_id', $companyId)
            ->whereIn('project_id', $assignedProjectIds)
            ->with(['project', 'submitter'])
            ->latest()
            ->get();
        $pendingTechReviews = $technicalReviews->whereIn('status', ['pending', 'under_review'])->values();

        // Material Requests submitted by this Project Engineer
        $materialReqQuery = MaterialRequest::where('company_id', $companyId)
            ->where('request_type', 'project_material');
        if (!$isSuperOrAdmin) {
            $materialReqQuery->where('requested_by', $userId);
        }
        $materialRequests = $materialReqQuery->with(['items.product', 'warehouse', 'project'])->latest()->get();
        $pendingMaterialReqs = $materialRequests->where('status', 'pending')->values();

        // Site Progress Reports
        $siteReports = ProjectSiteReport::where('company_id', $companyId)
            ->whereIn('project_id', $assignedProjectIds)
            ->with('project')
            ->latest()
            ->limit(10)
            ->get();

        // Engineering Issues
        $issues = ProjectEngineeringIssue::where('company_id', $companyId)
            ->whereIn('project_id', $assignedProjectIds)
            ->with('project')
            ->latest()
            ->get();

        // Timesheet Summary
        $timesheets = Timesheet::where('user_id', $userId)
            ->latest()
            ->limit(10)
            ->get();
        $totalHoursLogged = $timesheets->sum('hours');

        // Recent Activities
        $activities = [];
        foreach ($tasks->take(4) as $t) {
            $activities[] = [
                'id' => 'tsk_' . $t->id,
                'title' => 'Task Updated: ' . $t->title,
                'description' => ($t->project?->name ?? 'Project') . ' [' . ucfirst($t->status) . ']',
                'time' => $t->updated_at?->diffForHumans() ?: 'Recently',
                'type' => 'task',
            ];
        }
        foreach ($siteReports->take(3) as $sr) {
            $activities[] = [
                'id' => 'sr_' . $sr->id,
                'title' => 'Site Report Submitted',
                'description' => ($sr->project?->name ?? 'Project') . ' (' . Carbon::parse($sr->report_date)->format('M d, Y') . ')',
                'time' => $sr->created_at?->diffForHumans() ?: 'Today',
                'type' => 'report',
            ];
        }

        // Lookups for quick actions
        $availableProducts = Product::where('company_id', $companyId)->select('id', 'name', 'sku')->get();
        $availableWarehouses = Warehouse::where('company_id', $companyId)->select('id', 'name')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'cards' => [
                    'assigned_projects' => $projects->count(),
                    'total_assigned_tasks' => $totalTasks,
                    'tasks_in_progress' => $inProgressTasks,
                    'tasks_pending' => $pendingTasks,
                    'overdue_tasks' => $overdueTasks,
                    'upcoming_milestones' => $upcomingMilestones->count(),
                    'pending_technical_reviews' => $pendingTechReviews->count(),
                    'pending_material_requests' => $pendingMaterialReqs->count(),
                ],
                'sections' => [
                    'my_assigned_projects' => $projects,
                    'my_tasks_and_deadlines' => $tasks,
                    'upcoming_milestones' => $upcomingMilestones,
                    'technical_review_queue' => $technicalReviews,
                    'site_progress_reports' => $siteReports,
                    'pending_material_requests' => $materialRequests,
                    'project_issues_and_risks' => $issues,
                    'timesheet_summary' => [
                        'total_hours' => $totalHoursLogged,
                        'entries' => $timesheets,
                    ],
                    'recent_activities' => $activities,
                ],
                'lookups' => [
                    'projects' => $projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'code' => $p->code ?? ('PRJ-' . $p->id)]),
                    'products' => $availableProducts,
                    'warehouses' => $availableWarehouses,
                ],
            ],
        ]);
    }

    /**
     * Quick Action: Update Task Progress
     */
    public function updateTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,id',
            'status' => 'required|string|in:pending,in_progress,completed,on_hold',
            'notes' => 'nullable|string',
        ]);

        $task = Task::findOrFail($validated['task_id']);
        $task->status = $validated['status'];
        $task->save();

        SystemAuditLog::log('projects', 'update_task_progress', (string) $task->id, null, [
            'status' => $task->status,
            'notes' => $validated['notes'] ?? null,
        ], $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => "Task status updated to " . ucfirst(str_replace('_', ' ', $validated['status'])),
            'data' => $task,
        ]);
    }

    /**
     * Quick Action: Submit Daily Site Progress Report
     */
    public function submitProgressReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'report_date' => 'required|date',
            'work_completed' => 'required|string',
            'site_conditions' => 'nullable|string|max:255',
            'blockers' => 'nullable|string',
            'manpower_count' => 'nullable|integer|min:0',
        ]);

        $report = ProjectSiteReport::create([
            'company_id' => $this->getCompanyId($request),
            'project_id' => $validated['project_id'],
            'engineer_id' => $request->user()->id,
            'report_date' => $validated['report_date'],
            'work_completed' => $validated['work_completed'],
            'site_conditions' => $validated['site_conditions'] ?? 'Normal working conditions',
            'blockers' => $validated['blockers'] ?? null,
            'manpower_count' => $validated['manpower_count'] ?? 1,
        ]);

        SystemAuditLog::log('projects', 'submit_site_report', (string) $report->id, null, $report->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Daily Site Progress Report submitted successfully.',
            'data' => $report,
        ]);
    }

    /**
     * Quick Action: Record Engineering Issue
     */
    public function recordIssue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|string|in:low,medium,high,critical',
            'mitigation_plan' => 'nullable|string',
        ]);

        $issue = ProjectEngineeringIssue::create([
            'company_id' => $this->getCompanyId($request),
            'project_id' => $validated['project_id'],
            'engineer_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'status' => 'open',
            'mitigation_plan' => $validated['mitigation_plan'] ?? null,
        ]);

        SystemAuditLog::log('projects', 'record_engineering_issue', (string) $issue->id, null, $issue->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Engineering issue logged with severity ' . strtoupper($validated['severity']) . '.',
            'data' => $issue,
        ]);
    }

    /**
     * Quick Action: Submit Material Request to Store (Interacts with Store Executive)
     */
    public function submitMaterialRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'warehouse_id' => 'nullable|integer',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'required_date' => 'required|date',
            'site_location' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $requestNo = 'MR-PRJ-' . date('Y') . '-' . str_pad((string) (MaterialRequest::count() + 1), 4, '0', STR_PAD_LEFT);

        $materialRequest = MaterialRequest::create([
            'company_id' => $this->getCompanyId($request),
            'request_number' => $requestNo,
            'requested_by' => $request->user()->id,
            'request_type' => 'project_material',
            'project_id' => $validated['project_id'],
            'warehouse_id' => $validated['warehouse_id'] ?? Warehouse::first()?->id,
            'status' => 'pending',
            'priority' => $validated['priority'],
            'required_date' => $validated['required_date'],
            'site_location' => $validated['site_location'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $prod = Product::find($item['product_id']);
            MaterialRequestItem::create([
                'material_request_id' => $materialRequest->id,
                'product_id' => $item['product_id'],
                'quantity_requested' => $item['quantity'],
                'quantity_issued' => 0,
                'unit' => $prod->unit ?? 'pcs',
                'notes' => $item['notes'] ?? null,
            ]);
        }

        SystemAuditLog::log('projects', 'submit_material_request', (string) $materialRequest->id, null, $materialRequest->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Material request ' . $requestNo . ' submitted to Store Executive for issue.',
            'data' => $materialRequest->load('items.product'),
        ]);
    }

    /**
     * Quick Action: Enter Timesheet
     */
    public function enterTimesheet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'description' => 'required|string',
        ]);

        $timesheet = Timesheet::create([
            'project_id' => $validated['project_id'],
            'user_id' => $request->user()->id,
            'date' => $validated['date'],
            'hours' => $validated['hours'],
            'description' => $validated['description'],
        ]);

        SystemAuditLog::log('projects', 'enter_timesheet', (string) $timesheet->id, null, $timesheet->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Logged ' . $validated['hours'] . ' hours for engineering timesheet.',
            'data' => $timesheet,
        ]);
    }

    /**
     * Quick Action: Submit Work for Technical Review
     */
    public function submitTechnicalReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'title' => 'required|string|max:255',
            'deliverable_type' => 'required|string|in:drawing,calculation,specification,site_plan',
            'version' => 'nullable|string|max:20',
            'submission_notes' => 'required|string',
        ]);

        $review = ProjectTechnicalReview::create([
            'company_id' => $this->getCompanyId($request),
            'project_id' => $validated['project_id'],
            'submitted_by' => $request->user()->id,
            'title' => $validated['title'],
            'deliverable_type' => $validated['deliverable_type'],
            'version' => $validated['version'] ?? '1.0',
            'status' => 'under_review',
            'submission_notes' => $validated['submission_notes'],
        ]);

        SystemAuditLog::log('projects', 'submit_technical_review', (string) $review->id, null, $review->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Technical deliverable submitted for engineering review.',
            'data' => $review,
        ]);
    }
}
