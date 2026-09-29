<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    protected CrmService $crmService;

    public function __construct(CrmService $crmService)
    {
        $this->crmService = $crmService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $data = $this->crmService->getDashboardData($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
