<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QcInspection;
use App\Models\QcNcr;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\PurchaseOrder;
use App\Models\SystemAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class QualityControlController extends Controller
{
    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    /**
     * Get Quality Control Dashboard Metrics and Sections
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $inspections = QcInspection::where('company_id', $companyId)
            ->with(['product', 'warehouse', 'inspector', 'purchaseOrder'])
            ->latest()
            ->get();

        $totalInspections = $inspections->count();
        $pendingInspections = $inspections->where('status', 'pending')->count();
        $inProgressInspections = $inspections->where('status', 'in_progress')->count();
        $passedInspections = $inspections->where('status', 'passed')->count();
        $failedInspections = $inspections->where('status', 'failed')->count();
        $qualityHoldItems = $inspections->where('status', 'quality_hold')->count();

        // NCRs and Defect reports
        $ncrs = QcNcr::where('company_id', $companyId)
            ->with(['product', 'reporter', 'assignee', 'inspection'])
            ->latest()
            ->get();

        $openNcrs = $ncrs->whereIn('status', ['open', 'under_review', 'capa_pending'])->values();
        $pendingCapa = $ncrs->where('status', 'capa_pending')->values();

        // Sections
        $pendingQueue = $inspections->whereIn('status', ['pending', 'in_progress'])->values();
        $incomingInspections = $inspections->where('inspection_type', 'incoming_material')->values();
        $recentResults = $inspections->whereIn('status', ['passed', 'failed', 'quality_hold'])->take(15)->values();
        $failedHeldItems = $inspections->whereIn('status', ['failed', 'quality_hold'])->values();

        // Reinspection Schedule
        $reinspectionSchedule = $inspections->filter(function ($i) {
            return !empty($i->reinspection_date) && Carbon::parse($i->reinspection_date)->gte(Carbon::today());
        })->values();

        // Quality Trends Chart (Last 6 Months Pass vs Fail/Hold)
        $monthlyTrends = [
            'Passed' => $passedInspections,
            'Failed' => $failedInspections,
            'Quality Hold' => $qualityHoldItems,
            'Pending Review' => $pendingInspections + $inProgressInspections,
        ];

        // Recent Activities
        $activities = [];
        foreach ($inspections->take(6) as $insp) {
            $activities[] = [
                'id' => 'qc_' . $insp->id,
                'title' => 'Inspection ' . $insp->inspection_number . ' (' . ucfirst($insp->status) . ')',
                'description' => ($insp->product?->name ?? 'Material') . ' - Lot: ' . ($insp->lot_number ?? 'N/A'),
                'time' => $insp->updated_at?->diffForHumans() ?: 'Recently',
                'type' => $insp->status,
            ];
        }

        // Available lookups
        $availableProducts = Product::where('company_id', $companyId)->select('id', 'name', 'sku')->get();
        $availableWarehouses = Warehouse::where('company_id', $companyId)->select('id', 'name')->get();
        $availablePurchaseOrders = PurchaseOrder::where('company_id', $companyId)->select('id', 'po_number', 'supplier_id')->limit(30)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'cards' => [
                    'total_inspection_requests' => $totalInspections,
                    'pending_inspections' => $pendingInspections,
                    'inspections_in_progress' => $inProgressInspections,
                    'passed_inspections' => $passedInspections,
                    'failed_inspections' => $failedInspections,
                    'items_on_quality_hold' => $qualityHoldItems,
                    'open_defects_ncrs' => $openNcrs->count(),
                    'pending_corrective_actions' => $pendingCapa->count(),
                ],
                'sections' => [
                    'pending_inspection_queue' => $pendingQueue,
                    'incoming_material_inspections' => $incomingInspections,
                    'recent_inspection_results' => $recentResults,
                    'failed_held_items' => $failedHeldItems,
                    'open_defects' => $openNcrs,
                    'corrective_action_tracking' => $pendingCapa,
                    'reinspection_schedule' => $reinspectionSchedule,
                    'quality_trends_chart' => $monthlyTrends,
                    'recent_activities' => $activities,
                ],
                'lookups' => [
                    'products' => $availableProducts,
                    'warehouses' => $availableWarehouses,
                    'purchase_orders' => $availablePurchaseOrders,
                ],
            ],
        ]);
    }

    /**
     * Quick Action: Start Inspection
     */
    public function startInspection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inspection_id' => 'required|integer|exists:qc_inspections,id',
        ]);

        $inspection = QcInspection::findOrFail($validated['inspection_id']);
        $inspection->status = 'in_progress';
        $inspection->inspector_id = $request->user()->id;
        $inspection->save();

        SystemAuditLog::log('qc', 'start_inspection', (string) $inspection->id, null, $inspection->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => "Inspection {$inspection->inspection_number} status set to In Progress.",
            'data' => $inspection,
        ]);
    }

    /**
     * Quick Action: Record Inspection Measurements
     */
    public function recordMeasurements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inspection_id' => 'required|integer|exists:qc_inspections,id',
            'measurements' => 'required|array',
            'checklist' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $inspection = QcInspection::findOrFail($validated['inspection_id']);
        $inspection->measurements = $validated['measurements'];
        if (isset($validated['checklist'])) {
            $inspection->checklist = $validated['checklist'];
        }
        if (isset($validated['notes'])) {
            $inspection->notes = $validated['notes'];
        }
        $inspection->save();

        SystemAuditLog::log('qc', 'record_measurements', (string) $inspection->id, null, $validated['measurements'], $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => "Test measurements recorded for {$inspection->inspection_number}.",
            'data' => $inspection,
        ]);
    }

    /**
     * Quick Action: Authorized Decision (Mark Pass / Fail / Hold)
     */
    public function submitDecision(Request $request): JsonResponse
    {
        // Enforce restriction: only authorized QC users may record final inspection decisions
        $user = $request->user();
        if (!in_array($user->role, ['Quality Control', 'Super Admin', 'Admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Only authorized Quality Control personnel can finalize inspection decisions.',
            ], 403);
        }

        $validated = $request->validate([
            'inspection_id' => 'required|integer|exists:qc_inspections,id',
            'decision' => 'required|string|in:passed,failed,quality_hold',
            'notes' => 'required|string',
            'reinspection_date' => 'nullable|date',
            'defect_count' => 'nullable|integer|min:0',
        ]);

        $inspection = QcInspection::findOrFail($validated['inspection_id']);
        $inspection->status = $validated['decision'];
        $inspection->decision_date = Carbon::now();
        $inspection->notes = $validated['notes'];
        $inspection->defect_count = $validated['defect_count'] ?? 0;
        if (!empty($validated['reinspection_date'])) {
            $inspection->reinspection_date = $validated['reinspection_date'];
        }
        $inspection->save();

        SystemAuditLog::log('qc', 'record_inspection_decision', (string) $inspection->id, null, [
            'decision' => $validated['decision'],
            'notes' => $validated['notes'],
        ], $this->getCompanyId($request), $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Inspection {$inspection->inspection_number} finalized as " . strtoupper($validated['decision']) . ".",
            'data' => $inspection,
        ]);
    }

    /**
     * Quick Action: Create Defect / NCR Report
     */
    public function createNcr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inspection_id' => 'nullable|integer|exists:qc_inspections,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|string|in:minor,major,critical',
            'root_cause' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'action_due_date' => 'nullable|date',
        ]);

        $ncrNo = 'NCR-' . date('Y') . '-' . str_pad((string) (QcNcr::count() + 1), 4, '0', STR_PAD_LEFT);

        $ncr = QcNcr::create([
            'company_id' => $this->getCompanyId($request),
            'ncr_number' => $ncrNo,
            'inspection_id' => $validated['inspection_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'reported_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'severity' => $validated['severity'],
            'status' => !empty($validated['corrective_action']) ? 'capa_pending' : 'open',
            'root_cause' => $validated['root_cause'] ?? null,
            'corrective_action' => $validated['corrective_action'] ?? null,
            'action_assigned_to' => $request->user()->id,
            'action_due_date' => $validated['action_due_date'] ?? null,
        ]);

        // If linked to an inspection, ensure inspection is put on quality_hold or failed
        if (!empty($validated['inspection_id'])) {
            QcInspection::where('id', $validated['inspection_id'])->update([
                'status' => 'quality_hold',
            ]);
        }

        SystemAuditLog::log('qc', 'create_ncr', (string) $ncr->id, null, $ncr->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => "Non-Conformance Report {$ncrNo} logged with severity {$validated['severity']}.",
            'data' => $ncr,
        ]);
    }

    /**
     * Quick Action: Schedule Reinspection
     */
    public function scheduleReinspection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inspection_id' => 'required|integer|exists:qc_inspections,id',
            'reinspection_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        $inspection = QcInspection::findOrFail($validated['inspection_id']);
        $inspection->reinspection_date = $validated['reinspection_date'];
        if (!empty($validated['notes'])) {
            $inspection->notes = ($inspection->notes ? $inspection->notes . ' | Reinspection note: ' : 'Reinspection note: ') . $validated['notes'];
        }
        $inspection->save();

        SystemAuditLog::log('qc', 'schedule_reinspection', (string) $inspection->id, null, [
            'reinspection_date' => $validated['reinspection_date'],
        ], $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => "Reinspection for {$inspection->inspection_number} scheduled for {$validated['reinspection_date']}.",
            'data' => $inspection,
        ]);
    }
}
