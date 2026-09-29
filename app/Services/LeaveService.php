<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    /**
     * Submit a leave request
     */
    public function submitRequest(array $data, ?int $companyId = null): LeaveRequest
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $companyId = $companyId ?? $employee->company_id;

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        if ($endDate->lessThan($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date cannot be earlier than start date.',
            ]);
        }

        $daysCount = $startDate->diffInDays($endDate) + 1;
        $currentYear = (int) $startDate->format('Y');

        // Check leave balance
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', $currentYear)
            ->first();

        $leaveType = LeaveType::find($data['leave_type_id']);

        if ($balance && $leaveType && $leaveType->is_paid) {
            if ($balance->remaining < $daysCount) {
                throw ValidationException::withMessages([
                    'days_count' => "Insufficient leave balance. You have {$balance->remaining} days remaining, but requested {$daysCount} days.",
                ]);
            }
        }

        return LeaveRequest::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => $daysCount,
            'reason' => $data['reason'] ?? null,
            'attachment' => $data['attachment'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Approve leave request
     */
    public function approveRequest(LeaveRequest $request, ?int $approverId = null): LeaveRequest
    {
        return DB::transaction(function () use ($request, $approverId) {
            $currentYear = (int) $request->start_date->format('Y');

            // 1. Update Leave Balance
            $balance = LeaveBalance::where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $currentYear)
                ->first();

            if ($balance) {
                $newUsed = $balance->used + $request->days_count;
                $newRemaining = max(0, $balance->allocated - $newUsed);
                $balance->update([
                    'used' => $newUsed,
                    'remaining' => $newRemaining,
                ]);
            }

            // 2. Mark attendance records for the leave period
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            foreach ($period as $date) {
                Attendance::updateOrCreate(
                    [
                        'company_id' => $request->company_id,
                        'employee_id' => $request->employee_id,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'status' => 'leave',
                        'working_hours' => 0,
                        'working_minutes' => 0,
                        'late_minutes' => 0,
                        'overtime_minutes' => 0,
                        'notes' => 'Approved Leave: ' . ($request->leaveType?->name ?? 'General Leave'),
                    ]
                );
            }

            // 3. Mark request as approved
            $request->update([
                'status' => 'approved',
                'approved_by' => $approverId ?? auth()->id(),
                'approved_at' => Carbon::now(),
            ]);

            return $request->fresh(['employee', 'leaveType', 'approver']);
        });
    }

    /**
     * Reject leave request
     */
    public function rejectRequest(LeaveRequest $request, ?string $reason = null, ?int $approverId = null): LeaveRequest
    {
        $request->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $approverId ?? auth()->id(),
            'approved_at' => Carbon::now(),
        ]);

        return $request->fresh(['employee', 'leaveType', 'approver']);
    }

    /**
     * Get employee leave balance overview
     */
    public function getEmployeeBalances(int $employeeId, ?int $year = null)
    {
        $year = $year ?? (int) Carbon::today()->format('Y');

        return LeaveBalance::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get();
    }
}
