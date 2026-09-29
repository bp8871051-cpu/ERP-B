<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(protected NoteService $noteService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->noteService->getNotes(
            $request->user(),
            $request->query('filter'),
            $request->query('tag'),
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
            'content' => 'nullable|string',
            'checklist' => 'nullable|array',
            'color' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_pinned' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
        ]);

        $note = $this->noteService->createNote($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $note,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $note = \App\Models\Note::with(['tags', 'attachments', 'user'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $note,
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $note = $this->noteService->updateNote($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $note,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->noteService->deleteNote($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function togglePin(int $id, Request $request): JsonResponse
    {
        $result = $this->noteService->togglePin($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function toggleFavorite(int $id, Request $request): JsonResponse
    {
        $result = $this->noteService->toggleFavorite($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function toggleArchive(int $id, Request $request): JsonResponse
    {
        $result = $this->noteService->toggleArchive($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
