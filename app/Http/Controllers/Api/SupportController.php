<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    protected SupportService $supportService;

    public function __construct(SupportService $supportService)
    {
        $this->supportService = $supportService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $data = $this->supportService->getDashboardData($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
