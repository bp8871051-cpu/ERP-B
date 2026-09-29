<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {
    }

    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversations = $this->chatService->getConversations($user, $request->query('search'));

        return response()->json([
            'status' => 'success',
            'data' => $conversations,
        ]);
    }

    public function createConversation(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|in:direct,group',
            'title' => 'nullable|string|max:255',
            'participant_ids' => 'required|array|min:1',
            'initial_message' => 'nullable|string',
        ]);

        $conversation = $this->chatService->createConversation($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $conversation,
        ], 201);
    }

    public function messages(int $id, Request $request): JsonResponse
    {
        $messages = $this->chatService->getMessages($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $messages,
        ]);
    }

    public function sendMessage(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'body' => 'nullable|string',
            'type' => 'nullable|string|in:text,image,file,voice,system',
            'parent_id' => 'nullable|integer',
            'attachments' => 'nullable|array',
        ]);

        $message = $this->chatService->sendMessage($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $message,
        ], 201);
    }

    public function editMessage(int $id, Request $request): JsonResponse
    {
        $request->validate(['body' => 'required|string']);
        $message = $this->chatService->editMessage($id, $request->user(), $request->input('body'));

        return response()->json([
            'status' => 'success',
            'data' => $message,
        ]);
    }

    public function deleteMessage(int $id, Request $request): JsonResponse
    {
        $result = $this->chatService->deleteMessage($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function reaction(int $id, Request $request): JsonResponse
    {
        $request->validate(['reaction' => 'required|string']);
        $result = $this->chatService->toggleReaction($id, $request->user(), $request->input('reaction'));

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function pin(int $id, Request $request): JsonResponse
    {
        $result = $this->chatService->togglePin($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function read(int $id, Request $request): JsonResponse
    {
        $result = $this->chatService->markAsRead($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
