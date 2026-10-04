<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAuditLog;
use App\Models\AssetDisposal;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AssetDisposalService
{
    /**
     * List disposal requests and records
     */
    public function listDisposals(array $filters, int $companyId): LengthAwarePaginator
    {
        $query = AssetDisposal::with(['asset.category', 'approver', 'creator'])
            ->where('company_id', $companyId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['disposal_type'])) {
            $query->where('disposal_type', $filters['disposal_type']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', $search)
                    ->orWhereHas('asset', fn($aq) => $aq->where('name', 'like', $search)->orWhere('asset_code', 'like', $search));
            });
        }

        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));
        return $query->latest('disposal_date')->paginate($perPage);
    }

    /**
     * Submit a disposal request for an asset
     */
    public function requestDisposal(array $data, int $userId, int $companyId): AssetDisposal
    {
        return DB::transaction(function () use ($data, $userId, $companyId) {
            $asset = Asset::where('company_id', $companyId)->findOrFail($data['asset_id']);

            if ($asset->status === 'Disposed') {
                throw new Exception("Asset {$asset->asset_code} has already been disposed.");
            }

            $bookValue = (float) $asset->current_book_value;
            $saleValue = (float) ($data['sale_value'] ?? 0);
            $lossGain = round($saleValue - $bookValue, 2);

            $disposal = AssetDisposal::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'disposal_type' => $data['disposal_type'] ?? 'Sell',
                'disposal_date' => $data['disposal_date'] ?? Carbon::today(),
                'book_value' => $bookValue,
                'sale_value' => $saleValue,
                'loss_gain' => $lossGain,
                'reason' => $data['reason'],
                'status' => 'Pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'user_id' => $userId,
                'action' => 'disposal_requested',
                'new_values' => $disposal->toArray(),
                'ip_address' => request()->ip(),
            ]);

            return $disposal->load(['asset', 'creator']);
        });
    }

    /**
     * Approve asset disposal and mark asset as Disposed
     */
    public function approveDisposal(int $disposalId, int $approverId, int $companyId): AssetDisposal
    {
        return DB::transaction(function () use ($disposalId, $approverId, $companyId) {
            $disposal = AssetDisposal::where('company_id', $companyId)->findOrFail($disposalId);
            $asset = Asset::find($disposal->asset_id);

            $disposal->update([
                'status' => 'Disposed',
                'approved_by' => $approverId,
                'approved_at' => Carbon::now(),
            ]);

            if ($asset) {
                $asset->update([
                    'status' => 'Disposed',
                    'current_book_value' => 0,
                ]);
            }

            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $disposal->asset_id,
                'user_id' => $approverId,
                'action' => 'disposal_approved',
                'new_values' => $disposal->toArray(),
                'ip_address' => request()->ip(),
            ]);

            return $disposal->fresh(['asset', 'approver']);
        });
    }
}
