<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Vendor;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorService
{
    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Vendor::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->withCount(['purchaseOrders', 'purchases'])
            ->withSum('purchases', 'grand_total');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['name', 'company_name', 'balance', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): Vendor
    {
        $query = Vendor::query()->with([
            'purchaseOrders' => fn ($q) => $q->latest()->take(10),
            'purchases' => fn ($q) => $q->latest()->take(10),
            'payments' => fn ($q) => $q->latest()->take(10),
            'returns' => fn ($q) => $q->latest()->take(10),
        ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $vendor = $query->findOrFail($id);
        $vendor->total_orders_count = $vendor->purchaseOrders()->count();
        $vendor->total_purchases_amount = (float) $vendor->purchases()->sum('grand_total');
        $vendor->total_paid_amount = (float) $vendor->payments()->sum('amount');
        $vendor->outstanding_payable = (float) $vendor->balance;

        return $vendor;
    }

    public function create(array $data, ?int $userId = null): Vendor
    {
        return DB::transaction(function () use ($data, $userId) {
            if (empty($data['vendor_code'])) {
                $count = Vendor::where('company_id', $data['company_id'] ?? 1)->count() + 1;
                $data['vendor_code'] = 'VEN-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            if (isset($data['opening_balance']) && !isset($data['balance'])) {
                $data['balance'] = $data['opening_balance'];
            }

            $vendor = Vendor::create($data);

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $vendor->company_id,
                'action' => 'vendor.created',
                'module' => 'purchase',
                'record_id' => $vendor->id,
                'new_values' => $vendor->toArray(),
            ]);

            return $vendor;
        });
    }

    public function update(int $id, array $data, ?int $userId = null): Vendor
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $vendor = Vendor::findOrFail($id);
            $old = $vendor->toArray();

            $vendor->update($data);

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $vendor->company_id,
                'action' => 'vendor.updated',
                'module' => 'purchase',
                'record_id' => $vendor->id,
                'old_values' => $old,
                'new_values' => $vendor->toArray(),
            ]);

            return $vendor;
        });
    }

    public function delete(int $id, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $vendor = Vendor::findOrFail($id);
            $vendor->delete();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $vendor->company_id,
                'action' => 'vendor.deleted',
                'module' => 'purchase',
                'record_id' => $id,
            ]);

            return true;
        });
    }
}
