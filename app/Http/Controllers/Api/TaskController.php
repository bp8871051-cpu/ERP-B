<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(protected TaskService $taskService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->taskService->getTasks(
            $request->user(),
            $request->query('view', 'all'),
            $request->query('status'),
            $request->query('priority'),
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
            'title' => 'required|string|max:255',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'status' => 'nullable|string|in:todo,in_progress,review,completed,cancelled',
            'category' => 'nullable|string',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'assigned_to' => 'nullable|integer',
            'checklist' => 'nullable|array',
            'labels' => 'nullable|array',
        ]);

        $task = $this->taskService->createTask($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = \App\Models\Task::with([
            'assignedUser',
            'reporter',
            'checklists',
            'comments.user',
            'labels',
            'project',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $task = $this->taskService->updateTask($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->taskService->deleteTask($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function comments(int $id, Request $request): JsonResponse
    {
        $request->validate(['comment' => 'required|string']);
        $comment = $this->taskService->addComment($id, $request->user(), $request->input('comment'));

        return response()->json([
            'status' => 'success',
            'data' => $comment,
        ], 201);
    }

    public function toggleChecklist(int $taskId, int $checklistId): JsonResponse
    {
        $result = $this->taskService->toggleChecklist($taskId, $checklistId);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
