<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Support\RemoteUrlGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VehicleImageUploadService
{
    private const REMOTE_TIMEOUT_SECONDS = 30;

    private const MAX_REDIRECTS = 3;

    public function __construct(
        private FileService $fileService
    ) {}

    /**
     * Attach vehicle images from uploads and/or existing URL/path strings.
     *
     * @param  array<int, UploadedFile|string>  $items
     * @return int Next available sort order
     */
    public function attachVehicleImages(Vehicle $vehicle, array $items, int $startSortOrder = 0): int
    {
        $sortOrder = $startSortOrder;

        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $this->attachExistingImage($vehicle, $item, $sortOrder++);
                continue;
            }

            if ($item instanceof UploadedFile && $item->isValid()) {
                $this->attachUploadedImage($vehicle, $item, $sortOrder++);
            }
        }

        return $sortOrder;
    }

    /**
     * Download remote image URLs and store them using the standard vehicle image pipeline.
     *
     * @param  list<string>  $urls
     * @return array{attached: int, warnings: list<array{field: string, value: string, message: string}>}
     */
    public function attachImagesFromRemoteUrls(Vehicle $vehicle, array $urls, int $startSortOrder = 0): array
    {
        $attached = 0;
        $warnings = [];
        $sortOrder = $startSortOrder;
        $maxBytes = (int) config('images.vehicle.remote_max_bytes', 10 * 1024 * 1024);

        foreach ($urls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }

            try {
                $this->attachRemoteImage($vehicle, $url, $sortOrder++);
                $attached++;
            } catch (\Throwable $e) {
                Log::warning('Vehicle import image download failed', [
                    'vehicle_id' => $vehicle->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
                $warnings[] = [
                    'field' => 'image_urls',
                    'value' => $url,
                    'message' => __('messages.api.vehicle_import_image_failed', ['url' => $url]),
                ];
            }

            if ($maxBytes > 0) {
                // Guard enforced inside attachRemoteImage via HTTP size check.
            }
        }

        return ['attached' => $attached, 'warnings' => $warnings];
    }

    /**
     * @return array<int, VehicleImage>
     */
    public function uploadVehicleImages(Vehicle $vehicle, array $files, int $startSortOrder = 0): array
    {
        $uploaded = [];
        $sortOrder = $startSortOrder;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $uploaded[] = $this->attachUploadedImage($vehicle, $file, $sortOrder++);
        }

        return $uploaded;
    }

    private function attachRemoteImage(Vehicle $vehicle, string $url, int $sortOrder): VehicleImage
    {
        $maxBytes = (int) config('images.vehicle.remote_max_bytes', 10 * 1024 * 1024);
        $response = $this->fetchRemoteImageResponse($url);

        if (! $response->successful()) {
            throw new \RuntimeException(__('messages.api.vehicle_import_image_http_error', ['status' => $response->status()]));
        }

        $body = $response->body();
        if ($body === '' || strlen($body) > $maxBytes) {
            throw new \RuntimeException(__('messages.api.vehicle_import_image_too_large'));
        }

        $mime = $response->header('Content-Type');
        $mime = is_array($mime) ? ($mime[0] ?? '') : (string) $mime;
        $mime = strtolower(trim(explode(';', $mime)[0]));
        $extension = $this->extensionFromMime($mime) ?? $this->extensionFromUrl($url);
        if ($extension === null) {
            throw new \RuntimeException(__('messages.api.vehicle_import_image_invalid_type'));
        }

        $tmp = tempnam(sys_get_temp_dir(), 'veh_img_');
        if ($tmp === false) {
            throw new \RuntimeException(__('messages.api.vehicle_import_image_temp_failed'));
        }

        $tmpPath = $tmp.'.'.$extension;
        @unlink($tmp);
        file_put_contents($tmpPath, $body);

        try {
            $uploadedFile = new UploadedFile(
                $tmpPath,
                'import_'.$vehicle->id.'_'.$sortOrder.'.'.$extension,
                $mime !== '' ? $mime : 'application/octet-stream',
                null,
                true
            );

            return $this->attachUploadedImage($vehicle, $uploadedFile, $sortOrder);
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Fetch a remote image while validating every redirect hop before following it.
     *
     * @return \Illuminate\Http\Client\Response
     */
    private function fetchRemoteImageResponse(string $url)
    {
        $currentUrl = $url;
        RemoteUrlGuard::assertPublicHttpUrl($currentUrl);
        $redirects = 0;

        while (true) {
            $response = Http::timeout(self::REMOTE_TIMEOUT_SECONDS)
                ->withOptions([
                    'allow_redirects' => false,
                    'http_errors' => false,
                ])
                ->get($currentUrl);

            if (! $response->redirect()) {
                return $response;
            }

            if ($redirects >= self::MAX_REDIRECTS) {
                throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_blocked_host'));
            }

            $location = $response->header('Location');
            $location = is_array($location) ? ($location[0] ?? '') : (string) $location;
            $location = trim($location);
            if ($location === '') {
                throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_blocked_host'));
            }

            $currentUrl = $this->resolveRedirectUrl($currentUrl, $location);
            RemoteUrlGuard::assertPublicHttpUrl($currentUrl);
            $redirects++;
        }
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }

        $parts = parse_url($currentUrl);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \InvalidArgumentException(__('messages.api.vehicle_import_image_invalid_url'));
        }

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $base = $scheme.'://'.$host.$port;

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $base.$location;
        }

        $path = $parts['path'] ?? '/';
        $dir = preg_replace('#/[^/]*$#', '/', $path) ?: '/';

        return $base.$dir.$location;
    }

    private function extensionFromMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };
    }

    private function extensionFromUrl(string $url): ?string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
            ? ($ext === 'jpeg' ? 'jpg' : $ext)
            : null;
    }

    private function attachUploadedImage(Vehicle $vehicle, UploadedFile $file, int $sortOrder): VehicleImage
    {
        $result = $this->fileService->uploadVehicleImage($file);

        return VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'image_path' => $result['path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'sort_order' => $sortOrder,
        ]);
    }

    private function attachExistingImage(Vehicle $vehicle, string $fileUrl, int $sortOrder): VehicleImage
    {
        $imagePath = str_replace('/storage/', '', parse_url($fileUrl, PHP_URL_PATH) ?? $fileUrl);

        $thumbnailPath = null;
        try {
            $thumbnailUrl = $this->fileService->createThumbnail($fileUrl, 300, 300, 'public');
            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH) ?? '');
        } catch (\Exception $e) {
            // Existing images may already have thumbnails elsewhere; continue without one.
        }

        return VehicleImage::create([
            'vehicle_id' => $vehicle->id,
            'image_path' => $imagePath,
            'thumbnail_path' => $thumbnailPath ?: null,
            'sort_order' => $sortOrder,
        ]);
    }
}
