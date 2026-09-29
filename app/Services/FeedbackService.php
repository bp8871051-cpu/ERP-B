<?php

namespace App\Services;

use App\Models\CrmAuditLog;
use App\Models\CrmFeedback;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeedbackService
{
    public function list(array $params, ?int $companyId = null): LengthAwarePaginator
    {
        $query = CrmFeedback::query()
            ->with(['customer', 'contact', 'deal', 'assignedUser']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('feedback_text', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%")
                  ->orWhere('resolution', 'like', "%{$s}%");
            });
        }

        if (!empty($params['rating'])) {
            $query->where('rating', $params['rating']);
        }

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $perPage = max(5, min(100, (int) ($params['per_page'] ?? 15)));
        return $query->latest()->paginate($perPage);
    }

    public function getDashboardMetrics(?int $companyId = null): array
    {
        $query = CrmFeedback::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $totalFeedback = $query->count();
        $avgRating = $totalFeedback > 0 ? round((float) $query->avg('rating'), 1) : 0.0;

        $positive = (clone $query)->whereIn('rating', [4, 5])->count();
        $neutral = (clone $query)->where('rating', 3)->count();
        $negative = (clone $query)->whereIn('rating', [1, 2])->count();

        $openIssues = (clone $query)->whereIn('status', ['new', 'reviewed', 'assigned'])->count();
        $resolved = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();
        $resolutionRate = $totalFeedback > 0 ? round(($resolved / $totalFeedback) * 100, 1) : 0.0;

        // Rating distribution (1 to 5 stars)
        $ratingDistribution = [];
        for ($r = 5; $r >= 1; $r--) {
            $cnt = (clone $query)->where('rating', $r)->count();
            $ratingDistribution[] = [
                'rating' => "{$r} Stars",
                'stars' => $r,
                'count' => $cnt,
                'percentage' => $totalFeedback > 0 ? round(($cnt / $totalFeedback) * 100, 1) : 0,
            ];
        }

        // Category breakdown
        $categories = ['Product', 'Service', 'Support', 'Sales', 'Delivery', 'Billing', 'Other'];
        $feedbackByCategory = [];
        foreach ($categories as $cat) {
            $feedbackByCategory[] = [
                'category' => $cat,
                'count' => (clone $query)->where('category', strtolower($cat))->count(),
            ];
        }

        // Feedback trend by month
        $feedbackTrend = (clone $query)
            ->select(DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"), DB::raw("COUNT(*) as total"), DB::raw("AVG(rating) as avg_rating"))
            ->groupBy('month')
            ->orderByRaw("MIN(created_at) ASC")
            ->take(6)
            ->get();

        return [
            'summary' => [
                'total_feedback' => $totalFeedback,
                'avg_rating' => $avgRating,
                'positive' => $positive,
                'neutral' => $neutral,
                'negative' => $negative,
                'open_issues' => $openIssues,
                'resolved' => $resolved,
                'resolution_rate' => $resolutionRate,
            ],
            'rating_distribution' => $ratingDistribution,
            'feedback_by_category' => $feedbackByCategory,
            'feedback_trend' => $feedbackTrend,
        ];
    }

    public function updateStatus(CrmFeedback $feedback, string $newStatus, ?string $resolution = null, ?int $userId = null): CrmFeedback
    {
        $oldStatus = $feedback->status;

        $updateData = [
            'status' => $newStatus,
        ];

        if ($resolution !== null) {
            $updateData['resolution'] = $resolution;
        }

        if (in_array($newStatus, ['resolved', 'closed']) && !$feedback->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $feedback->update($updateData);

        CrmAuditLog::log(
            'Feedback Status Updated',
            CrmFeedback::class,
            $feedback->id,
            ['status' => $oldStatus],
            ['status' => $newStatus, 'resolution' => $resolution],
            $feedback->company_id,
            $userId
        );

        return $feedback->fresh(['customer', 'contact', 'deal', 'assignedUser']);
    }
}
