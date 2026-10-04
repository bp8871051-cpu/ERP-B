<?php

namespace App\Services;

use App\Models\MembershipAddon;
use App\Models\MembershipPlan;
use App\Models\SystemAuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MembershipPlanService
{
    /**
     * Get paginated plans
     */
    public function getPlans(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = MembershipPlan::where('company_id', $companyId)
            ->withCount('memberships');

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['billing_cycle']) && $filters['billing_cycle'] !== 'all') {
            $query->where('billing_cycle', $filters['billing_cycle']);
        }

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 15)));
        return $query->orderBy('price', 'asc')->paginate($perPage);
    }

    /**
     * Create new plan
     */
    public function storePlan(array $data, int $companyId = 1, ?int $userId = null): MembershipPlan
    {
        $code = !empty($data['code']) ? strtoupper(trim($data['code'])) : 'PLAN-' . strtoupper(Str::random(6));

        $plan = MembershipPlan::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $code,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'trial_period_days' => $data['trial_period_days'] ?? 0,
            'setup_fee' => $data['setup_fee'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 18,
            'max_users' => $data['max_users'] ?? 5,
            'storage_limit_gb' => $data['storage_limit_gb'] ?? 10,
            'features' => $data['features'] ?? [],
            'status' => $data['status'] ?? 'active',
        ]);

        SystemAuditLog::log('membership', 'create_plan', (string) $plan->id, null, $plan->toArray(), $companyId, $userId);

        return $plan;
    }

    /**
     * Update plan
     */
    public function updatePlan(int $id, array $data, int $companyId = 1, ?int $userId = null): MembershipPlan
    {
        $plan = MembershipPlan::where('company_id', $companyId)->findOrFail($id);
        $old = $plan->toArray();

        $plan->update($data);

        SystemAuditLog::log('membership', 'update_plan', (string) $plan->id, $old, $plan->toArray(), $companyId, $userId);

        return $plan;
    }

    /**
     * Get Addons
     */
    public function getAddons(int $companyId = 1): Collection
    {
        return MembershipAddon::where('company_id', $companyId)->get();
    }

    /**
     * Store Addon
     */
    public function storeAddon(array $data, int $companyId = 1, ?int $userId = null): MembershipAddon
    {
        $code = !empty($data['code']) ? strtoupper(trim($data['code'])) : 'ADDON-' . strtoupper(Str::random(6));

        $addon = MembershipAddon::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $code,
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'quantity_limit' => $data['quantity_limit'] ?? null,
            'feature_key' => $data['feature_key'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        SystemAuditLog::log('membership', 'create_addon', (string) $addon->id, null, $addon->toArray(), $companyId, $userId);

        return $addon;
    }
}
