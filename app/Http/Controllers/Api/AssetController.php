<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AssetAnalyticsService;
use App\Services\AssetAssignmentService;
use App\Services\AssetDepreciationService;
use App\Services\AssetDisposalService;
use App\Services\AssetMaintenanceService;
use App\Services\AssetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    protected AssetService $assetService;
    protected AssetAssignmentService $assignmentService;
    protected AssetDepreciationService $depreciationService;
    protected AssetMaintenanceService $maintenanceService;
    protected AssetDisposalService $disposalService;
    protected AssetAnalyticsService $analyticsService;

    public function __construct(
        AssetService $assetService,
        AssetAssignmentService $assignmentService,
        AssetDepreciationService $depreciationService,
        AssetMaintenanceService $maintenanceService,
        AssetDisposalService $disposalService,
        AssetAnalyticsService $analyticsService
    ) {
        $this->assetService = $assetService;
        $this->assignmentService = $assignmentService;
        $this->depreciationService = $depreciationService;
        $this->maintenanceService = $maintenanceService;
        $this->disposalService = $disposalService;
        $this->analyticsService = $analyticsService;
    }

    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?: 1);
    }

    /**
     * Asset Dashboard Overview
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $data = $this->analyticsService->getDashboardMetrics($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * List Assets with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $assets = $this->assetService->listAssets($request->all(), $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $assets,
        ]);
    }

    /**
     * Create Asset
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        $request->validate([
            'name' => 'required|string|max:255',
            'purchase_cost' => 'required|numeric|min:0',
        ]);

        try {
            $asset = $this->assetService->createAsset($request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Asset registered successfully.',
                'data' => $asset,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show Asset Details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $asset = $this->assetService->getAssetDetails($id, $companyId);
        $metrics = $this->depreciationService->calculateDepreciationMetrics($asset);

        return response()->json([
            'status' => 'success',
            'data' => [
                'asset' => $asset,
                'depreciation_metrics' => $metrics,
            ],
        ]);
    }

    /**
     * Update Asset
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $asset = $this->assetService->updateAsset($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Asset updated successfully.',
                'data' => $asset,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete Asset
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $this->assetService->deleteAsset($id, $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Asset deleted successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Assign Asset
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $assignment = $this->assignmentService->assignAsset($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Asset assigned successfully.',
                'data' => $assignment,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Return Asset
     */
    public function returnAsset(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $assignment = $this->assignmentService->returnAsset($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Asset returned successfully.',
                'data' => $assignment,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Asset Assignments List
     */
    public function assignments(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $assignments = $this->assignmentService->listAssignments($request->all(), $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $assignments,
        ]);
    }

    /**
     * Asset Depreciation Overview & Schedule
     */
    public function depreciation(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $assetId = $request->input('asset_id');

        if ($assetId) {
            $type = $request->input('schedule_type', 'yearly');
            $schedule = $this->depreciationService->generateSchedule((int) $assetId, $type, $companyId);
            return response()->json([
                'status' => 'success',
                'data' => $schedule,
            ]);
        }

        // Return depreciation table for all active assets
        $assets = $this->assetService->listAssets(array_merge($request->all(), ['per_page' => 50]), $companyId);
        $tableData = collect($assets->items())->map(function ($asset) {
            $metrics = $this->depreciationService->calculateDepreciationMetrics($asset);
            return [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'name' => $asset->name,
                'category' => $asset->category?->name ?? 'General',
                'purchase_cost' => $metrics['purchase_cost'],
                'salvage_value' => $metrics['salvage_value'],
                'useful_life' => $metrics['useful_life_years'],
                'method' => ucwords(str_replace('_', ' ', $metrics['depreciation_method'])),
                'monthly_depreciation' => $metrics['monthly_depreciation'],
                'accumulated_depreciation' => $metrics['accumulated_depreciation'],
                'current_book_value' => $metrics['current_book_value'],
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'table' => $tableData,
                'pagination' => [
                    'current_page' => $assets->currentPage(),
                    'total' => $assets->total(),
                    'per_page' => $assets->perPage(),
                ],
            ],
        ]);
    }

    /**
     * Run batch depreciation calculation
     */
    public function calculateDepreciation(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $result = $this->depreciationService->runBatchDepreciation($companyId, $userId);
            return response()->json([
                'status' => 'success',
                'message' => 'Depreciation calculated and posted successfully.',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List Maintenance tickets
     */
    public function maintenance(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $tickets = $this->maintenanceService->listMaintenance($request->all(), $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $tickets,
        ]);
    }

    /**
     * Store Maintenance Ticket
     */
    public function storeMaintenance(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        $request->validate([
            'asset_id' => 'required|integer',
            'issue' => 'required|string',
        ]);

        try {
            $ticket = $this->maintenanceService->createTicket($request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance ticket created and asset flagged.',
                'data' => $ticket,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update Maintenance Ticket
     */
    public function updateMaintenance(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $ticket = $this->maintenanceService->updateTicket($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance ticket updated.',
                'data' => $ticket,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List Disposals
     */
    public function disposals(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $disposals = $this->disposalService->listDisposals($request->all(), $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $disposals,
        ]);
    }

    /**
     * Request Disposal
     */
    public function storeDisposal(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        $request->validate([
            'asset_id' => 'required|integer',
            'reason' => 'required|string',
        ]);

        try {
            $disposal = $this->disposalService->requestDisposal($request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Asset disposal request created.',
                'data' => $disposal,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve Disposal
     */
    public function approveDisposal(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $disposal = $this->disposalService->approveDisposal($id, $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Disposal approved and asset marked as Disposed.',
                'data' => $disposal,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Asset Analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $analytics = $this->analyticsService->getAdvancedAnalytics($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $analytics,
        ]);
    }

    /**
     * Meta / Dropdown Lookups (Categories, Locations, Departments, Employees)
     */
    public function lookups(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $categories = AssetCategory::where('company_id', $companyId)->where('is_active', true)->get();
        $locations = AssetLocation::where('company_id', $companyId)->where('is_active', true)->get();
        $departments = Department::where('company_id', $companyId)->get();
        $employees = Employee::where('company_id', $companyId)->where('status', 'active')->select('id', 'first_name', 'last_name', 'employee_code', 'department_id')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => $categories,
                'locations' => $locations,
                'departments' => $departments,
                'employees' => $employees,
            ],
        ]);
    }
}
