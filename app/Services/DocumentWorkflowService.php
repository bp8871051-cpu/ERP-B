<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentAuditLog;
use App\Models\DocumentWorkflow;
use App\Models\DocumentWorkflowStep;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DocumentWorkflowService
{
    /**
     * List workflows for company
     */
    public function listWorkflows(int $companyId): array
    {
        return DocumentWorkflow::with(['steps.approverUser', 'department'])
            ->where('company_id', $companyId)
            ->get()
            ->toArray();
    }

    /**
     * Create workflow with sequential steps
     */
    public function createWorkflow(array $data, int $companyId): DocumentWorkflow
    {
        return DB::transaction(function () use ($data, $companyId) {
            $workflow = DocumentWorkflow::create([
                'company_id' => $companyId,
                'department_id' => $data['department_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => true,
            ]);

            $steps = $data['steps'] ?? [];
            if (empty($steps)) {
                $steps = [
                    ['step_name' => 'Initial Reviewer', 'sequence' => 1, 'approver_type' => 'role', 'approver_role' => 'Reviewer', 'sla_hours' => 24],
                    ['step_name' => 'Department Manager', 'sequence' => 2, 'approver_type' => 'role', 'approver_role' => 'Manager', 'sla_hours' => 48],
                    ['step_name' => 'Final Executive Approval', 'sequence' => 3, 'approver_type' => 'role', 'approver_role' => 'Admin', 'sla_hours' => 72],
                ];
            }

            foreach ($steps as $idx => $step) {
                DocumentWorkflowStep::create([
                    'workflow_id' => $workflow->id,
                    'step_name' => $step['step_name'],
                    'sequence' => $step['sequence'] ?? ($idx + 1),
                    'approver_type' => $step['approver_type'] ?? 'role',
                    'approver_id' => $step['approver_id'] ?? null,
                    'approver_role' => $step['approver_role'] ?? null,
                    'approver_department_id' => $step['approver_department_id'] ?? null,
                    'is_required' => $step['is_required'] ?? true,
                    'sla_hours' => $step['sla_hours'] ?? 48,
                ]);
            }

            return $workflow->load('steps');
        });
    }

    /**
     * Attach a document to a workflow and initialize step 1
     */
    public function startDocumentWorkflow(int $documentId, int $workflowId, int $companyId): DocumentApproval
    {
        $doc = Document::where('company_id', $companyId)->findOrFail($documentId);
        $workflow = DocumentWorkflow::with('steps')->where('company_id', $companyId)->findOrFail($workflowId);

        $firstStep = $workflow->steps->sortBy('sequence')->first();
        if (!$firstStep) {
            throw new Exception("Workflow has no steps configured.");
        }

        $doc->update(['status' => 'Pending Review']);

        return DocumentApproval::create([
            'document_id' => $doc->id,
            'workflow_step_id' => $firstStep->id,
            'approver_id' => $firstStep->approver_id,
            'status' => 'pending',
            'comments' => 'Initiated workflow approval: ' . $workflow->name,
        ]);
    }
}
