<?php

namespace App\Services;

use App\Models\CrmCustomerSegment;
use App\Models\CrmDeal;
use App\Models\CrmSegmentMember;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class CustomerSegmentationService
{
    public function evaluateSegments(int $companyId): array
    {
        $segments = CrmCustomerSegment::where('company_id', $companyId)->get();

        if ($segments->isEmpty()) {
            $this->seedDefaultSegments($companyId);
            $segments = CrmCustomerSegment::where('company_id', $companyId)->get();
        }

        $customers = Customer::where('company_id', $companyId)->get();
        $results = [];

        foreach ($segments as $segment) {
            $matchedCount = 0;
            foreach ($customers as $customer) {
                if ($this->customerMatchesSegment($customer, $segment->code)) {
                    CrmSegmentMember::firstOrCreate([
                        'segment_id' => $segment->id,
                        'customer_id' => $customer->id,
                    ], [
                        'joined_at' => now(),
                    ]);
                    $matchedCount++;
                } else {
                    CrmSegmentMember::where('segment_id', $segment->id)
                        ->where('customer_id', $customer->id)
                        ->delete();
                }
            }

            $results[] = [
                'segment' => $segment,
                'count' => $matchedCount,
            ];
        }

        return $results;
    }

    protected function customerMatchesSegment(Customer $customer, string $code): bool
    {
        $totalInvoiced = (float) Invoice::where('customer_id', $customer->id)->where('status', 'paid')->sum('total');
        $wonDealsCount = CrmDeal::where('customer_id', $customer->id)->where('status', 'won')->count();
        $isRecent = $customer->created_at->diffInDays(now()) <= 30;

        return match ($code) {
            'VIP' => $totalInvoiced >= 500000 || $wonDealsCount >= 5,
            'HIGH_VALUE' => $totalInvoiced >= 200000 && $totalInvoiced < 500000,
            'REPEAT' => $wonDealsCount >= 2,
            'NEW' => $isRecent,
            'ACTIVE' => $customer->status === 'active' && ($wonDealsCount > 0 || $totalInvoiced > 0),
            'AT_RISK' => $customer->status === 'active' && $wonDealsCount === 0 && !$isRecent,
            'INACTIVE' => $customer->status === 'inactive' || ($customer->created_at->diffInDays(now()) > 180 && $totalInvoiced == 0),
            default => false,
        };
    }

    public function seedDefaultSegments(int $companyId): void
    {
        $defaults = [
            ['name' => 'VIP Clients', 'code' => 'VIP', 'color' => '#8B5CF6', 'description' => 'High-yield accounts generating > ₹5L revenue or 5+ deals'],
            ['name' => 'High Value Customers', 'code' => 'HIGH_VALUE', 'color' => '#0F8B7A', 'description' => 'Substantial contracts generating ₹2L - ₹5L'],
            ['name' => 'Repeat Customers', 'code' => 'REPEAT', 'color' => '#2563EB', 'description' => 'Multiple closed won transactions'],
            ['name' => 'New Customers', 'code' => 'NEW', 'color' => '#10B981', 'description' => 'Registered in the last 30 days'],
            ['name' => 'Active Customers', 'code' => 'ACTIVE', 'color' => '#0284C7', 'description' => 'Consistently engaged client portfolio'],
            ['name' => 'At Risk Accounts', 'code' => 'AT_RISK', 'color' => '#F59E0B', 'description' => 'No active deals or invoices in past 90 days'],
            ['name' => 'Inactive Accounts', 'code' => 'INACTIVE', 'color' => '#94A3B8', 'description' => 'Dormant client directory entries'],
        ];

        foreach ($defaults as $d) {
            CrmCustomerSegment::firstOrCreate([
                'company_id' => $companyId,
                'code' => $d['code'],
            ], [
                'name' => $d['name'],
                'color' => $d['color'],
                'description' => $d['description'],
                'status' => 'active',
            ]);
        }
    }
}
