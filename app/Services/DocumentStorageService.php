<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentStorageService
{
    protected array $allowedMimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'application/zip',
        'text/csv',
        'text/plain',
    ];

    protected int $maxSizeBytes = 25 * 1024 * 1024; // 25 MB

    /**
     * Store uploaded file securely on disk
     */
    public function storeFile(UploadedFile $file, string $subfolder = 'documents'): array
    {
        $mime = $file->getMimeType();
        $size = $file->getSize();

        if ($size > $this->maxSizeBytes) {
            throw new Exception("File size exceeds 25 MB limit.");
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid() . '.' . $extension;
        $path = $file->storeAs("documents/{$subfolder}", $filename, 'local');

        return [
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size' => $size,
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'file_type' => $extension,
            'disk' => 'local',
        ];
    }
}
