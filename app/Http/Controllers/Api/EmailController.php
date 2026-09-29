<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function __construct(protected EmailService $emailService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->emailService->getEmails(
            $request->user(),
            $request->query('folder', 'inbox'),
            $request->query('search'),
            $request->query('label') ? (int) $request->query('label') : null
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $email = $this->emailService->getEmail($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $email,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required',
            'subject' => 'nullable|string',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'cc' => 'nullable',
            'is_draft' => 'nullable|boolean',
            'attachments' => 'nullable|array',
        ]);

        $email = $this->emailService->sendEmail($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $email,
        ], 201);
    }

    public function reply(int $id, Request $request): JsonResponse
    {
        $email = $this->emailService->replyEmail($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $email,
        ]);
    }

    public function forward(int $id, Request $request): JsonResponse
    {
        $email = $this->emailService->forwardEmail($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $email,
        ]);
    }

    public function toggleRead(int $id, Request $request): JsonResponse
    {
        $result = $this->emailService->toggleRead($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function toggleStar(int $id, Request $request): JsonResponse
    {
        $result = $this->emailService->toggleStar($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->emailService->deleteEmail($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
