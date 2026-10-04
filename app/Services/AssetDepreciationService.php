<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\AssetDepreciationSchedule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class AssetDepreciationService
{
    /**
     * Calculate monthly and annual depreciation for an asset
     */
    public function calculateDepreciationMetrics(Asset $asset): array
    {
        $cost = (float) $asset->total_cost;
        $salvage = (float) $asset->salvage_value;
        $lifeYears = max(1, (int) $asset->useful_life_years);
        $method = $asset->depreciation_method ?: 'straight_line';

        $depreciableBase = max(0, $cost - $salvage);
        $annualDepreciation = 0;
        $monthlyDepreciation = 0;
        $depreciationRate = 0;

        switch ($method) {
            case 'declining_balance':
                // Standard Declining balance rate e.g. 1 / life
                $depreciationRate = round((1 / $lifeYears) * 100, 2);
                $annualDepreciation = round($cost * ($depreciationRate / 100), 2);
                $monthlyDepreciation = round($annualDepreciation / 12, 2);
                break;

            case 'double_declining':
                // Double declining balance (2 / life)
                $depreciationRate = round((2 / $lifeYears) * 100, 2);
                $annualDepreciation = round($cost * ($depreciationRate / 100), 2);
                $monthlyDepreciation = round($annualDepreciation / 12, 2);
                break;

            case 'units_of_production':
                // Approximation based on standard usage
                $depreciationRate = round((1 / $lifeYears) * 100, 2);
                $annualDepreciation = round($depreciableBase / $lifeYears, 2);
                $monthlyDepreciation = round($annualDepreciation / 12, 2);
                break;

            case 'straight_line':
            default:
                // Straight line: (Cost - Salvage) / Useful Life
                $annualDepreciation = round($depreciableBase / $lifeYears, 2);
                $monthlyDepreciation = round($annualDepreciation / 12, 2);
                $depreciationRate = round((1 / $lifeYears) * 100, 2);
                break;
        }

        // Calculate months elapsed since purchase date
        $purchaseDate = $asset->purchase_date ? Carbon::parse($asset->purchase_date) : Carbon::now()->subMonths(6);
        $monthsElapsed = max(0, $purchaseDate->diffInMonths(Carbon::now()));
        $accumulatedDepreciation = min($depreciableBase, round($monthlyDepreciation * $monthsElapsed, 2));
        $currentBookValue = max($salvage, round($cost - $accumulatedDepreciation, 2));

        return [
            'purchase_cost' => $cost,
            'salvage_value' => $salvage,
            'useful_life_years' => $lifeYears,
            'depreciation_method' => $method,
            'depreciation_rate' => $depreciationRate,
            'annual_depreciation' => $annualDepreciation,
            'monthly_depreciation' => $monthlyDepreciation,
            'months_elapsed' => $monthsElapsed,
            'accumulated_depreciation' => $accumulatedDepreciation,
            'current_book_value' => $currentBookValue,
        ];
    }

    /**
     * Generate full depreciation schedule (Monthly, Quarterly, or Yearly)
     */
    public function generateSchedule(int $assetId, string $scheduleType = 'yearly', int $companyId = 1): array
    {
        $asset = Asset::where('company_id', $companyId)->findOrFail($assetId);
        $metrics = $this->calculateDepreciationMetrics($asset);

        $cost = $metrics['purchase_cost'];
        $salvage = $metrics['salvage_value'];
        $lifeYears = $metrics['useful_life_years'];
        $annualDep = $metrics['annual_depreciation'];
        $monthlyDep = $metrics['monthly_depreciation'];

        $schedule = [];
        $runningAccumulated = 0;
        $currentValue = $cost;
        $purchaseDate = $asset->purchase_date ? Carbon::parse($asset->purchase_date) : Carbon::today();

        if ($scheduleType === 'monthly') {
            $totalPeriods = min(60, $lifeYears * 12);
            for ($i = 1; $i <= $totalPeriods; $i++) {
                $periodDate = (clone $purchaseDate)->addMonths($i - 1);
                $depAmount = min($currentValue - $salvage, $monthlyDep);
                if ($depAmount < 0) $depAmount = 0;
                $beginning = $currentValue;
                $runningAccumulated += $depAmount;
                $currentValue = round($cost - $runningAccumulated, 2);

                $schedule[] = [
                    'period' => $i,
                    'period_label' => $periodDate->format('M Y'),
                    'period_date' => $periodDate->format('Y-m-d'),
                    'beginning_value' => $beginning,
                    'depreciation_amount' => $depAmount,
                    'accumulated_depreciation' => round($runningAccumulated, 2),
                    'ending_value' => max($salvage, $currentValue),
                ];
            }
        } elseif ($scheduleType === 'quarterly') {
            $totalPeriods = min(40, $lifeYears * 4);
            $quarterlyDep = round($annualDep / 4, 2);
            for ($i = 1; $i <= $totalPeriods; $i++) {
                $periodDate = (clone $purchaseDate)->addMonths(($i - 1) * 3);
                $depAmount = min($currentValue - $salvage, $quarterlyDep);
                if ($depAmount < 0) $depAmount = 0;
                $beginning = $currentValue;
                $runningAccumulated += $depAmount;
                $currentValue = round($cost - $runningAccumulated, 2);

                $schedule[] = [
                    'period' => $i,
                    'period_label' => 'Q' . ceil(($periodDate->month) / 3) . ' ' . $periodDate->year,
                    'period_date' => $periodDate->format('Y-m-d'),
                    'beginning_value' => $beginning,
                    'depreciation_amount' => $depAmount,
                    'accumulated_depreciation' => round($runningAccumulated, 2),
                    'ending_value' => max($salvage, $currentValue),
                ];
            }
        } else {
            // Yearly
            for ($year = 1; $year <= $lifeYears; $year++) {
                $periodDate = (clone $purchaseDate)->addYears($year - 1);
                $depAmount = min($currentValue - $salvage, $annualDep);
                if ($depAmount < 0) $depAmount = 0;
                $beginning = $currentValue;
                $runningAccumulated += $depAmount;
                $currentValue = round($cost - $runningAccumulated, 2);

                $schedule[] = [
                    'period' => $year,
                    'period_label' => 'Year ' . $year . ' (' . $periodDate->year . ')',
                    'period_date' => $periodDate->format('Y-m-d'),
                    'beginning_value' => $beginning,
                    'depreciation_amount' => $depAmount,
                    'accumulated_depreciation' => round($runningAccumulated, 2),
                    'ending_value' => max($salvage, $currentValue),
                ];
            }
        }

        return [
            'asset' => $asset,
            'metrics' => $metrics,
            'schedule_type' => $scheduleType,
            'schedule' => $schedule,
        ];
    }

    /**
     * Run batch depreciation calculation across all company assets
     */
    public function runBatchDepreciation(int $companyId, ?int $userId = null): array
    {
        $assets = Asset::where('company_id', $companyId)
            ->whereIn('status', ['available', 'assigned', 'under_maintenance', 'Available', 'Assigned', 'Under Maintenance'])
            ->get();

        $processed = 0;
        $totalDepreciationRun = 0;

        foreach ($assets as $asset) {
            $metrics = $this->calculateDepreciationMetrics($asset);

            // Only update current_book_value (accumulated_depreciation is virtual in model)
            $asset->update([
                'current_book_value' => $metrics['current_book_value'],
            ]);

            // Save annual depreciation record using actual column names
            AssetDepreciation::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'asset_id' => $asset->id,
                    'period_date' => Carbon::today()->format('Y-m-d'),
                ],
                [
                    'depreciation_method' => $asset->depreciation_method ?: 'straight_line',
                    'depreciation_amount' => $metrics['annual_depreciation'],
                    'accumulated_depreciation' => $metrics['accumulated_depreciation'],
                    'book_value_ending' => $metrics['current_book_value'],
                    'created_by' => $userId,
                ]
            );

            $totalDepreciationRun += $metrics['annual_depreciation'];
            $processed++;
        }

        return [
            'calculated_count' => $processed,
            'processed_assets' => $processed,
            'total_depreciation' => round($totalDepreciationRun, 2),
        ];
    }
}
