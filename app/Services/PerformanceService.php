<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Models\PerformanceReview;
use Carbon\Carbon;

class PerformanceService
{
    /**
     * Get active performance cycles with goals and reviews
     */
    public function getCyclesOverview(?int $companyId = null)
    {
        $cycles = PerformanceCycle::withCount(['goals', 'reviews'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->latest('year')
            ->get();

        $activeCycle = $cycles->where('status', 'active')->first() ?? $cycles->first();

        $reviews = [];
        if ($activeCycle) {
            $reviews = PerformanceReview::with(['employee.department', 'employee.designation', 'reviewer'])
                ->where('cycle_id', $activeCycle->id)
                ->get();
        }

        return [
            'cycles' => $cycles,
            'active_cycle' => $activeCycle,
            'reviews' => $reviews,
        ];
    }

    /**
     * Submit or complete an employee review
     */
    public function submitReview(array $data, ?int $companyId = null): PerformanceReview
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $companyId = $companyId ?? $employee->company_id;

        $selfRating = isset($data['self_rating']) ? (int) $data['self_rating'] : null;
        $managerRating = isset($data['manager_rating']) ? (int) $data['manager_rating'] : null;

        // Weighted final rating (40% self, 60% manager)
        $finalRating = $managerRating ?? $selfRating ?? 3;
        $finalScore = round(($selfRating * 0.40) + ($managerRating * 0.60), 2);

        return PerformanceReview::updateOrCreate(
            [
                'cycle_id' => $data['cycle_id'],
                'employee_id' => $employee->id,
            ],
            [
                'company_id' => $companyId,
                'reviewer_id' => $data['reviewer_id'] ?? auth()->id(),
                'self_rating' => $selfRating,
                'self_comments' => $data['self_comments'] ?? null,
                'manager_rating' => $managerRating,
                'manager_comments' => $data['manager_comments'] ?? null,
                'final_rating' => $finalRating,
                'final_score' => $finalScore > 0 ? $finalScore : $finalRating,
                'status' => $data['status'] ?? 'completed',
            ]
        );
    }
}
