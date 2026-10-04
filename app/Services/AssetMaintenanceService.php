<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAuditLog;
use App\Models\AssetMaintenance;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AssetMaintenanceService
{
    /**
     * List maintenance tickets
     */
    public function listMaintenance(array $filters, int $companyId): LengthAwarePaginator
    {
        $query = AssetMaintenance::with(['asset.category', 'creator'])
            ->where('company_id', $companyId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (!empty($filters['maintenance_type'])) {
            $query->where('maintenance_type', $filters['maintenance_type']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('issue', 'like', $search)
                    ->orWhere('vendor', 'like', $search)
                    ->orWhere('assigned_technician', 'like', $search)
                    ->orWhereHas('asset', fn($aq) => $aq->where('name', 'like', $search)->orWhere('asset_code', 'like', $search));
            });
        }

        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));
        return $query->latest('start_date')->paginate($perPage);
    }

    /**
     * Create maintenance ticket
     */
    public function createTicket(array $data, int $userId, int $companyId): AssetMaintenance
    {
        return DB::transaction(function () use ($data, $userId, $companyId) {
            $asset = Asset::where('company_id', $companyId)->findOrFail($data['asset_id']);

            $ticket = AssetMaintenance::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'maintenance_type' => $data['maintenance_type'] ?? 'Preventive',
                'priority' => $data['priority'] ?? 'Medium',
                'issue' => $data['issue'],
                'description' => $data['description'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'assigned_technician' => $data['assigned_technician'] ?? null,
                'start_date' => $data['start_date'] ?? Carbon::today(),
                'expected_completion' => $data['expected_completion'] ?? null,
                'estimated_cost' => (float) ($data['estimated_cost'] ?? 0),
                'actual_cost' => 0,
                'status' => $data['status'] ?? 'Requested',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Update asset status to 'Under Maintenance'
            $asset->update(['status' => 'Under Maintenance']);

            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $asset->id,
                'user_id' => $userId,
                'action' => 'maintenance_requested',
                'new_values' => $ticket->toArray(),
                'ip_address' => request()->ip(),
            ]);

            return $ticket->load('asset');
        });
    }

    /**
     * Update maintenance ticket (e.g. In Progress, Completed with Actual Cost)
     */
    public function updateTicket(int $ticketId, array $data, int $userId, int $companyId): AssetMaintenance
    {
        return DB::transaction(function () use ($ticketId, $data, $userId, $companyId) {
            $ticket = AssetMaintenance::where('company_id', $companyId)->findOrFail($ticketId);
            $asset = Asset::find($ticket->asset_id);

            $status = $data['status'] ?? $ticket->status;
            if ($status === 'Completed') {
                $data['actual_completion'] = $data['actual_completion'] ?? Carbon::today();
                // Release asset back to Available if not assigned
                if ($asset) {
                    $asset->update(['status' => 'Available', 'condition' => 'Good']);
                }
            } elseif ($status === 'Cancelled' && $asset) {
                $asset->update(['status' => 'Available']);
            }

            $ticket->update($data);

            AssetAuditLog::create([
                'company_id' => $companyId,
                'asset_id' => $ticket->asset_id,
                'user_id' => $userId,
                'action' => 'maintenance_status_' . strtolower($status),
                'new_values' => $ticket->toArray(),
                'ip_address' => request()->ip(),
            ]);

            return $ticket->fresh(['asset']);
        });
    }
}
