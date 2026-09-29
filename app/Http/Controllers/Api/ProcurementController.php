<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProcurementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    protected ProcurementService $procurementService;

    public function __construct(ProcurementService $procurementService)
    {
        $this->procurementService = $procurementService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $data = $this->procurementService->getDashboardData($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
