<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;

class FileService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload files to storage
     * 
     * @param array $files Array of UploadedFile instances or URLs
     * @param string $disk Storage disk
     * @param string $directory Directory to store files
     * @param bool $createThumbnails Whether to create thumbnails for images
     * @param bool $optimizeImages Whether to optimize images
     * @param int|null $thumbnailWidth Thumbnail width (default: 300)
     * @param int|null $thumbnailHeight Thumbnail height (default: 300)
     * @return array Array of uploaded file URLs
     */
    public function uploadFiles(
        array $files, 
        string $disk = 'public', 
        string $directory = 'uploads',
        bool $createThumbnails = false,
        bool $optimizeImages = false,
        ?int $thumbnailWidth = 300,
        ?int $thumbnailHeight = 300
    ): array {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                // Validate file first
                $this->validateFile($file);
                
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($directory, $filename, $disk);
                $fileUrl = Storage::disk($disk)->url($path);
                
                // Optimize image if requested and it's an image
                if ($optimizeImages && $this->isImageFile($file)) {
                    $fileUrl = $this->optimizeImage($fileUrl, 85, null, null, $disk);
                }
                
                // Create thumbnail if requested and it's an image
                if ($createThumbnails && $this->isImageFile($file)) {
                    $this->createThumbnail($fileUrl, $thumbnailWidth, $thumbnailHeight, $disk);
                }
                
                $uploadedFiles[] = $fileUrl;
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
                // Check if it's a URL or already a path
                $parsedUrl = parse_url($url);
                
                if (isset($parsedUrl['scheme']) || isset($parsedUrl['host'])) {
                    // It's a URL - extract path from URL
                    $path = $parsedUrl['path'] ?? '';
                    // Remove /storage prefix if present
                    $path = str_replace('/storage/', '', $path);
                } else {
                    // It's already a path - use it directly
                    $path = $url;
                    // Remove /storage prefix if present (in case it was included)
                    $path = str_replace('/storage/', '', $path);
                }
                
                // Try to delete from public disk
                if (!empty($path) && Storage::disk('public')->exists($path)) {
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

    /**
     * Retrieve file path/URL and verify existence
     *
     * @param string $fileUrl File URL to retrieve
     * @param string $disk Storage disk
     * @return string|null File path or null if not found
     */
    public function retrieveFile(string $fileUrl, string $disk = 'public'): ?string
    {
        if (empty($fileUrl)) {
            return null;
        }

        // Extract path from URL
        $path = parse_url($fileUrl, PHP_URL_PATH);
        
        // Remove /storage prefix if present
        $path = str_replace('/storage/', '', $path);
        
        // Check if file exists
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->path($path);
        }

        return null;
    }

    /**
     * Update/replace existing file with new one
     *
     * @param string $oldFileUrl URL of the file to replace
     * @param UploadedFile $newFile New file to upload
     * @param string $disk Storage disk
     * @param string $directory Directory to store files
     * @return string URL of the new file
     */
    public function updateFile(
        string $oldFileUrl, 
        UploadedFile $newFile, 
        string $disk = 'public', 
        string $directory = 'uploads'
    ): string {
        // Delete old file
        $this->deleteFiles([$oldFileUrl]);
        
        // Delete old thumbnail if it exists
        $thumbnailUrl = $this->getThumbnailUrl($oldFileUrl);
        if ($thumbnailUrl) {
            $this->deleteFiles([$thumbnailUrl]);
        }
        
        // Upload new file
        $newFileUrls = $this->uploadFiles([$newFile], $disk, $directory);
        
        return $newFileUrls[0] ?? '';
    }

    /**
     * Create thumbnail from image URL
     *
     * @param string $fileUrl URL of the image file
     * @param int $width Thumbnail width (default: 300)
     * @param int $height Thumbnail height (default: 300)
     * @param string $disk Storage disk
     * @return string URL of the created thumbnail
     */
    public function createThumbnail(
        string $fileUrl, 
        int $width = 300, 
        int $height = 300, 
        string $disk = 'public'
    ): string {
        $filePath = $this->retrieveFile($fileUrl, $disk);
        
        if (!$filePath || !$this->isImageUrl($fileUrl)) {
            throw new \Exception('File not found or is not an image');
        }

        try {
            // Load image
            $image = $this->imageManager->read($filePath);
            
            // Resize maintaining aspect ratio
            $image->scaleDown($width, $height);
            
            // Extract path info to create thumbnail path
            $path = parse_url($fileUrl, PHP_URL_PATH);
            $path = str_replace('/storage/', '', $path);
            $pathInfo = pathinfo($path);
            $thumbnailDirectory = $pathInfo['dirname'] . '/thumbnails';
            $thumbnailFilename = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            $thumbnailPath = $thumbnailDirectory . '/' . $thumbnailFilename;
            
            // Ensure thumbnail directory exists
            Storage::disk($disk)->makeDirectory($thumbnailDirectory);
            
            // Save thumbnail
            $thumbnailFullPath = Storage::disk($disk)->path($thumbnailPath);
            $image->save($thumbnailFullPath, quality: 85);
            
            // Return thumbnail URL
            return Storage::disk($disk)->url($thumbnailPath);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create thumbnail: ' . $e->getMessage());
        }
    }

    /**
     * Optimize image size and quality
     *
     * @param string $fileUrl URL of the image file
     * @param int $quality Image quality (1-100, default: 85)
     * @param int|null $maxWidth Maximum width (null = no limit)
     * @param int|null $maxHeight Maximum height (null = no limit)
     * @param string $disk Storage disk
     * @return string URL of the optimized image (returns original URL if optimization fails)
     */
    public function optimizeImage(
        string $fileUrl, 
        int $quality = 85, 
        ?int $maxWidth = null, 
        ?int $maxHeight = null, 
        string $disk = 'public'
    ): string {
        $filePath = $this->retrieveFile($fileUrl, $disk);
        
        if (!$filePath || !$this->isImageUrl($fileUrl)) {
            return $fileUrl; // Return original if not found or not an image
        }

        try {
            // Load image
            $image = $this->imageManager->read($filePath);
            
            // Resize if max dimensions specified
            if ($maxWidth !== null || $maxHeight !== null) {
                $image->scaleDown($maxWidth ?? PHP_INT_MAX, $maxHeight ?? PHP_INT_MAX);
            }
            
            // Save with optimized quality (replace original)
            $image->save($filePath, quality: $quality);
            
            return $fileUrl; // Return same URL as original is replaced
        } catch (\Exception $e) {
            // If optimization fails, return original URL
            return $fileUrl;
        }
    }

    /**
     * Check if file is an image
     *
     * @param UploadedFile $file
     * @return bool
     */
    private function isImageFile(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    /**
     * Check if URL is an image
     *
     * @param string $url
     * @return bool
     */
    private function isImageUrl(string $url): bool
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    /**
     * Get thumbnail URL from original file URL
     *
     * @param string $fileUrl
     * @return string|null
     */
    private function getThumbnailUrl(string $fileUrl): ?string
    {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $path = str_replace('/storage/', '', $path);
        $pathInfo = pathinfo($path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::disk('public')->url($thumbnailPath);
        }
        
        return null;
    }
}

