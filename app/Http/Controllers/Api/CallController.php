<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function __construct(protected CallService $callService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->callService->getDashboardData(
            $request->user(),
            $request->query('filter'),
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
            'receiver_id' => 'nullable|integer',
            'type' => 'nullable|string|in:voice,video',
            'provider' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $call = $this->callService->initiateCall($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $call,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $call = \App\Models\Call::with(['caller', 'receiver', 'participants.user', 'logs'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $call,
        ]);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $call = $this->callService->updateCall($id, $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $call,
        ]);
    }
}
