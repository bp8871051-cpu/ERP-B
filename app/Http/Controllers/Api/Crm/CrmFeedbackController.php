<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmFeedback;
use App\Services\FeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmFeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $paginator = $this->feedbackService->list($request->all(), $companyId);

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

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $metrics = $this->feedbackService->getDashboardMetrics($companyId);

        return response()->json([
            'status' => 'success',
            'data' => $metrics,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'contact_id' => 'nullable|exists:crm_contacts,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
            'rating' => 'required|integer|between:1,5',
            'category' => 'required|string|in:product,service,support,sales,delivery,billing,other',
            'feedback_text' => 'required|string',
            'status' => 'nullable|string|in:new,reviewed,assigned,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'resolution' => 'nullable|string',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $validated['company_id'] = $companyId;
        $validated['status'] = $validated['status'] ?? 'new';

        $feedback = CrmFeedback::create($validated);

        CrmAuditLog::log(
            'Feedback Created',
            CrmFeedback::class,
            $feedback->id,
            null,
            $feedback->toArray(),
            $companyId,
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Customer feedback recorded successfully',
            'data' => $feedback->load(['customer', 'contact', 'deal', 'assignedUser']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $feedback = CrmFeedback::where('company_id', $companyId)
            ->with(['customer', 'contact', 'deal', 'assignedUser'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $feedback,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $feedback = CrmFeedback::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'rating' => 'sometimes|required|integer|between:1,5',
            'category' => 'sometimes|required|string|in:product,service,support,sales,delivery,billing,other',
            'feedback_text' => 'sometimes|required|string',
            'status' => 'sometimes|required|string|in:new,reviewed,assigned,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'resolution' => 'nullable|string',
        ]);

        $feedback->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback updated successfully',
            'data' => $feedback->fresh(['customer', 'contact', 'deal', 'assignedUser']),
        ]);
    }

    public function resolve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|string',
            'status' => 'nullable|string|in:resolved,closed',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $feedback = CrmFeedback::where('company_id', $companyId)->findOrFail($id);

        $updated = $this->feedbackService->updateStatus(
            $feedback,
            $request->input('status', 'resolved'),
            $request->input('resolution'),
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback resolved successfully',
            'data' => $updated,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $feedback = CrmFeedback::where('company_id', $companyId)->findOrFail($id);

        $feedback->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback deleted successfully',
        ]);
    }
}
