<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Services\CustomerAnalyticsService;
use App\Services\CustomerSegmentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmAnalyticsController extends Controller
{
    protected CustomerAnalyticsService $analyticsService;
    protected CustomerSegmentationService $segmentationService;

    public function __construct(CustomerAnalyticsService $analyticsService, CustomerSegmentationService $segmentationService)
    {
        $this->analyticsService = $analyticsService;
        $this->segmentationService = $segmentationService;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $data = $this->analyticsService->getAnalytics($companyId);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function evaluateSegments(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
        $segments = $this->segmentationService->evaluateSegments($companyId);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer segmentation evaluated successfully',
            'data' => $segments,
        ]);
    }
}
