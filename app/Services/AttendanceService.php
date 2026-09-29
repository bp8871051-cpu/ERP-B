<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * Employee Check-In
     */
    public function checkIn(int $employeeId, ?int $companyId = null, array $metadata = []): Attendance
    {
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $employee = Employee::findOrFail($employeeId);
        $companyId = $companyId ?? $employee->company_id;

        // Check duplicate
        $existing = Attendance::where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('date', $today)
            ->first();

        if ($existing && $existing->check_in) {
            throw ValidationException::withMessages([
                'check_in' => 'Attendance has already been marked as checked-in for today.',
            ]);
        }

        // Standard shift start: 09:00 AM (grace period until 09:15 AM)
        $shiftStart = Carbon::today()->setTime(9, 0, 0);
        $lateThreshold = Carbon::today()->setTime(9, 15, 0);
        $isLate = $now->greaterThan($lateThreshold);
        $lateMinutes = $isLate ? max(0, $now->diffInMinutes($shiftStart)) : 0;
        $status = $isLate ? 'late' : 'present';

        return DB::transaction(function () use ($companyId, $employeeId, $today, $now, $status, $lateMinutes, $metadata, $existing) {
            if ($existing) {
                $existing->update([
                    'check_in' => $now->format('H:i:s'),
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                ]);
                $attendance = $existing;
            } else {
                $attendance = Attendance::create([
                    'company_id' => $companyId,
                    'employee_id' => $employeeId,
                    'date' => $today,
                    'check_in' => $now->format('H:i:s'),
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'working_hours' => 0,
                    'working_minutes' => 0,
                    'overtime_minutes' => 0,
                ]);
            }

            // Create check-in log
            AttendanceLog::create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'attendance_id' => $attendance->id,
                'type' => 'in',
                'logged_at' => $now,
                'device' => $metadata['device'] ?? 'Web Portal',
                'ip_address' => request()->ip(),
            ]);

            return $attendance->fresh(['employee', 'logs']);
        });
    }

    /**
     * Employee Check-Out
     */
    public function checkOut(int $employeeId, ?int $companyId = null, array $metadata = []): Attendance
    {
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $employee = Employee::findOrFail($employeeId);
        $companyId = $companyId ?? $employee->company_id;

        $attendance = Attendance::where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            throw ValidationException::withMessages([
                'check_out' => 'No active check-in record found for today. Please check in first.',
            ]);
        }

        return DB::transaction(function () use ($attendance, $now, $metadata, $companyId, $employeeId) {
            $checkInTime = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $attendance->check_in);
            $totalMinutes = max(0, $now->diffInMinutes($checkInTime));
            $workingHours = round($totalMinutes / 60, 2);

            // Standard workday: 8 hours (480 minutes)
            $standardMinutes = 480;
            $overtimeMinutes = max(0, $totalMinutes - $standardMinutes);

            // Half-day if worked less than 4.5 hours (270 minutes)
            $status = $attendance->status;
            if ($totalMinutes < 270) {
                $status = 'half_day';
            }

            $attendance->update([
                'check_out' => $now->format('H:i:s'),
                'working_minutes' => $totalMinutes,
                'working_hours' => $workingHours,
                'overtime_minutes' => $overtimeMinutes,
                'status' => $status,
            ]);

            AttendanceLog::create([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'attendance_id' => $attendance->id,
                'type' => 'out',
                'logged_at' => $now,
                'device' => $metadata['device'] ?? 'Web Portal',
                'ip_address' => request()->ip(),
            ]);

            return $attendance->fresh(['employee', 'logs']);
        });
    }

    /**
     * List attendance records with filtering
     */
    public function listAttendance(array $filters = [], ?int $companyId = null)
    {
        $query = Attendance::with(['employee.department', 'employee.designation', 'approver']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn($e) => $e->where('department_id', $filters['department_id']));
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->where('date', $filters['date']);
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
        }

        $sortField = $filters['sort_by'] ?? 'date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
