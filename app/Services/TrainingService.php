<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeTraining;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use Carbon\Carbon;

class TrainingService
{
    /**
     * List training programs with sessions and enrolled employees
     */
    public function listPrograms(?int $companyId = null)
    {
        return TrainingProgram::with(['sessions', 'enrollments.employee.department'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->latest('start_date')
            ->get();
    }

    /**
     * Create training program
     */
    public function createProgram(array $data, ?int $companyId = null): TrainingProgram
    {
        $companyId = $companyId ?? ($data['company_id'] ?? 1);

        $program = TrainingProgram::create([
            'company_id' => $companyId,
            'title' => $data['title'],
            'trainer' => $data['trainer'] ?? 'Senior Corporate Trainer',
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? Carbon::today(),
            'end_date' => $data['end_date'] ?? Carbon::today()->addDays(5),
            'duration_hours' => $data['duration_hours'] ?? 16,
            'location' => $data['location'] ?? 'Auditorium A / Virtual',
            'cost' => $data['cost'] ?? 0,
            'status' => $data['status'] ?? 'planned',
        ]);

        if (!empty($data['sessions']) && is_array($data['sessions'])) {
            foreach ($data['sessions'] as $session) {
                $program->sessions()->create([
                    'title' => $session['title'],
                    'session_date' => $session['session_date'] ?? $program->start_date,
                    'start_time' => $session['start_time'] ?? '10:00:00',
                    'end_time' => $session['end_time'] ?? '13:00:00',
                    'room' => $session['room'] ?? 'Virtual Room 1',
                ]);
            }
        }

        return $program->fresh(['sessions']);
    }

    /**
     * Enroll employee in training
     */
    public function enrollEmployee(int $programId, int $employeeId): EmployeeTraining
    {
        $program = TrainingProgram::findOrFail($programId);
        $employee = Employee::findOrFail($employeeId);

        return EmployeeTraining::updateOrCreate(
            [
                'program_id' => $program->id,
                'employee_id' => $employee->id,
            ],
            [
                'company_id' => $program->company_id,
                'enrollment_date' => Carbon::today(),
                'completion_status' => 'enrolled',
            ]
        );
    }
}
