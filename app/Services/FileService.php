<?php

namespace App\Services;

use App\Services\Media\VehicleMediaPolicyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class FileService
{
    private ImageManager $imageManager;

    public function __construct(
        private VehicleMediaPolicyService $mediaPolicyService,
    ) {
        $this->imageManager = new ImageManager(new Driver);
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
     * Upload and optimize a single vehicle image (WebP pipeline, thumbnail included).
     *
     * @return array{url: string, path: string, thumbnail_path: string|null}
     */
    public function uploadVehicleImage(
        UploadedFile $file,
        ?string $disk = null,
        ?string $directory = null
    ): array {
        $profile = config('images.vehicle', []);
        $disk = $disk ?? ($profile['disk'] ?? 'public');
        $directory = $directory ?? ($profile['directory'] ?? 'vehicles');

        $this->validateFile($file);

        if ($this->shouldPreserveAnimatedGif($file)) {
            return $this->storeVehicleImageWithoutConversion($file, $disk, $directory, $profile);
        }

        $processed = $this->processVehicleImage($file, $profile);

        $filename = Str::uuid().'.'.$processed['extension'];
        $path = $directory.'/'.$filename;

        Storage::disk($disk)->put($path, $processed['binary']);

        $url = Storage::disk($disk)->url($path);

        $thumbnailPath = null;
        try {
            $thumbnailPath = $this->createVehicleThumbnail($path, $disk, $profile);
        } catch (\Exception $e) {
            // Thumbnail is optional; main image is already stored.
        }

        return [
            'url' => $url,
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{binary: string, extension: string}
     */
    private function processVehicleImage(UploadedFile $file, array $profile): array
    {
        $maxLongEdge = (int) ($profile['max_long_edge'] ?? 2048);
        $startQuality = (int) ($profile['start_quality'] ?? 82);
        $minQuality = (int) ($profile['min_quality'] ?? 68);
        $targetBytes = (int) ($profile['target_max_bytes'] ?? 819200);

        $image = $this->imageManager->read($file->getPathname());
        $image->orient();
        $image->scaleDown($maxLongEdge, $maxLongEdge);
        $image = $this->applyVehicleWatermarkIfEnabled($image);

        $useWebp = $this->supportsWebp();
        $extension = $useWebp ? 'webp' : 'jpg';
        $quality = $startQuality;
        $binary = $this->encodeVehicleImage($image, $useWebp, $quality);

        while (strlen($binary) > $targetBytes && $quality > $minQuality) {
            $quality = max($minQuality, $quality - 3);
            $binary = $this->encodeVehicleImage($image, $useWebp, $quality);
        }

        $guard = 0;
        while (strlen($binary) > $targetBytes && $guard < 20) {
            $guard++;
            $width = $image->width();
            $height = $image->height();

            if ($width <= 640 || $height <= 640) {
                break;
            }

            $image->scaleDown(
                max(640, (int) floor($width * 0.9)),
                max(640, (int) floor($height * 0.9))
            );
            $binary = $this->encodeVehicleImage($image, $useWebp, $quality);
        }

        return [
            'binary' => $binary,
            'extension' => $extension,
        ];
    }

    private function applyVehicleWatermarkIfEnabled(ImageInterface $image): ImageInterface
    {
        try {
            if (! $this->mediaPolicyService->watermarkEnabled()) {
                return $image;
            }

            $watermarkPath = $this->mediaPolicyService->watermarkImagePath();
            if (! $watermarkPath) {
                return $image;
            }

            $watermark = $this->imageManager->read($watermarkPath);
            $maxWidth = max(80, (int) floor($image->width() * 0.22));
            $watermark->scaleDown($maxWidth, $maxWidth);
            $opacity = $this->mediaPolicyService->watermarkOpacity();

            return $image->place($watermark, 'bottom-right', 12, 12, $opacity);
        } catch (\Throwable) {
            return $image;
        }
    }

    private function encodeVehicleImage(mixed $image, bool $useWebp, int $quality): string
    {
        if ($useWebp) {
            return (string) $image->toWebp(quality: $quality);
        }

        return (string) $image->toJpeg(quality: $quality);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function createVehicleThumbnail(string $mainRelativePath, string $disk, array $profile): string
    {
        $filePath = Storage::disk($disk)->path($mainRelativePath);

        if (! file_exists($filePath)) {
            throw new \RuntimeException('Main image not found for thumbnail generation.');
        }

        $maxLongEdge = (int) ($profile['thumbnail_max_long_edge'] ?? 480);
        $quality = (int) ($profile['thumbnail_quality'] ?? 75);
        $useWebp = $this->supportsWebp();
        $thumbExtension = $useWebp ? 'webp' : 'jpg';

        $image = $this->imageManager->read($filePath);
        $image->scaleDown($maxLongEdge, $maxLongEdge);

        $pathInfo = pathinfo($mainRelativePath);
        $thumbnailDirectory = $pathInfo['dirname'].'/thumbnails';
        $thumbnailFilename = $pathInfo['filename'].'_thumb.'.$thumbExtension;
        $thumbnailPath = $thumbnailDirectory.'/'.$thumbnailFilename;

        Storage::disk($disk)->makeDirectory($thumbnailDirectory);

        $binary = $this->encodeVehicleImage($image, $useWebp, $quality);
        Storage::disk($disk)->put($thumbnailPath, $binary);

        return $thumbnailPath;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{url: string, path: string, thumbnail_path: string|null}
     */
    private function storeVehicleImageWithoutConversion(
        UploadedFile $file,
        string $disk,
        string $directory,
        array $profile
    ): array {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, $disk);
        $url = Storage::disk($disk)->url($path);

        $thumbnailPath = null;
        try {
            $thumbnailPath = $this->createVehicleThumbnail($path, $disk, $profile);
        } catch (\Exception $e) {
            // Continue without thumbnail for animated GIFs if generation fails.
        }

        return [
            'url' => $url,
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
        ];
    }

    private function supportsWebp(): bool
    {
        return function_exists('imagewebp');
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
        $maxSize = 20 * 1024 * 1024; // 20MB

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
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

