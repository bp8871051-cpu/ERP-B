<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Services\CrmDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmDashboardController extends Controller
{
    protected CrmDashboardService $dashboardService;

    public function __construct(CrmDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $data = $this->dashboardService->getDashboardMetrics($companyId);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
