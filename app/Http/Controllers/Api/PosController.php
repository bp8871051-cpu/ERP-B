<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $data = $this->posService->getDashboardData($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
