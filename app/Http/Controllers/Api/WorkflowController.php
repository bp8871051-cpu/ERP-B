<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(protected WorkflowService $workflowService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->workflowService->getDashboardData(
            $request->user(),
            $request->query('filter'),
            $request->query('module'),
            $request->query('search')
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'module' => 'required|string',
            'steps' => 'required|array|min:1',
            'trigger' => 'nullable|string',
        ]);

        $workflow = $this->workflowService->createWorkflow($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $workflow,
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $wf = \App\Models\Workflow::findOrFail($id);
        $wf->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $wf,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $wf = \App\Models\Workflow::findOrFail($id);
        $wf->delete();

        return response()->json([
            'status' => 'success',
            'data' => ['success' => true],
        ]);
    }

    public function createRequest(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'module' => 'nullable|string',
            'amount' => 'nullable|numeric',
            'workflow_id' => 'nullable|integer',
            'data' => 'nullable|array',
        ]);

        $req = $this->workflowService->createRequest($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $req,
        ], 201);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $req = $this->workflowService->approveRequest($id, $request->user(), $request->input('comments'));

        return response()->json([
            'status' => 'success',
            'data' => $req,
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $req = $this->workflowService->rejectRequest($id, $request->user(), $request->input('comments'));

        return response()->json([
            'status' => 'success',
            'data' => $req,
        ]);
    }

    public function requestChanges(int $id, Request $request): JsonResponse
    {
        $request->validate(['comments' => 'required|string']);
        $req = $this->workflowService->requestChanges($id, $request->user(), $request->input('comments'));

        return response()->json([
            'status' => 'success',
            'data' => $req,
        ]);
    }

    public function addComment(int $id, Request $request): JsonResponse
    {
        $request->validate(['comment' => 'required|string']);
        $comment = $this->workflowService->addComment($id, $request->user(), $request->input('comment'));

        return response()->json([
            'status' => 'success',
            'data' => $comment,
        ], 201);
    }
}
