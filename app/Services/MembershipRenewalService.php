<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Membership;
use App\Models\MembershipAddon;
use App\Models\MembershipAddonItem;
use App\Models\MembershipPlan;
use App\Models\MembershipRenewal;
use App\Models\MembershipTransaction;
use App\Models\SystemAuditLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MembershipRenewalService
{
    /**
     * Get paginated customer memberships
     */
    public function getMembers(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = Membership::where('company_id', $companyId)
            ->with(['customer', 'plan', 'addonItems.addon']);

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('membership_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', function ($cq) use ($s) {
                        $cq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                    })
                    ->orWhereHas('plan', function ($pq) use ($s) {
                        $pq->where('name', 'like', "%{$s}%");
                    });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['plan_id']) && $filters['plan_id'] !== 'all') {
            $query->where('plan_id', $filters['plan_id']);
        }

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 15)));
        return $query->latest('start_date')->paginate($perPage);
    }

    /**
     * Subscribe Customer to Plan + Addons
     */
    public function createMembership(array $data, int $companyId = 1, ?int $userId = null): Membership
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            $plan = MembershipPlan::where('company_id', $companyId)->findOrFail($data['plan_id']);

            // Calculate start & expiry
            $startDate = !empty($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::today();
            $expiryDate = $this->calculateExpiryDate($startDate, $plan->billing_cycle);

            $membershipNumber = 'MEM-' . date('Y') . '-' . str_pad((string) (Membership::where('company_id', $companyId)->count() + 1), 4, '0', STR_PAD_LEFT);

            // Compute total amount
            $basePrice = (float) $plan->price;
            $discount = (float) ($data['discount'] ?? $plan->discount ?? 0);
            $taxRate = (float) ($plan->tax_rate ?? 18);

            $addonTotal = 0;
            $addonsToAttach = [];
            if (!empty($data['addons']) && is_array($data['addons'])) {
                foreach ($data['addons'] as $addonReq) {
                    $addonId = $addonReq['addon_id'] ?? $addonReq['id'];
                    $qty = max(1, (int) ($addonReq['quantity'] ?? 1));
                    $addon = MembershipAddon::where('company_id', $companyId)->find($addonId);
                    if ($addon) {
                        $itemTotal = (float) $addon->price * $qty;
                        $addonTotal += $itemTotal;
                        $addonsToAttach[] = [
                            'addon_id' => $addon->id,
                            'quantity' => $qty,
                            'unit_price' => $addon->price,
                            'total_price' => $itemTotal,
                        ];
                    }
                }
            }

            $subtotal = max(0, ($basePrice + $addonTotal) - $discount);
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $totalAmount = round($subtotal + $taxAmount, 2);

            $membership = Membership::create([
                'company_id' => $companyId,
                'customer_id' => $data['customer_id'],
                'plan_id' => $plan->id,
                'membership_number' => $membershipNumber,
                'status' => 'active',
                'start_date' => $startDate->toDateString(),
                'expiry_date' => $expiryDate->toDateString(),
                'billing_cycle' => $plan->billing_cycle,
                'auto_renew' => $data['auto_renew'] ?? true,
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'] ?? 'card',
                'notes' => $data['notes'] ?? null,
            ]);

            // Save Addons
            foreach ($addonsToAttach as $item) {
                MembershipAddonItem::create(array_merge($item, ['membership_id' => $membership->id]));
            }

            // Create initial payment transaction
            MembershipTransaction::create([
                'company_id' => $companyId,
                'customer_id' => $data['customer_id'],
                'membership_id' => $membership->id,
                'transaction_number' => 'TXN-' . strtoupper(Str::random(8)),
                'amount' => $basePrice + $addonTotal,
                'tax' => $taxAmount,
                'discount' => $discount,
                'total' => $totalAmount,
                'payment_method' => $data['payment_method'] ?? 'card',
                'status' => 'paid',
                'transaction_date' => Carbon::now(),
                'notes' => 'Initial subscription purchase for ' . $plan->name,
            ]);

            SystemAuditLog::log('membership', 'create_membership', (string) $membership->id, null, $membership->toArray(), $companyId, $userId);

            return $membership->load(['customer', 'plan', 'addonItems.addon']);
        });
    }

    /**
     * Renew an existing membership
     */
    public function renewMembership(int $membershipId, array $options = [], int $companyId = 1, ?int $userId = null): Membership
    {
        return DB::transaction(function () use ($membershipId, $options, $companyId, $userId) {
            $membership = Membership::where('company_id', $companyId)->with('plan')->findOrFail($membershipId);
            $oldExpiry = Carbon::parse($membership->expiry_date);

            // If already expired, renew starting from today, otherwise add to current expiry
            $baseStart = $oldExpiry->isPast() ? Carbon::today() : $oldExpiry;
            $newExpiry = $this->calculateExpiryDate($baseStart, $membership->plan->billing_cycle);

            $renewalPrice = (float) $membership->total_amount;
            $discount = (float) ($options['discount'] ?? 0);
            $tax = round(($renewalPrice - $discount) * ((float) ($membership->plan->tax_rate ?? 18) / 100), 2);
            $finalPaid = round(($renewalPrice - $discount) + $tax, 2);

            $membership->expiry_date = $newExpiry->toDateString();
            $membership->status = 'active';
            $membership->save();

            // Log renewal
            MembershipRenewal::create([
                'company_id' => $companyId,
                'membership_id' => $membership->id,
                'previous_plan_id' => $membership->plan_id,
                'new_plan_id' => $membership->plan_id,
                'previous_expiry' => $oldExpiry->toDateString(),
                'new_expiry' => $newExpiry->toDateString(),
                'renewal_amount' => $renewalPrice,
                'discount' => $discount,
                'tax' => $tax,
                'total_paid' => $finalPaid,
                'renewal_type' => 'renew',
                'payment_status' => 'paid',
                'renewed_by' => $userId,
            ]);

            // Transaction
            MembershipTransaction::create([
                'company_id' => $companyId,
                'customer_id' => $membership->customer_id,
                'membership_id' => $membership->id,
                'transaction_number' => 'TXN-' . strtoupper(Str::random(8)),
                'amount' => $renewalPrice,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $finalPaid,
                'payment_method' => $options['payment_method'] ?? $membership->payment_method ?? 'card',
                'status' => 'paid',
                'transaction_date' => Carbon::now(),
                'notes' => 'Subscription renewal extended to ' . $newExpiry->toDateString(),
            ]);

            SystemAuditLog::log('membership', 'renew_membership', (string) $membership->id, null, ['new_expiry' => $newExpiry->toDateString()], $companyId, $userId);

            return $membership->load(['customer', 'plan']);
        });
    }

    /**
     * Calculate Pro-Rata Upgrade or Downgrade in Laravel
     */
    public function calculateProrata(int $membershipId, int $newPlanId, int $companyId = 1): array
    {
        $membership = Membership::where('company_id', $companyId)->with('plan')->findOrFail($membershipId);
        $newPlan = MembershipPlan::where('company_id', $companyId)->findOrFail($newPlanId);

        $currentPlan = $membership->plan;
        $currentExpiry = Carbon::parse($membership->expiry_date);
        $today = Carbon::today();

        // Remaining period days
        $daysRemaining = max(0, $today->diffInDays($currentExpiry, false));
        $totalCycleDays = 30; // standard month reference
        if ($currentPlan->billing_cycle === 'yearly') $totalCycleDays = 365;
        if ($currentPlan->billing_cycle === 'quarterly') $totalCycleDays = 90;

        $unusedRatio = min(1.0, max(0.0, $daysRemaining / max(1, $totalCycleDays)));
        $creditAmount = round((float) $currentPlan->price * $unusedRatio, 2);

        $newPlanPrice = (float) $newPlan->price;
        $difference = round($newPlanPrice - $creditAmount, 2);
        $tax = round(max(0, $difference) * ((float) ($newPlan->tax_rate ?? 18) / 100), 2);
        $finalAmount = round(max(0, $difference) + $tax, 2);

        return [
            'current_plan' => $currentPlan->name,
            'new_plan' => $newPlan->name,
            'days_remaining' => $daysRemaining,
            'credit_amount' => $creditAmount,
            'new_plan_price' => $newPlanPrice,
            'difference' => $difference,
            'tax' => $tax,
            'final_amount' => $finalAmount,
            'is_upgrade' => $newPlanPrice >= $currentPlan->price,
        ];
    }

    /**
     * Apply Upgrade or Downgrade
     */
    public function switchPlan(int $membershipId, int $newPlanId, int $companyId = 1, ?int $userId = null): Membership
    {
        return DB::transaction(function () use ($membershipId, $newPlanId, $companyId, $userId) {
            $membership = Membership::where('company_id', $companyId)->findOrFail($membershipId);
            $calculation = $this->calculateProrata($membershipId, $newPlanId, $companyId);
            $newPlan = MembershipPlan::where('company_id', $companyId)->findOrFail($newPlanId);

            $oldPlanId = $membership->plan_id;
            $oldExpiry = Carbon::parse($membership->expiry_date);
            $newExpiry = $this->calculateExpiryDate(Carbon::today(), $newPlan->billing_cycle);

            $membership->plan_id = $newPlan->id;
            $membership->billing_cycle = $newPlan->billing_cycle;
            $membership->expiry_date = $newExpiry->toDateString();
            $membership->total_amount = $calculation['final_amount'];
            $membership->save();

            // Record Renewal / Upgrade log
            MembershipRenewal::create([
                'company_id' => $companyId,
                'membership_id' => $membership->id,
                'previous_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlan->id,
                'previous_expiry' => $oldExpiry->toDateString(),
                'new_expiry' => $newExpiry->toDateString(),
                'renewal_amount' => $calculation['new_plan_price'],
                'discount' => $calculation['credit_amount'],
                'tax' => $calculation['tax'],
                'total_paid' => $calculation['final_amount'],
                'renewal_type' => $calculation['is_upgrade'] ? 'upgrade' : 'downgrade',
                'payment_status' => 'paid',
                'renewed_by' => $userId,
            ]);

            // Transaction
            if ($calculation['final_amount'] > 0) {
                MembershipTransaction::create([
                    'company_id' => $companyId,
                    'customer_id' => $membership->customer_id,
                    'membership_id' => $membership->id,
                    'transaction_number' => 'TXN-' . strtoupper(Str::random(8)),
                    'amount' => $calculation['difference'],
                    'tax' => $calculation['tax'],
                    'discount' => $calculation['credit_amount'],
                    'total' => $calculation['final_amount'],
                    'payment_method' => $membership->payment_method ?? 'card',
                    'status' => 'paid',
                    'transaction_date' => Carbon::now(),
                    'notes' => 'Plan switch adjustment to ' . $newPlan->name,
                ]);
            }

            SystemAuditLog::log('membership', 'switch_plan', (string) $membership->id, ['plan_id' => $oldPlanId], ['plan_id' => $newPlan->id], $companyId, $userId);

            return $membership->load(['customer', 'plan']);
        });
    }

    /**
     * Cancel Membership
     */
    public function cancelMembership(int $membershipId, ?string $reason = null, int $companyId = 1, ?int $userId = null): Membership
    {
        $membership = Membership::where('company_id', $companyId)->findOrFail($membershipId);
        $membership->status = 'cancelled';
        $membership->cancelled_at = Carbon::now();
        $membership->cancellation_reason = $reason ?: 'Cancelled upon customer request';
        $membership->auto_renew = false;
        $membership->save();

        SystemAuditLog::log('membership', 'cancel_membership', (string) $membership->id, null, ['reason' => $reason], $companyId, $userId);

        return $membership->load(['customer', 'plan']);
    }

    /**
     * Calculate expiry date from start date and billing cycle
     */
    protected function calculateExpiryDate(Carbon $startDate, string $cycle): Carbon
    {
        return match ($cycle) {
            'quarterly' => $startDate->copy()->addMonths(3),
            'half-yearly' => $startDate->copy()->addMonths(6),
            'yearly' => $startDate->copy()->addYear(),
            'lifetime' => $startDate->copy()->addYears(99),
            default => $startDate->copy()->addMonth(),
        };
    }
}
