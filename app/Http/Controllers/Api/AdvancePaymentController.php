<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvancePayment;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvancePaymentController extends Controller
{
    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);
    }

    /**
     * List all advance payments with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $query = AdvancePayment::where('company_id', $companyId)
            ->with(['project', 'employee', 'vendor', 'creator', 'approver']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('advance_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('project', fn($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('employee', fn($e) => $e->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                    ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$search}%"));
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Type Filter (employees, vendor, project)
        if ($type = $request->input('type')) {
            $query->where('advance_type', $type);
        }

        // Project Filter
        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        // Approval Level Filter
        if ($level = $request->input('approval_level')) {
            $query->where('approval_level', $level);
        }

        // Date range
        if ($dateFrom = $request->input('date_from')) {
            $query->where('advance_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('advance_date', '<=', $dateTo);
        }

        $perPage = min(100, max(5, (int) $request->input('per_page', 15)));
        $advances = $query->latest('advance_date')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $advances,
        ]);
    }

    /**
     * Store new advance payment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'advance_type' => 'required|in:employees,vendor,project',
            'project_id' => 'nullable|exists:projects,id',
            'employee_id' => 'nullable|exists:employees,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'project_amount' => 'required|numeric|min:0.01',
            'advance_date' => 'required|date',
            'expected_settlement_date' => 'nullable|date',
            'purpose' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,settled,rejected',
            'approval_level' => 'nullable|in:manager,finance,director,completed',
        ]);

        $companyId = $this->getCompanyId($request);
        $validated['company_id'] = $companyId;
        $validated['created_by'] = $request->user()?->id;

        $advance = AdvancePayment::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Advance payment record created successfully.',
            'data' => $advance->load(['project', 'employee', 'vendor']),
        ], 201);
    }

    /**
     * Show advance payment details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $advance = AdvancePayment::where('company_id', $companyId)
            ->with(['project', 'employee', 'vendor', 'creator', 'approver'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $advance,
        ]);
    }

    /**
     * Update advance payment
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $advance = AdvancePayment::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'advance_type' => 'sometimes|required|in:employees,vendor,project',
            'project_id' => 'nullable|exists:projects,id',
            'employee_id' => 'nullable|exists:employees,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'project_amount' => 'sometimes|required|numeric|min:0.01',
            'advance_date' => 'sometimes|required|date',
            'expected_settlement_date' => 'nullable|date',
            'purpose' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,settled,rejected',
            'approval_level' => 'nullable|in:manager,finance,director,completed',
        ]);

        $advance->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Advance payment updated successfully.',
            'data' => $advance->load(['project', 'employee', 'vendor']),
        ]);
    }

    /**
     * Approve advance payment
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $advance = AdvancePayment::where('company_id', $companyId)->findOrFail($id);

        $advance->status = 'approved';
        $advance->approval_level = 'completed';
        $advance->approved_by = $request->user()?->id;
        $advance->approved_at = Carbon::now();
        $advance->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Advance payment approved successfully.',
            'data' => $advance,
        ]);
    }

    /**
     * Delete advance payment (Soft delete)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $advance = AdvancePayment::where('company_id', $companyId)->findOrFail($id);
        $advance->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Advance payment deleted successfully.',
        ]);
    }
}

