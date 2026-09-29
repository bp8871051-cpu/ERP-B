<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->withCount(['salesOrders', 'invoices'])
            ->withSum('invoices', 'total');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['name', 'company_name', 'balance', 'created_at', 'total_sales'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): Customer
    {
        $query = Customer::query()->with([
            'salesOrders' => fn ($q) => $q->latest()->take(10),
            'invoices' => fn ($q) => $q->latest()->take(10),
            'payments' => fn ($q) => $q->latest()->take(10),
            'creditNotes' => fn ($q) => $q->latest()->take(10),
            'refunds' => fn ($q) => $q->latest()->take(10),
            'deliveryNotes' => fn ($q) => $q->latest()->take(10),
        ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $customer = $query->findOrFail($id);

        $customer->total_orders_count = $customer->salesOrders()->count();
        $customer->total_sales_amount = (float) $customer->invoices()->sum('total');
        $customer->paid_amount = (float) $customer->invoices()->sum('amount_paid');
        $customer->outstanding_balance = (float) $customer->balance;

        return $customer;
    }

    public function create(array $data, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($data, $userId) {
            if (empty($data['customer_code'])) {
                $count = Customer::where('company_id', $data['company_id'] ?? 1)->count() + 1;
                $data['customer_code'] = 'CUST-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            if (isset($data['opening_balance']) && !isset($data['balance'])) {
                $data['balance'] = $data['opening_balance'];
            }

            $customer = Customer::create($data);

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $customer->company_id,
                'action' => 'customer.created',
                'module' => 'sales',
                'record_id' => $customer->id,
                'new_values' => $customer->toArray(),
            ]);

            return $customer;
        });
    }

    public function update(int $id, array $data, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $customer = Customer::findOrFail($id);
            $old = $customer->toArray();

            $customer->update($data);

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $customer->company_id,
                'action' => 'customer.updated',
                'module' => 'sales',
                'record_id' => $customer->id,
                'old_values' => $old,
                'new_values' => $customer->toArray(),
            ]);

            return $customer;
        });
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $customer = Customer::findOrFail($id);
            $customer->delete();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $customer->company_id,
                'action' => 'customer.deleted',
                'module' => 'sales',
                'record_id' => $id,
            ]);

            return true;
        });
    }
}
