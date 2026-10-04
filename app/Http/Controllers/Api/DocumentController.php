<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentFolder;
use App\Services\DocumentApprovalService;
use App\Services\DocumentComplianceService;
use App\Services\DocumentService;
use App\Services\DocumentSharingService;
use App\Services\DocumentVersionService;
use App\Services\DocumentWorkflowService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    protected DocumentService $documentService;
    protected DocumentVersionService $versionService;
    protected DocumentApprovalService $approvalService;
    protected DocumentWorkflowService $workflowService;
    protected DocumentComplianceService $complianceService;
    protected DocumentSharingService $sharingService;

    public function __construct(
        DocumentService $documentService,
        DocumentVersionService $versionService,
        DocumentApprovalService $approvalService,
        DocumentWorkflowService $workflowService,
        DocumentComplianceService $complianceService,
        DocumentSharingService $sharingService
    ) {
        $this->documentService = $documentService;
        $this->versionService = $versionService;
        $this->approvalService = $approvalService;
        $this->workflowService = $workflowService;
        $this->complianceService = $complianceService;
        $this->sharingService = $sharingService;
    }

    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?: 1);
    }

    /**
     * Document Management Dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $data = $this->documentService->getDashboardData($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * List Documents
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $documents = $this->documentService->listDocuments($request->all(), $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $documents,
        ]);
    }

    /**
     * Upload & Create Document
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'nullable|file|max:25600',
        ]);

        try {
            $file = $request->file('file');
            $doc = $this->documentService->createDocument($request->all(), $file, $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Document uploaded and registered successfully.',
                'data' => $doc,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show Document Details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $doc = $this->documentService->getDocumentDetails($id, $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $doc,
        ]);
    }

    /**
     * Update Document metadata
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $doc = Document::where('company_id', $companyId)->findOrFail($id);

        $doc->update($request->only([
            'name',
            'folder_id',
            'category_id',
            'department_id',
            'confidentiality',
            'status',
            'issue_date',
            'expiry_date',
            'tags',
            'description',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Document details updated.',
            'data' => $doc->fresh(['folder', 'category', 'department']),
        ]);
    }

    /**
     * Delete / Archive Document
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $doc = Document::where('company_id', $companyId)->findOrFail($id);
        $doc->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document archived successfully.',
        ]);
    }

    /**
     * Upload new version for existing document
     */
    public function uploadVersion(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        $request->validate([
            'file' => 'required|file|max:25600',
            'change_summary' => 'nullable|string',
        ]);

        try {
            $version = $this->versionService->uploadNewVersion($id, $request->file('file'), $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'New document version uploaded successfully.',
                'data' => $version,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get document version history
     */
    public function versions(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $versions = $this->versionService->getDocumentVersions($id, $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $versions,
        ]);
    }

    /**
     * Download document file
     */
    public function download(Request $request, int $id)
    {
        $companyId = $this->getCompanyId($request);
        $doc = Document::where('company_id', $companyId)->findOrFail($id);

        if (!$doc->file_path || !Storage::disk('local')->exists($doc->file_path)) {
            // Return dummy stream or 404 message
            return response()->json([
                'status' => 'error',
                'message' => 'File not found on storage disk.',
            ], 404);
        }

        return Storage::disk('local')->download($doc->file_path, $doc->original_name ?: $doc->name);
    }

    /**
     * Share document
     */
    public function share(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $share = $this->sharingService->createShare($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Share link generated successfully.',
                'data' => $share,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve Document
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $approval = $this->approvalService->approve($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Document approved successfully.',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject Document
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $approval = $this->approvalService->reject($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Document rejected.',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Request Changes
     */
    public function requestChanges(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $userId = $request->user()?->id ?: 1;

        try {
            $approval = $this->approvalService->requestChanges($id, $request->all(), $userId, $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Change request submitted.',
                'data' => $approval,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Policies & Manuals List
     */
    public function policies(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $policies = $this->documentService->listPolicies($request->all(), $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $policies,
        ]);
    }

    /**
     * Create Policy
     */
    public function storePolicy(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        try {
            $policy = $this->documentService->createPolicy($request->all(), $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Policy document created successfully.',
                'data' => $policy,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Compliance Documents List
     */
    public function compliance(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $records = $this->complianceService->listCompliance($request->all(), $companyId);
        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }

    /**
     * Create Compliance Document
     */
    public function storeCompliance(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $request->validate([
            'title' => 'required|string',
            'compliance_type' => 'required|string',
            'authority' => 'required|string',
            'document_number' => 'required|string',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date',
        ]);

        try {
            $record = $this->complianceService->createCompliance($request->all(), $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Compliance document registered successfully.',
                'data' => $record,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Compliance Expiry Alerts
     */
    public function complianceAlerts(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $alerts = $this->complianceService->getComplianceAlerts($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $alerts,
        ]);
    }

    /**
     * Document Workflows List
     */
    public function workflows(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $workflows = $this->workflowService->listWorkflows($companyId);
        return response()->json([
            'status' => 'success',
            'data' => $workflows,
        ]);
    }

    /**
     * Create Document Workflow
     */
    public function storeWorkflow(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $request->validate([
            'name' => 'required|string',
        ]);

        try {
            $wf = $this->workflowService->createWorkflow($request->all(), $companyId);
            return response()->json([
                'status' => 'success',
                'message' => 'Approval workflow configured.',
                'data' => $wf,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Document Folders Tree
     */
    public function folders(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $folders = $this->documentService->getFoldersTree($companyId);
        $categories = DocumentCategory::where('company_id', $companyId)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'folders' => $folders,
                'categories' => $categories,
            ],
        ]);
    }
}
