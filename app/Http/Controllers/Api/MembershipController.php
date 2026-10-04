<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Services\MembershipPlanService;
use App\Services\MembershipRenewalService;
use App\Services\MembershipService;
use App\Services\MembershipTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    protected MembershipService $membershipService;
    protected MembershipPlanService $planService;
    protected MembershipRenewalService $renewalService;
    protected MembershipTransactionService $transactionService;

    public function __construct(
        MembershipService $membershipService,
        MembershipPlanService $planService,
        MembershipRenewalService $renewalService,
        MembershipTransactionService $transactionService
    ) {
        $this->membershipService = $membershipService;
        $this->planService = $planService;
        $this->renewalService = $renewalService;
        $this->transactionService = $transactionService;
    }

    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->membershipService->getDashboardData($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // --- Plans & Addons ---
    public function plans(Request $request): JsonResponse
    {
        $plans = $this->planService->getPlans($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $plans]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,quarterly,half-yearly,yearly,lifetime,custom',
            'trial_period_days' => 'nullable|integer',
            'setup_fee' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'max_users' => 'nullable|integer|min:1',
            'storage_limit_gb' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive,draft,archived',
        ]);

        $plan = $this->planService->storePlan($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $plan, 'message' => 'Membership plan created successfully.']);
    }

    public function updatePlan(Request $request, int $id): JsonResponse
    {
        $plan = $this->planService->updatePlan($id, $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $plan, 'message' => 'Plan updated successfully.']);
    }

    public function addons(Request $request): JsonResponse
    {
        $addons = $this->planService->getAddons($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $addons]);
    }

    public function storeAddon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'nullable|string',
            'quantity_limit' => 'nullable|integer',
            'feature_key' => 'nullable|string',
        ]);

        $addon = $this->planService->storeAddon($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $addon, 'message' => 'Membership addon created.']);
    }

    // --- Subscriptions / Memberships ---
    public function members(Request $request): JsonResponse
    {
        $members = $this->renewalService->getMembers($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $members]);
    }

    public function storeMember(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'plan_id' => 'required|integer',
            'start_date' => 'nullable|date',
            'auto_renew' => 'nullable|boolean',
            'payment_method' => 'nullable|string',
            'addons' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $member = $this->renewalService->createMembership($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $member, 'message' => 'Customer membership registered successfully.']);
    }

    public function showMember(Request $request, int $id): JsonResponse
    {
        $member = Membership::where('company_id', $this->getCompanyId($request))
            ->with(['customer', 'plan', 'addonItems.addon', 'transactions', 'renewals.previousPlan', 'renewals.newPlan'])
            ->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $member]);
    }

    public function renewMember(Request $request, int $id): JsonResponse
    {
        $member = $this->renewalService->renewMembership($id, $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $member, 'message' => 'Membership renewed successfully!']);
    }

    public function calculateProrata(Request $request, int $id): JsonResponse
    {
        $request->validate(['new_plan_id' => 'required|integer']);
        $calc = $this->renewalService->calculateProrata($id, (int) $request->input('new_plan_id'), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $calc]);
    }

    public function switchPlanMember(Request $request, int $id): JsonResponse
    {
        $request->validate(['new_plan_id' => 'required|integer']);
        $member = $this->renewalService->switchPlan($id, (int) $request->input('new_plan_id'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $member, 'message' => 'Membership plan changed successfully!']);
    }

    public function cancelMember(Request $request, int $id): JsonResponse
    {
        $member = $this->renewalService->cancelMembership($id, $request->input('reason'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $member, 'message' => 'Membership cancelled.']);
    }

    // --- Transactions ---
    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->transactionService->getTransactions($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $transactions]);
    }
}
