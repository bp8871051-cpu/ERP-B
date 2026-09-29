<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileItem;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(protected FileService $fileService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $folderId = $request->query('folder_id') ? (int) $request->query('folder_id') : null;
        $data = $this->fileService->getFilesAndFolders(
            $request->user(),
            $folderId,
            $request->query('filter'),
            $request->query('search'),
            $request->query('sort_by', 'name'),
            $request->query('sort_order', 'asc')
        );

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max per file
            'folder_id' => 'nullable|integer',
        ]);

        $folderId = $request->input('folder_id') ? (int) $request->input('folder_id') : null;
        $uploaded = $this->fileService->uploadFile($request->user(), $request->file('file'), $folderId);

        return response()->json([
            'status' => 'success',
            'data' => $uploaded,
        ], 201);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer',
            'color' => 'nullable|string',
        ]);

        $folder = $this->fileService->createFolder($request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $folder,
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $file = $this->fileService->updateFile($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $file,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->fileService->deleteFile($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function destroyFolder(int $id, Request $request): JsonResponse
    {
        $result = $this->fileService->deleteFolder($id, $request->user());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    public function download(int $id, Request $request)
    {
        $file = FileItem::findOrFail($id);
        $file->increment('download_count');

        if (Storage::disk($file->disk)->exists($file->file_path)) {
            return Storage::disk($file->disk)->download($file->file_path, $file->name);
        }

        // Fallback for seeded demonstration
        return response($file->name . "\nFalcon ERP Enterprise Storage Stream\nFile ID: " . $file->id, 200, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $file->name . '"',
        ]);
    }

    public function share(int $id, Request $request): JsonResponse
    {
        $result = $this->fileService->shareFile($id, $request->user(), $request->all());

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
