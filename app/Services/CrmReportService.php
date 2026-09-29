<?php

namespace App\Services;

use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmLead;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmReportService
{
    public function exportContactsCsv(?int $companyId = null): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="crm_contacts_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($companyId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'First Name', 'Last Name', 'Company', 'Email', 'Phone', 'Job Title', 'City', 'State', 'Status', 'Created At']);

            $query = CrmContact::query()->when($companyId, fn($q) => $q->where('company_id', $companyId));
            $query->chunk(200, function ($contacts) use ($handle) {
                foreach ($contacts as $c) {
                    fputcsv($handle, [
                        $c->id,
                        $c->first_name,
                        $c->last_name,
                        $c->company_name,
                        $c->email,
                        $c->phone,
                        $c->job_title,
                        $c->city,
                        $c->state,
                        $c->status,
                        $c->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function exportLeadsCsv(?int $companyId = null): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="crm_leads_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($companyId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Company', 'Email', 'Phone', 'Score', 'Category', 'Status', 'Budget', 'Expected Value', 'Created At']);

            $query = CrmLead::query()->when($companyId, fn($q) => $q->where('company_id', $companyId));
            $query->chunk(200, function ($leads) use ($handle) {
                foreach ($leads as $l) {
                    fputcsv($handle, [
                        $l->id,
                        $l->name,
                        $l->company_name,
                        $l->email,
                        $l->phone,
                        $l->score,
                        $l->score_category,
                        $l->status,
                        $l->budget,
                        $l->expected_value,
                        $l->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function exportDealsCsv(?int $companyId = null): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="crm_deals_' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($companyId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Deal Name', 'Value', 'Currency', 'Probability (%)', 'Expected Revenue', 'Stage', 'Status', 'Close Date', 'Created At']);

            $query = CrmDeal::query()->with('stage')->when($companyId, fn($q) => $q->where('company_id', $companyId));
            $query->chunk(200, function ($deals) use ($handle) {
                foreach ($deals as $d) {
                    fputcsv($handle, [
                        $d->id,
                        $d->name,
                        $d->value,
                        $d->currency,
                        $d->probability,
                        $d->expected_revenue,
                        $d->stage?->name,
                        $d->status,
                        $d->expected_close_date,
                        $d->created_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function importContacts(array $rows, int $companyId, ?int $ownerId = null): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;
            if (empty($row['first_name'])) {
                $failed++;
                $errors[] = ['row' => $rowNum, 'message' => 'First Name is required'];
                continue;
            }

            try {
                CrmContact::create([
                    'company_id' => $companyId,
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'] ?? null,
                    'company_name' => $row['company_name'] ?? null,
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'job_title' => $row['job_title'] ?? null,
                    'city' => $row['city'] ?? null,
                    'state' => $row['state'] ?? null,
                    'country' => $row['country'] ?? 'India',
                    'owner_id' => $ownerId ?? auth()->id(),
                    'status' => 'active',
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $rowNum, 'message' => $e->getMessage()];
            }
        }

        return [
            'total_rows' => count($rows),
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
