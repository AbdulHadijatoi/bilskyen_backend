<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class FileService
{
    private const KIB = 1024;

    private const MIB = 1048576;

    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * Upload files to storage
     *
     * @param  array  $files  Array of UploadedFile instances or URLs
     * @param  string  $disk  Storage disk
     * @param  string  $directory  Directory to store files
     * @param  bool  $createThumbnails  Whether to create thumbnails for images
     * @param  bool  $optimizeImages  Unused; images are always optimized for web via optimizeImageForWeb
     * @param  int|null  $thumbnailWidth  Thumbnail width (default: 300)
     * @param  int|null  $thumbnailHeight  Thumbnail height (default: 300)
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
        assert(\is_bool($optimizeImages));

        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                // Validate file first
                $this->validateFile($file);

                $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs($directory, $filename, $disk);
                $fileUrl = Storage::disk($disk)->url($path);

                $originalBytes = $file->getSize();
                if ($this->isImageFile($file)) {
                    $fileUrl = $this->optimizeImageForWeb($fileUrl, $originalBytes, $disk);
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
                if (! empty($path) && Storage::disk('public')->exists($path)) {
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
        $maxSize = 20 * 1024 * 1024; // 20MB

        if (! in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed types: '.implode(', ', $allowedTypes));
        }

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size of 20MB');
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
     * @param  string  $fileUrl  File URL to retrieve
     * @param  string  $disk  Storage disk
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
     * Max encoded file size target from original upload size (piecewise caps for web delivery).
     */
    public function resolveWebTargetMaxBytes(int $originalBytes): int
    {
        $orig = max(0, $originalBytes);
        $mb10 = 10 * self::MIB;
        $mb5 = 5 * self::MIB;
        $mb3 = 3 * self::MIB;
        $target15 = (int) round(1.5 * self::MIB);
        $target1 = self::MIB;
        $targetAt3 = (int) round(0.65 * self::MIB);

        if ($orig >= $mb10) {
            return $target15;
        }

        if ($orig >= $mb5) {
            $t = ($orig - $mb5) / ($mb10 - $mb5);

            return (int) round($target1 + $t * ($target15 - $target1));
        }

        if ($orig >= $mb3) {
            $t = ($orig - $mb3) / ($mb5 - $mb3);

            return (int) round($targetAt3 + $t * ($target1 - $targetAt3));
        }

        $floor = 100 * self::KIB;

        return max((int) round($orig * 0.33), $floor);
    }

    /**
     * Reduce stored image size toward resolveWebTargetMaxBytes() using quality steps then scaling.
     */
    public function optimizeImageForWeb(string $fileUrl, int $originalUploadBytes, string $disk = 'public'): string
    {
        $filePath = $this->retrieveFile($fileUrl, $disk);

        if (! $filePath || ! $this->isImageUrl($fileUrl)) {
            return $fileUrl;
        }

        if ($this->isAnimatedGifFile($filePath)) {
            return $fileUrl;
        }

        $targetBytes = $this->resolveWebTargetMaxBytes($originalUploadBytes);

        try {
            $currentSize = file_exists($filePath) ? filesize($filePath) : false;
            if ($currentSize !== false && $currentSize <= $targetBytes) {
                return $fileUrl;
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $usesQuality = in_array($extension, ['jpg', 'jpeg', 'webp'], true);

            $quality = 85;
            $minQuality = 45;

            while ($usesQuality && $quality >= $minQuality) {
                $currentSize = file_exists($filePath) ? filesize($filePath) : false;
                if ($currentSize !== false && $currentSize <= $targetBytes) {
                    return $fileUrl;
                }

                $image = $this->imageManager->read($filePath);
                $image->save($filePath, quality: $quality);
                $currentSize = file_exists($filePath) ? filesize($filePath) : false;
                if ($currentSize !== false && $currentSize <= $targetBytes) {
                    return $fileUrl;
                }
                $quality -= 5;
            }

            $scaleFactor = 0.92;
            $guard = 0;

            while ($guard < 35) {
                $currentSize = file_exists($filePath) ? filesize($filePath) : false;
                if ($currentSize !== false && $currentSize <= $targetBytes) {
                    return $fileUrl;
                }

                $guard++;
                $image = $this->imageManager->read($filePath);
                $w = $image->width();
                $h = $image->height();

                if ($w <= 32 || $h <= 32) {
                    break;
                }

                $newW = max(32, (int) floor($w * $scaleFactor));
                $newH = max(32, (int) floor($h * $scaleFactor));

                $image->scaleDown($newW, $newH);

                if ($usesQuality) {
                    $image->save($filePath, quality: max($minQuality, $quality));
                } else {
                    $image->save($filePath);
                }
            }
        } catch (\Exception $e) {
            return $fileUrl;
        }

        return $fileUrl;
    }

    /**
     * Update/replace existing file with new one
     *
     * @param  string  $oldFileUrl  URL of the file to replace
     * @param  UploadedFile  $newFile  New file to upload
     * @param  string  $disk  Storage disk
     * @param  string  $directory  Directory to store files
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
     * @param  string  $fileUrl  URL of the image file
     * @param  int  $width  Thumbnail width (default: 300)
     * @param  int  $height  Thumbnail height (default: 300)
     * @param  string  $disk  Storage disk
     * @return string URL of the created thumbnail
     */
    public function createThumbnail(
        string $fileUrl,
        int $width = 300,
        int $height = 300,
        string $disk = 'public'
    ): string {
        $filePath = $this->retrieveFile($fileUrl, $disk);

        if (! $filePath || ! $this->isImageUrl($fileUrl)) {
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
            $thumbnailDirectory = $pathInfo['dirname'].'/thumbnails';
            $thumbnailFilename = $pathInfo['filename'].'_thumb.'.$pathInfo['extension'];
            $thumbnailPath = $thumbnailDirectory.'/'.$thumbnailFilename;

            // Ensure thumbnail directory exists
            Storage::disk($disk)->makeDirectory($thumbnailDirectory);

            // Save thumbnail
            $thumbnailFullPath = Storage::disk($disk)->path($thumbnailPath);
            $image->save($thumbnailFullPath, quality: 85);

            // Return thumbnail URL
            return Storage::disk($disk)->url($thumbnailPath);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create thumbnail: '.$e->getMessage());
        }
    }

    /**
     * Optimize image size and quality
     *
     * @param  string  $fileUrl  URL of the image file
     * @param  int  $quality  Image quality (1-100, default: 85)
     * @param  int|null  $maxWidth  Maximum width (null = no limit)
     * @param  int|null  $maxHeight  Maximum height (null = no limit)
     * @param  string  $disk  Storage disk
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

        if (! $filePath || ! $this->isImageUrl($fileUrl)) {
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
     */
    private function isImageFile(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    /**
     * Check if URL is an image
     */
    private function isImageUrl(string $url): bool
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    private function isAnimatedGifFile(string $absolutePath): bool
    {
        if (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)) !== 'gif') {
            return false;
        }

        $head = @file_get_contents($absolutePath, false, null, 0, 131072);
        if ($head === false || $head === '') {
            return false;
        }

        return str_contains($head, 'NETSCAPE2.0');
    }

    /**
     * Get thumbnail URL from original file URL
     */
    private function getThumbnailUrl(string $fileUrl): ?string
    {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $path = str_replace('/storage/', '', $path);
        $pathInfo = pathinfo($path);
        $thumbnailPath = $pathInfo['dirname'].'/thumbnails/'.$pathInfo['filename'].'_thumb.'.$pathInfo['extension'];

        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::disk('public')->url($thumbnailPath);
        }

        return null;
    }
}
