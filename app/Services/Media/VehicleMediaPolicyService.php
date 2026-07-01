<?php

namespace App\Services\Media;

use App\Services\PlatformSettingService;

class VehicleMediaPolicyService
{
    public function __construct(
        private PlatformSettingService $platformSettingService
    ) {}

    public function minImagesBeforePublish(): int
    {
        return (int) $this->platformSettingService->get('media', 'min_images_before_publish', 0);
    }

    public function maxImageUploadMb(): int
    {
        return max(1, (int) $this->platformSettingService->get('media', 'max_image_upload_mb', 10));
    }

    public function maxImageUploadBytes(): int
    {
        return $this->maxImageUploadMb() * 1024 * 1024;
    }

    public function watermarkEnabled(): bool
    {
        return filter_var($this->platformSettingService->get('media', 'watermark_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function watermarkOpacity(): int
    {
        return min(100, max(0, (int) $this->platformSettingService->get('media', 'watermark_opacity', 40)));
    }

    public function watermarkImagePath(): ?string
    {
        $configured = trim((string) $this->platformSettingService->get('media', 'watermark_image_path', ''));
        if ($configured !== '') {
            if (is_file($configured)) {
                return $configured;
            }
            $storage = storage_path('app/'.$configured);
            if (is_file($storage)) {
                return $storage;
            }
            $public = public_path($configured);
            if (is_file($public)) {
                return $public;
            }
        }

        $default = public_path('watermark.png');
        if (is_file($default)) {
            return $default;
        }

        return null;
    }

    public function assertCanPublish(int $imageCount): void
    {
        $min = $this->minImagesBeforePublish();
        if ($min > 0 && $imageCount < $min) {
            throw new \RuntimeException(__('messages.api.min_images_before_publish', ['min' => $min]));
        }
    }

    public static function detectVideoProvider(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($url, 'vimeo.com')) {
            return 'vimeo';
        }

        return 'external';
    }
}
