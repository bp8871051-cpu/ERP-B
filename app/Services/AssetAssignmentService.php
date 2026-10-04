<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetAssignmentHistory;
use App\Models\AssetAuditLog;
use App\Models\Employee;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AssetAssignmentService
{
    /**
     * List assignments
     */
    public function listAssignments(array $filters, int $companyId): LengthAwarePaginator
    {
        $query = AssetAssignment::with(['asset.category', 'employee.department', 'department', 'location', 'assigner'])
            ->where('company_id', $companyId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('asset', function ($q) use ($search) {
                $q->where('name', 'like', $search)->orWhere('asset_code', 'like', $search);
            });
        }

        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));
        return $query->latest('assigned_date')->paginate($perPage);
    }

    /**
     * Assign asset to employee/department/location
     */
    public function assignAsset(int $assetId, array $data, int $userId, int $companyId): AssetAssignment
    {
        return DB::transaction(function () use ($assetId, $data, $userId, $companyId) {
            $asset = Asset::where('company_id', $companyId)->findOrFail($assetId);

            if ($asset->status === 'Assigned') {
                throw new Exception("Asset {$asset->asset_code} is already assigned. Please return it before reassigning.");
            }
            if ($asset->status === 'Disposed' || $asset->status === 'Retired') {
                throw new Exception("Asset is {$asset->status} and cannot be assigned.");
            }

            $employeeId = $data['employee_id'] ?? null;
            $employee = $employeeId ? Employee::find($employeeId) : null;
            $departmentId = $data['department_id'] ?? ($employee?->department_id ?? $asset->department_id);
            $locationId = $data['location_id'] ?? $asset->location_id;
            $assignedDate = $data['assigned_date'] ?? Carbon::today();
            $expectedReturn = $data['expected_return_date'] ?? null;
            $condition = $data['condition'] ?? 'Good';

            $assignment = AssetAssignment::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'employee_id' => $employeeId,
                'department_id' => $departmentId,
                'location_id' => $locationId,
                'assigned_by' => $userId,
                'assigned_date' => $assignedDate,
                'expected_return_date' => $expectedReturn,
                'condition' => $condition,
                'status' => 'Assigned',
                'notes' => $data['notes'] ?? null,
            ]);

            // Update Asset status & holder
            $asset->update([
                'status' => 'Assigned',
                'assigned_to' => $employeeId,
                'department_id' => $departmentId,
                'location_id' => $locationId,
                'condition' => $condition,
            ]);

            // Record in assignment history ledger
            AssetAssignmentHistory::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'previous_holder' => 'Unassigned (Inventory)',
                'new_holder' => $employee?->first_name ? ($employee->first_name . ' ' . $employee->last_name) : 'Departmental Allocation',
                'previous_location' => $asset->location?->name ?? 'Central Storage',
                'new_location' => $assignment->location?->name ?? 'Field Site',
                'assigned_date' => $assignedDate,
                'condition' => $condition,
                'assigned_by_name' => auth()->user()?->name ?? 'Admin',
                'notes' => $data['notes'] ?? 'Assigned to workforce member',
            ]);

            // Audit Log
            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'user_id' => $userId,
                'action' => 'assigned',
                'new_values' => $assignment->toArray(),
                'ip_address' => request()->ip(),
            ]);

            return $assignment->load(['asset', 'employee', 'department', 'location']);
        });
    }

    /**
     * Return asset back to inventory or flag for maintenance if damaged
     */
    public function returnAsset(int $assetId, array $data, int $userId, int $companyId): AssetAssignment
    {
        return DB::transaction(function () use ($assetId, $data, $userId, $companyId) {
            $asset = Asset::where('company_id', $companyId)->findOrFail($assetId);
            $assignment = AssetAssignment::where('asset_id', $asset->id)
                ->where('status', 'Assigned')
                ->latest()
                ->first();

            if (!$assignment) {
                throw new Exception("No active assignment found for asset {$asset->asset_code}.");
            }

            $returnDate = $data['returned_date'] ?? Carbon::today();
            $condition = $data['condition'] ?? 'Good'; // Good, Fair, Damaged
            $nextStatus = ($condition === 'Damaged') ? 'Damaged' : 'Available';

            $assignment->update([
                'actual_return_date' => $returnDate,
                'condition' => $condition,
                'status' => 'Returned',
                'notes' => $data['notes'] ?? $assignment->notes,
            ]);

            $asset->update([
                'status' => $nextStatus,
                'assigned_to' => null,
                'condition' => $condition,
            ]);

            // Record return in history
            $latestHist = AssetAssignmentHistory::where('asset_id', $asset->id)->latest()->first();
            if ($latestHist) {
                $latestHist->update([
                    'returned_date' => $returnDate,
                    'condition' => $condition,
                ]);
            }

            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'user_id' => $userId,
                'action' => 'returned',
                'old_values' => ['condition' => 'Good', 'status' => 'Assigned'],
                'new_values' => ['condition' => $condition, 'status' => $nextStatus],
                'ip_address' => request()->ip(),
            ]);

            return $assignment->fresh(['asset', 'employee']);
        });
    }
}
