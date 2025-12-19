<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Upload files to storage
     */
    public function uploadFiles(array $files, string $disk = 'public', string $directory = 'uploads'): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($directory, $filename, $disk);
                $uploadedFiles[] = Storage::disk($disk)->url($path);
            } elseif (is_string($file)) {
                // Already a URL, keep as is
                $uploadedFiles[] = $file;
            }
        }

        return $uploadedFiles;
    }

    /**
     * Delete files from storage
     */
    public function deleteFiles(array $fileUrls): void
    {
        foreach ($fileUrls as $url) {
            if (is_string($url)) {
                // Extract path from URL
                $path = parse_url($url, PHP_URL_PATH);
                
                // Remove /storage prefix if present
                $path = str_replace('/storage/', '', $path);
                
                // Try to delete from public disk
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }

    /**
     * Validate file
     */
    public function validateFile(UploadedFile $file): void
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 4 * 1024 * 1024; // 4MB

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of 4MB');
        }
    }

    /**
     * Validate multiple files
     */
    public function validateFiles(array $files): void
    {
        if (count($files) > 20) {
            throw new \Exception('Maximum 20 files allowed');
        }

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $this->validateFile($file);
            }
        }
    }
}

