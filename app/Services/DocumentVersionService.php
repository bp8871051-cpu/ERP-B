<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\DocumentVersion;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DocumentVersionService
{
    protected DocumentStorageService $storageService;

    public function __construct(DocumentStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Increment version string (e.g. v1.0 -> v1.1, or major v1.1 -> v2.0)
     */
    public function computeNextVersion(string $currentVersion, bool $isMajor = false): string
    {
        $cleaned = ltrim($currentVersion, 'v');
        $parts = explode('.', $cleaned);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        if ($isMajor) {
            return 'v' . ($major + 1) . '.0';
        }
        return 'v' . $major . '.' . ($minor + 1);
    }

    /**
     * Upload a new version for an existing document without overwriting previous versions
     */
    public function uploadNewVersion(int $documentId, UploadedFile $file, array $data, int $userId, int $companyId): DocumentVersion
    {
        return DB::transaction(function () use ($documentId, $file, $data, $userId, $companyId) {
            $doc = Document::where('company_id', $companyId)->findOrFail($documentId);

            $stored = $this->storageService->storeFile($file, 'versions');
            $isMajor = (bool) ($data['is_major_version'] ?? false);
            $newVersionStr = $data['version'] ?? $this->computeNextVersion($doc->current_version, $isMajor);

            $version = DocumentVersion::create([
                'document_id' => $doc->id,
                'version' => $newVersionStr,
                'file_path' => $stored['file_path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'file_hash' => $stored['file_hash'],
                'change_summary' => $data['change_summary'] ?? 'Updated document contents',
                'uploaded_by' => $userId,
            ]);

            // Update main document to point to the latest active file and version
            $doc->update([
                'current_version' => $newVersionStr,
                'file_path' => $stored['file_path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'file_size' => $stored['file_size'],
                'file_hash' => $stored['file_hash'],
                'status' => 'Pending Review',
            ]);

            DocumentAuditLog::create([
                'company_id' => $companyId,
                'document_id' => $doc->id,
                'user_id' => $userId,
                'action' => 'version_added',
                'new_values' => ['version' => $newVersionStr, 'file' => $stored['original_name']],
                'ip_address' => request()->ip(),
            ]);

            return $version->load('uploader');
        });
    }

    /**
     * List all historical versions of a document
     */
    public function getDocumentVersions(int $documentId, int $companyId): array
    {
        $doc = Document::where('company_id', $companyId)->findOrFail($documentId);
        return DocumentVersion::with(['uploader', 'approver'])
            ->where('document_id', $doc->id)
            ->latest()
            ->get()
            ->toArray();
    }
}
