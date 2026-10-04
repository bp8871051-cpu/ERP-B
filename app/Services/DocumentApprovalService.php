<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentAuditLog;
use App\Models\DocumentWorkflowStep;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DocumentApprovalService
{
    /**
     * Approve document step or complete final approval
     */
    public function approve(int $documentId, array $data, int $userId, int $companyId): DocumentApproval
    {
        return DB::transaction(function () use ($documentId, $data, $userId, $companyId) {
            $doc = Document::where('company_id', $companyId)->findOrFail($documentId);

            $pendingApproval = DocumentApproval::where('document_id', $doc->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$pendingApproval) {
                // Direct approval if no formal workflow step active
                $pendingApproval = DocumentApproval::create([
                    'document_id' => $doc->id,
                    'approver_id' => $userId,
                    'status' => 'approved',
                    'comments' => $data['comments'] ?? 'Approved by authorized user',
                    'acted_at' => Carbon::now(),
                ]);

                $doc->update(['status' => 'Approved']);
            } else {
                $pendingApproval->update([
                    'status' => 'approved',
                    'approver_id' => $userId,
                    'comments' => $data['comments'] ?? 'Step approved',
                    'acted_at' => Carbon::now(),
                ]);

                // Check if next step exists in workflow
                if ($pendingApproval->workflow_step_id) {
                    $currentStep = DocumentWorkflowStep::find($pendingApproval->workflow_step_id);
                    $nextStep = DocumentWorkflowStep::where('workflow_id', $currentStep->workflow_id)
                        ->where('sequence', '>', $currentStep->sequence)
                        ->orderBy('sequence')
                        ->first();

                    if ($nextStep) {
                        DocumentApproval::create([
                            'document_id' => $doc->id,
                            'workflow_step_id' => $nextStep->id,
                            'approver_id' => $nextStep->approver_id,
                            'status' => 'pending',
                            'comments' => 'Awaiting approval for step: ' . $nextStep->step_name,
                        ]);
                        $doc->update(['status' => 'Pending Approval']);
                    } else {
                        // All steps approved!
                        $doc->update(['status' => 'Approved']);
                    }
                } else {
                    $doc->update(['status' => 'Approved']);
                }
            }

            DocumentAuditLog::create([
                'company_id' => $companyId,
                'document_id' => $doc->id,
                'user_id' => $userId,
                'action' => 'approved',
                'new_values' => ['status' => 'Approved', 'comments' => $data['comments'] ?? ''],
                'ip_address' => request()->ip(),
            ]);

            return $pendingApproval->fresh(['approver', 'workflowStep']);
        });
    }

    /**
     * Reject document
     */
    public function reject(int $documentId, array $data, int $userId, int $companyId): DocumentApproval
    {
        return DB::transaction(function () use ($documentId, $data, $userId, $companyId) {
            $doc = Document::where('company_id', $companyId)->findOrFail($documentId);

            $approval = DocumentApproval::where('document_id', $doc->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$approval) {
                $approval = DocumentApproval::create([
                    'document_id' => $doc->id,
                    'approver_id' => $userId,
                    'status' => 'rejected',
                    'comments' => $data['comments'] ?? 'Document rejected',
                    'acted_at' => Carbon::now(),
                ]);
            } else {
                $approval->update([
                    'status' => 'rejected',
                    'approver_id' => $userId,
                    'comments' => $data['comments'] ?? 'Step rejected',
                    'acted_at' => Carbon::now(),
                ]);
            }

            $doc->update(['status' => 'Rejected']);

            DocumentAuditLog::create([
                'company_id' => $companyId,
                'document_id' => $doc->id,
                'user_id' => $userId,
                'action' => 'rejected',
                'new_values' => ['status' => 'Rejected', 'reason' => $data['comments'] ?? ''],
                'ip_address' => request()->ip(),
            ]);

            return $approval;
        });
    }

    /**
     * Request changes on document
     */
    public function requestChanges(int $documentId, array $data, int $userId, int $companyId): DocumentApproval
    {
        return DB::transaction(function () use ($documentId, $data, $userId, $companyId) {
            $doc = Document::where('company_id', $companyId)->findOrFail($documentId);

            $approval = DocumentApproval::where('document_id', $doc->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$approval) {
                $approval = DocumentApproval::create([
                    'document_id' => $doc->id,
                    'approver_id' => $userId,
                    'status' => 'changes_requested',
                    'comments' => $data['comments'] ?? 'Changes requested before approval',
                    'acted_at' => Carbon::now(),
                ]);
            } else {
                $approval->update([
                    'status' => 'changes_requested',
                    'approver_id' => $userId,
                    'comments' => $data['comments'] ?? 'Changes requested',
                    'acted_at' => Carbon::now(),
                ]);
            }

            $doc->update(['status' => 'Draft']);

            DocumentAuditLog::create([
                'company_id' => $companyId,
                'document_id' => $doc->id,
                'user_id' => $userId,
                'action' => 'changes_requested',
                'new_values' => ['comments' => $data['comments'] ?? ''],
                'ip_address' => request()->ip(),
            ]);

            return $approval;
        });
    }
}
