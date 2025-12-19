<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FileUploadController extends Controller
{
    public function __construct(
        private FileService $fileService
    ) {}

    /**
     * Upload file
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'pathname' => 'required|uuid',
            'file' => 'required|file|mimes:jpeg,png,webp,gif|max:4096',
        ]);

        $file = $request->file('file');
        $this->fileService->validateFile($file);

        $urls = $this->fileService->uploadFiles([$file]);

        return response()->json([
            'url' => $urls[0] ?? null,
        ]);
    }

    /**
     * Delete files
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|string|url',
        ]);

        $this->fileService->deleteFiles($request->input('files'));

        return response()->json([
            'message' => 'Files deleted successfully',
        ]);
    }
}

