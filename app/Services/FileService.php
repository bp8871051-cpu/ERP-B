<?php

namespace App\Services;

use App\Models\FileItem;
use App\Models\FileVersion;
use App\Models\FileShare;
use App\Models\FilePermission;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    public function getFilesAndFolders(User $user, ?int $folderId = null, ?string $filter = null, ?string $search = null, ?string $sortBy = 'name', ?string $sortOrder = 'asc')
    {
        $companyId = $user->company_id ?: 1;

        // Folders query
        $folderQuery = Folder::where('company_id', $companyId)
            ->withCount('files');

        // Files query
        $fileQuery = FileItem::where('company_id', $companyId)
            ->with(['user:id,name,avatar', 'folder:id,name']);

        if ($filter === 'starred' || $filter === 'favorites') {
            $folderQuery->where('is_favorite', true);
            $fileQuery->where('is_favorite', true);
        } elseif ($filter === 'recent') {
            $fileQuery->where('is_recent', true)->orderBy('updated_at', 'desc');
        } elseif ($filter && in_array($filter, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'image', 'zip'])) {
            if ($filter === 'image') {
                $fileQuery->whereIn('file_type', ['jpg', 'jpeg', 'png', 'webp']);
            } else {
                $fileQuery->where('file_type', $filter);
            }
        } else {
            // By folder hierarchy
            $folderQuery->where('parent_id', $folderId);
            $fileQuery->where('folder_id', $folderId);
        }

        if ($search) {
            $folderQuery->where('name', 'like', "%{$search}%");
            $fileQuery->where('name', 'like', "%{$search}%");
        }

        // Sorting
        $allowedSort = ['name', 'file_size', 'created_at', 'updated_at'];
        $sortCol = in_array($sortBy, $allowedSort) ? $sortBy : 'name';
        $order = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

        $folders = $folderQuery->orderBy('name', $order)->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'parentId' => $f->parent_id,
                'color' => $f->color ?: '#0F8B7A',
                'isFavorite' => (bool) $f->is_favorite,
                'filesCount' => $f->files_count,
                'updatedAt' => $f->updated_at->format('M d, Y'),
            ];
        });

        $files = $fileQuery->orderBy($sortCol, $order)->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'folderId' => $f->folder_id,
                'folderName' => $f->folder?->name,
                'type' => strtolower($f->file_type),
                'mimeType' => $f->mime_type,
                'size' => $f->file_size,
                'sizeFormatted' => $this->formatFileSize($f->file_size),
                'path' => $f->file_path,
                'url' => url('/api/v1/files/' . $f->id . '/download'),
                'isFavorite' => (bool) $f->is_favorite,
                'isRecent' => (bool) $f->is_recent,
                'downloadCount' => $f->download_count,
                'owner' => [
                    'id' => $f->user?->id,
                    'name' => $f->user?->name ?? 'System',
                    'avatar' => $f->user?->avatar,
                ],
                'updatedAt' => $f->updated_at->format('M d, Y h:i A'),
            ];
        });

        // Current folder path breadcrumbs
        $breadcrumbs = [];
        if ($folderId) {
            $curr = Folder::find($folderId);
            while ($curr) {
                array_unshift($breadcrumbs, ['id' => $curr->id, 'name' => $curr->name]);
                $curr = $curr->parent_id ? Folder::find($curr->parent_id) : null;
            }
        }

        // Storage usage statistics
        $totalBytesUsed = FileItem::where('company_id', $companyId)->sum('file_size');
        $totalFileCount = FileItem::where('company_id', $companyId)->count();
        $quotaBytes = 50 * 1024 * 1024 * 1024; // 50 GB enterprise quota

        return [
            'folders' => $folders,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
            'currentFolder' => $folderId ? Folder::find($folderId) : null,
            'allFolders' => Folder::where('company_id', $companyId)->select('id', 'name', 'parent_id')->get(),
            'storageStats' => [
                'usedBytes' => $totalBytesUsed,
                'usedFormatted' => $this->formatFileSize($totalBytesUsed),
                'quotaBytes' => $quotaBytes,
                'quotaFormatted' => '50 GB',
                'usagePercent' => round(($totalBytesUsed / $quotaBytes) * 100, 1),
                'fileCount' => $totalFileCount,
            ],
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function createFolder(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;

        return Folder::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#0F8B7A',
            'is_favorite' => false,
        ]);
    }

    public function uploadFile(User $user, UploadedFile $file, ?int $folderId = null)
    {
        $companyId = $user->company_id ?: 1;

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getClientMimeType();
        $size = $file->getSize();

        $storedPath = $file->store('erp_files/' . $companyId, 'public');

        $fileItem = FileItem::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'folder_id' => $folderId,
            'name' => $originalName,
            'disk' => 'public',
            'file_path' => $storedPath,
            'file_type' => $extension ?: 'bin',
            'mime_type' => $mime,
            'file_size' => $size,
            'is_favorite' => false,
            'is_recent' => true,
            'download_count' => 0,
        ]);

        FileVersion::create([
            'file_id' => $fileItem->id,
            'version_number' => 1,
            'file_path' => $storedPath,
            'file_size' => $size,
            'created_by' => $user->id,
        ]);

        return $fileItem;
    }

    public function updateFile(int $id, User $user, array $data)
    {
        $file = FileItem::findOrFail($id);
        $file->update($data);
        return $file;
    }

    public function deleteFile(int $id, User $user)
    {
        $file = FileItem::findOrFail($id);
        $file->delete();
        return ['success' => true];
    }

    public function deleteFolder(int $id, User $user)
    {
        $folder = Folder::findOrFail($id);
        $folder->delete();
        return ['success' => true];
    }

    public function shareFile(int $id, User $user, array $data)
    {
        $file = FileItem::findOrFail($id);
        $token = Str::random(32);

        $share = FileShare::create([
            'company_id' => $file->company_id,
            'file_id' => $file->id,
            'shared_by' => $user->id,
            'shared_with_user_id' => $data['user_id'] ?? null,
            'permission' => $data['permission'] ?? 'view',
            'share_token' => $token,
            'expires_at' => !empty($data['expires_at']) ? now()->parse($data['expires_at']) : now()->addDays(7),
        ]);

        return [
            'share' => $share,
            'shareUrl' => url('/api/v1/files/shared/' . $token),
        ];
    }
}
