<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use App\Models\CrmTask;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmActivityController extends Controller
{
    protected ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $paginator = $this->activityService->listActivities($request->all(), $companyId);

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
            'type' => 'required|string|in:call,email,meeting,task,note,sms,whatsapp',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
            'customer_id' => 'nullable|exists:customers,id',
            'due_at' => 'nullable|date',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['user_id'] = $request->user()?->id ?? 1;

        $activity = $this->activityService->createActivity($validated, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Activity recorded successfully',
            'data' => $activity->load(['user', 'contact', 'lead', 'deal', 'customer']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $activity = CrmActivity::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'subject' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'status' => 'sometimes|required|string|in:pending,in_progress,completed,cancelled',
        ]);

        if (!empty($validated['status']) && $validated['status'] === 'completed' && empty($validated['completed_at'])) {
            $validated['completed_at'] = now();
        }

        $activity->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Activity updated successfully',
            'data' => $activity->fresh(['user', 'contact', 'lead', 'deal', 'customer']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $activity = CrmActivity::where('company_id', $companyId)->findOrFail($id);

        $activity->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Activity deleted successfully',
        ]);
    }

    // Call logging helper
    public function logCall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
            'call_type' => 'required|string|in:incoming,outgoing,missed',
            'phone_number' => 'nullable|string|max:50',
            'duration_seconds' => 'nullable|integer|min:0',
            'call_time' => 'nullable|date',
            'outcome' => 'required|string|in:connected,left_voicemail,busy,no_answer,wrong_number',
            'notes' => 'nullable|string',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;

        $call = $this->activityService->logCall($validated, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Call logged successfully',
            'data' => $call,
        ], 201);
    }

    // Meeting scheduling helper
    public function scheduleMeeting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|string|max:255',
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
            'participants' => 'nullable|array',
            'status' => 'nullable|string|in:scheduled,completed,rescheduled,cancelled',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;

        $meeting = $this->activityService->scheduleMeeting($validated, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Meeting scheduled successfully',
            'data' => $meeting,
        ], 201);
    }

    // Email logging helper
    public function logEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_email' => 'required|email|max:255',
            'cc_email' => 'nullable|email|max:255',
            'bcc_email' => 'nullable|email|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;

        $email = $this->activityService->logEmail($validated, $request->user()?->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Email sent and logged successfully',
            'data' => $email,
        ], 201);
    }

    // Note creation helper
    public function addNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notable_type' => 'required|string',
            'notable_id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
        ]);

        $note = $this->activityService->addNote(
            $validated['notable_type'],
            $validated['notable_id'],
            $validated['content'],
            $validated['title'],
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Note saved successfully',
            'data' => $note->load('user'),
        ], 201);
    }

    // Task Management
    public function listTasks(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $query = CrmTask::query()->with(['assignedUser', 'contact', 'lead', 'deal']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        if (!empty($request->priority)) {
            $query->where('priority', $request->priority);
        }

        if (!empty($request->assigned_to)) {
            $query->where('assigned_to', $request->assigned_to);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->paginate(15),
        ]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'lead_id' => 'nullable|exists:crm_leads,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
            'customer_id' => 'nullable|exists:customers,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['created_by'] = $request->user()?->id ?? 1;

        $task = CrmTask::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Task created successfully',
            'data' => $task->load(['assignedUser', 'contact', 'lead', 'deal']),
        ], 201);
    }

    public function updateTask(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $task = CrmTask::where('company_id', $companyId)->findOrFail($id);

        $task->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Task updated successfully',
            'data' => $task->fresh(['assignedUser', 'contact', 'lead', 'deal']),
        ]);
    }

    public function deleteTask(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $task = CrmTask::where('company_id', $companyId)->findOrFail($id);
        $task->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Task deleted successfully',
        ]);
    }
}
