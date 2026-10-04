<?php

namespace App\Services;

use App\Models\DeleteRequest;
use App\Models\SystemAuditLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DeleteRequestService
{
    /**
     * Get paginated delete requests
     */
    public function getRequests(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = DeleteRequest::where('company_id', $companyId)
            ->with(['requester', 'reviewer']);

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('record_title', 'like', "%{$s}%")
                    ->orWhere('reason', 'like', "%{$s}%")
                    ->orWhere('module', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['module']) && $filters['module'] !== 'all') {
            $query->where('module', $filters['module']);
        }

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 15)));
        return $query->latest()->paginate($perPage);
    }

    /**
     * Submit new deletion request
     */
    public function submitRequest(array $data, int $companyId = 1, ?int $userId = null): DeleteRequest
    {
        $request = DeleteRequest::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'module' => $data['module'],
            'record_id' => $data['record_id'],
            'record_title' => $data['record_title'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        SystemAuditLog::log('system', 'create_delete_request', (string) $request->id, null, $request->toArray(), $companyId, $userId);

        return $request->load('requester');
    }

    /**
     * Approve and execute soft delete
     */
    public function approveRequest(int $id, ?string $notes = null, int $companyId = 1, ?int $adminId = null): DeleteRequest
    {
        return DB::transaction(function () use ($id, $notes, $companyId, $adminId) {
            $request = DeleteRequest::where('company_id', $companyId)->findOrFail($id);

            // Dynamically soft delete target record if model supports it
            $modelClass = $this->resolveModelClass($request->module);
            if ($modelClass && class_exists($modelClass)) {
                $record = $modelClass::find($request->record_id);
                if ($record) {
                    $record->delete();
                }
            }

            $request->status = 'approved';
            $request->reviewed_by = $adminId;
            $request->reviewed_at = Carbon::now();
            $request->review_notes = $notes ?: 'Approved and soft-deleted safely.';
            $request->save();

            SystemAuditLog::log('system', 'approve_delete_request', (string) $request->id, null, ['module' => $request->module, 'record_id' => $request->record_id], $companyId, $adminId);

            return $request->load(['requester', 'reviewer']);
        });
    }

    /**
     * Reject deletion request
     */
    public function rejectRequest(int $id, string $reason, int $companyId = 1, ?int $adminId = null): DeleteRequest
    {
        $request = DeleteRequest::where('company_id', $companyId)->findOrFail($id);
        $request->status = 'rejected';
        $request->reviewed_by = $adminId;
        $request->reviewed_at = Carbon::now();
        $request->review_notes = $reason;
        $request->save();

        SystemAuditLog::log('system', 'reject_delete_request', (string) $request->id, null, ['reason' => $reason], $companyId, $adminId);

        return $request->load(['requester', 'reviewer']);
    }

    /**
     * Map module name to Eloquent Model
     */
    protected function resolveModelClass(string $module): ?string
    {
        $map = [
            'products' => \App\Models\Product::class,
            'customers' => \App\Models\Customer::class,
            'vendors' => \App\Models\Vendor::class,
            'users' => \App\Models\User::class,
            'assets' => \App\Models\Asset::class,
            'documents' => \App\Models\Document::class,
            'tickets' => \App\Models\Ticket::class,
            'memberships' => \App\Models\Membership::class,
        ];

        return $map[strtolower($module)] ?? null;
    }
}
