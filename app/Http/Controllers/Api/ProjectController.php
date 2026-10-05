<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $data = $this->projectService->getDashboardData($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $query = Project::with(['client', 'members.user'])
            ->where('company_id', $companyId);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:customers,id',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'spent' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:not_started,in_progress,on_hold,completed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
            'lead_id' => 'nullable|exists:users,id',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;

        $project = DB::transaction(function () use ($validated, $companyId) {
            $project = Project::create([
                'company_id' => $companyId,
                'client_id' => $validated['client_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'budget' => $validated['budget'] ?? 0,
                'spent' => $validated['spent'] ?? 0,
                'status' => $validated['status'] ?? 'in_progress',
                'progress' => $validated['progress'] ?? 0,
            ]);

            // Add Project Lead
            if (!empty($validated['lead_id'])) {
                ProjectMember::create([
                    'project_id' => $project->id,
                    'user_id' => $validated['lead_id'],
                    'role' => 'Lead',
                ]);
            }

            // Add other members
            if (!empty($validated['member_ids'])) {
                foreach ($validated['member_ids'] as $userId) {
                    if ($userId != ($validated['lead_id'] ?? null)) {
                        ProjectMember::create([
                            'project_id' => $project->id,
                            'user_id' => $userId,
                            'role' => 'Member',
                        ]);
                    }
                }
            }

            return $project->load(['client', 'members.user']);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully!',
            'data' => $project,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $project = Project::with(['client', 'members.user', 'tasks.assignedUser', 'milestones'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $project,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'client_id' => 'nullable|exists:customers,id',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'spent' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:not_started,in_progress,on_hold,completed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $project->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully!',
            'data' => $project->fresh(['client', 'members.user']),
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Project deleted successfully.',
        ]);
    }

    public function meta(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;

        $clients = Customer::where('company_id', $companyId)
            ->select('id', 'name', 'company_name', 'email')
            ->get();

        $users = User::select('id', 'name', 'email', 'avatar', 'role')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'clients' => $clients,
                'users' => $users,
                'statuses' => [
                    ['id' => 'not_started', 'label' => 'Not Started'],
                    ['id' => 'in_progress', 'label' => 'In Progress'],
                    ['id' => 'on_hold', 'label' => 'On Hold'],
                    ['id' => 'completed', 'label' => 'Completed'],
                    ['id' => 'cancelled', 'label' => 'Cancelled'],
                ],
                'categories' => [
                    'Enterprise ERP',
                    'Cloud Infrastructure',
                    'Cybersecurity & Zero-Trust',
                    'Mobile Application',
                    'Web Application',
                    'AI / Machine Learning',
                    'Data Warehouse & ETL',
                    'DevOps & Automation',
                ],
            ],
        ]);
    }
}
