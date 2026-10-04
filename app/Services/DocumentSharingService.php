<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\DocumentShare;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DocumentSharingService
{
    /**
     * Create secure share token and link
     */
    public function createShare(int $documentId, array $data, int $userId, int $companyId): array
    {
        $doc = Document::where('company_id', $companyId)->findOrFail($documentId);

        $token = Str::random(48);
        $shareType = $data['share_type'] ?? 'public_link';
        $expiresAt = !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : Carbon::now()->addDays(7);
        $passwordHash = !empty($data['password']) ? Hash::make($data['password']) : null;
        $allowDownload = (bool) ($data['allow_download'] ?? true);

        $share = DocumentShare::create([
            'document_id' => $doc->id,
            'shared_by' => $userId,
            'share_token' => $token,
            'share_type' => $shareType,
            'shared_with_user_id' => $data['shared_with_user_id'] ?? null,
            'shared_with_department_id' => $data['shared_with_department_id'] ?? null,
            'shared_with_role' => $data['shared_with_role'] ?? null,
            'password_hash' => $passwordHash,
            'expires_at' => $expiresAt,
            'allow_download' => $allowDownload,
            'access_count' => 0,
        ]);

        DocumentAuditLog::create([
            'company_id' => $companyId,
            'document_id' => $doc->id,
            'user_id' => $userId,
            'action' => 'shared',
            'new_values' => ['share_type' => $shareType, 'expires_at' => $expiresAt->toDateTimeString()],
            'ip_address' => request()->ip(),
        ]);

        $shareUrl = url('/share/docs/' . $token);

        return [
            'share' => $share,
            'token' => $token,
            'share_url' => $shareUrl,
            'expires_at' => $expiresAt->format('Y-m-d H:i'),
            'has_password' => !empty($passwordHash),
        ];
    }
}
