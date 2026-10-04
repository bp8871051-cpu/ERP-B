<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAuditLog;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetService
{
    /**
     * Generate unique enterprise asset code: AST-2026-000001
     */
    public function generateAssetCode(int $companyId): string
    {
        $year = Carbon::now()->format('Y');
        $count = Asset::where('company_id', $companyId)->count() + 1;
        do {
            $code = sprintf('AST-%s-%06d', $year, $count);
            $exists = Asset::where('company_id', $companyId)->where('asset_code', $code)->exists();
            $count++;
        } while ($exists);

        return $code;
    }

    /**
     * List assets with filtering, pagination, search, sorting
     */
    public function listAssets(array $filters, int $companyId): LengthAwarePaginator
    {
        $query = Asset::with(['category', 'department', 'location', 'assignedEmployee', 'vendor'])
            ->where('company_id', $companyId);

        // Search by name, asset code, serial number, model
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('asset_name', 'like', $search)
                    ->orWhere('asset_code', 'like', $search)
                    ->orWhere('serial_number', 'like', $search)
                    ->orWhere('model_number', 'like', $search)
                    ->orWhere('brand', 'like', $search);
            });
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Department filter
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Location filter
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('purchase_date', [$filters['start_date'], $filters['end_date']]);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        if ($sortBy === 'name') {
            $sortBy = 'asset_name';
        }
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));

        return $query->paginate($perPage);
    }

    /**
     * Create a new fixed asset
     */
    public function createAsset(array $data, int $userId, int $companyId): Asset
    {
        return DB::transaction(function () use ($data, $userId, $companyId) {
            $assetCode = !empty($data['asset_code']) ? $data['asset_code'] : $this->generateAssetCode($companyId);
            $purchaseCost = (float) ($data['purchase_cost'] ?? 0);
            $taxAmount = (float) ($data['tax_amount'] ?? 0);
            $totalCost = (float) ($data['total_cost'] ?? ($purchaseCost + $taxAmount));
            $salvageValue = (float) ($data['salvage_value'] ?? 0);
            $usefulLifeYears = max(1, (int) ($data['useful_life_years'] ?? 5));
            $usefulLifeMonths = isset($data['useful_life_months']) ? (int)$data['useful_life_months'] : ($usefulLifeYears * 12);
            $method = $data['depreciation_method'] ?? 'straight_line';

            $asset = Asset::create([
                'company_id' => $companyId,
                'category_id' => $data['category_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'asset_code' => $assetCode,
                'asset_name' => $data['name'] ?? ($data['asset_name'] ?? 'Asset'),
                'subcategory' => $data['subcategory'] ?? null,
                'serial_number' => $data['serial_number'] ?? null,
                'model_number' => $data['model_number'] ?? null,
                'brand' => $data['brand'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? Carbon::today(),
                'purchase_invoice' => $data['purchase_invoice'] ?? null,
                'purchase_cost' => $purchaseCost,
                'tax_amount' => $taxAmount,
                'total_cost' => $totalCost,
                'salvage_value' => $salvageValue,
                'useful_life_months' => $usefulLifeMonths,
                'depreciation_method' => $method,
                'current_book_value' => $totalCost,
                'warranty_start' => $data['warranty_start'] ?? null,
                'warranty_end' => $data['warranty_end'] ?? null,
                'status' => $data['status'] ?? 'available',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Audit Log
            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'user_id' => $userId,
                'action' => 'created',
                'new_values' => $asset->toArray(),
                'ip_address' => request()->ip(),
            ]);

            return $asset->load(['category', 'department', 'location']);
        });
    }

    /**
     * Get single asset with complete audit, assignment, maintenance and depreciation history
     */
    public function getAssetDetails(int $id, int $companyId): Asset
    {
        return Asset::with([
            'category',
            'department',
            'location',
            'vendor',
            'assignedEmployee.department',
            'currentAssignment.employee',
            'assignmentHistory',
            'maintenanceRecords' => fn($q) => $q->latest(),
            'depreciations' => fn($q) => $q->latest(),
            'depreciationSchedules' => fn($q) => $q->orderBy('period_date'),
            'disposals',
            'documents',
            'auditLogs.user' => fn($q) => $q->latest(),
        ])
        ->where('company_id', $companyId)
        ->findOrFail($id);
    }

    /**
     * Update asset
     */
    public function updateAsset(int $id, array $data, int $userId, int $companyId): Asset
    {
        return DB::transaction(function () use ($id, $data, $userId, $companyId) {
            $asset = Asset::where('company_id', $companyId)->findOrFail($id);
            $oldValues = $asset->toArray();

            $data['updated_by'] = $userId;
            $asset->update($data);

            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'user_id' => $userId,
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $asset->toArray(),
                'ip_address' => request()->ip(),
            ]);

            return $asset->fresh(['category', 'department', 'location', 'assignedEmployee']);
        });
    }

    /**
     * Delete asset
     */
    public function deleteAsset(int $id, int $userId, int $companyId): bool
    {
        $asset = Asset::where('company_id', $companyId)->findOrFail($id);
        AssetAuditLog::create([
            'company_id' => $companyId,
            'asset_id' => $asset->id,
            'user_id' => $userId,
            'action' => 'deleted',
            'old_values' => $asset->toArray(),
            'ip_address' => request()->ip(),
        ]);
        return $asset->delete();
    }
}
