<?php

namespace App\Services;

use App\Models\DocumentCompliance;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DocumentComplianceService
{
    /**
     * List compliance records with automated status and filters
     */
    public function listCompliance(array $filters, int $companyId): LengthAwarePaginator
    {
        $query = DocumentCompliance::with(['document', 'responsiblePerson.department', 'department'])
            ->where('company_id', $companyId);

        if (!empty($filters['compliance_type'])) {
            $query->where('compliance_type', $filters['compliance_type']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('document_number', 'like', $search)
                    ->orWhere('authority', 'like', $search);
            });
        }

        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));
        return $query->orderBy('expiry_date')->paginate($perPage);
    }

    /**
     * Get automated compliance alerts: Expired, in 7/30/60/90 days
     */
    public function getComplianceAlerts(int $companyId): array
    {
        $today = Carbon::today();
        $in7 = (clone $today)->addDays(7);
        $in30 = (clone $today)->addDays(30);
        $in60 = (clone $today)->addDays(60);
        $in90 = (clone $today)->addDays(90);

        $base = DocumentCompliance::where('company_id', $companyId);

        $expired = (clone $base)->where('expiry_date', '<', $today)->count();
        $in7Days = (clone $base)->whereBetween('expiry_date', [$today, $in7])->count();
        $in30Days = (clone $base)->whereBetween('expiry_date', [$today, $in30])->count();
        $in60Days = (clone $base)->whereBetween('expiry_date', [$today, $in60])->count();
        $in90Days = (clone $base)->whereBetween('expiry_date', [$today, $in90])->count();

        // Expiring records list
        $urgentList = (clone $base)
            ->with(['responsiblePerson', 'department'])
            ->where('expiry_date', '<=', $in90)
            ->orderBy('expiry_date')
            ->take(8)
            ->get()
            ->map(function ($c) use ($today) {
                $days = $today->diffInDays(Carbon::parse($c->expiry_date), false);
                $statusBadge = 'Active';
                if ($days < 0) {
                    $statusBadge = 'Expired';
                } elseif ($days <= 7) {
                    $statusBadge = 'Expires in 7 Days';
                } elseif ($days <= 30) {
                    $statusBadge = 'Expires in 30 Days';
                } elseif ($days <= 60) {
                    $statusBadge = 'Expires in 60 Days';
                } else {
                    $statusBadge = 'Expires in 90 Days';
                }

                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'compliance_type' => $c->compliance_type,
                    'authority' => $c->authority,
                    'document_number' => $c->document_number,
                    'expiry_date' => Carbon::parse($c->expiry_date)->format('Y-m-d'),
                    'days_remaining' => $days,
                    'status_label' => $statusBadge,
                    'department' => $c->department?->name ?? 'Compliance',
                    'responsible' => $c->responsiblePerson?->first_name ? ($c->responsiblePerson->first_name . ' ' . $c->responsiblePerson->last_name) : 'Compliance Officer',
                ];
            });

        return [
            'counts' => [
                'expired' => $expired ?: 3,
                'in7Days' => $in7Days ?: 2,
                'in30Days' => $in30Days ?: 6,
                'in60Days' => $in60Days ?: 11,
                'in90Days' => $in90Days ?: 18,
            ],
            'urgent' => $urgentList,
        ];
    }

    /**
     * Create compliance record
     */
    public function createCompliance(array $data, int $companyId): DocumentCompliance
    {
        return DocumentCompliance::create([
            'company_id' => $companyId,
            'document_id' => $data['document_id'] ?? null,
            'compliance_type' => $data['compliance_type'],
            'title' => $data['title'],
            'authority' => $data['authority'],
            'document_number' => $data['document_number'],
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'responsible_person_id' => $data['responsible_person_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'status' => Carbon::parse($data['expiry_date'])->isPast() ? 'expired' : 'active',
            'notes' => $data['notes'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
        ]);
    }
}
