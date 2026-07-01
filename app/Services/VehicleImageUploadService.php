<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\UploadedFile;

class VehicleImageUploadService
{
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
