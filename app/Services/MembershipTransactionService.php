<?php

namespace App\Services;

use App\Models\MembershipTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

class MembershipTransactionService
{
    /**
     * Get paginated transactions ledger
     */
    public function getTransactions(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = MembershipTransaction::where('company_id', $companyId)
            ->with(['customer', 'membership.plan']);

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('transaction_number', 'like', "%{$s}%")
                    ->orWhere('payment_reference', 'like', "%{$s}%")
                    ->orWhereHas('customer', function ($cq) use ($s) {
                        $cq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                    });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $query->where('payment_method', $filters['payment_method']);
        }

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 15)));
        return $query->latest('transaction_date')->paginate($perPage);
    }
}
