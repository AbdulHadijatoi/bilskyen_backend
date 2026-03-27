<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\DmrBodyType;
use App\Models\DmrBrand;
use App\Models\DmrColour;
use App\Models\DmrDriveEnergy;
use App\Models\DmrEmissionNorm;
use App\Models\DmrModel;
use App\Models\DmrVehicleUse;
use App\Constants\VehicleListStatus;
use App\Services\FileService;
use App\Services\OwnershipTaxService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    public function __construct(
        private FileService $fileService,
        private OwnershipTaxService $ownershipTaxService,
    ) {}

    /**
     * Create a vehicle (DMR-linked listing row).
     */
    public function createVehicle(array $vehicleData): Vehicle
    {
        $equipmentIds = null;
        if (isset($vehicleData['equipment_ids']) && is_array($vehicleData['equipment_ids'])) {
            $equipmentIds = $vehicleData['equipment_ids'];
            unset($vehicleData['equipment_ids']);
        }

        $extraDescription = '';
        $detailsFields = [
            // add details fields here
        ];
        foreach ($detailsFields as $field) {
            if (isset($vehicleData[$field])) {
                if ($field === 'description' && empty($vehicleData['description'])) {
                    $vehicleData['description'] = (string) $vehicleData[$field];
                } elseif ($field === 'description') {
                    $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '') . "\n\n" . (string) $vehicleData[$field]);
                } elseif ($field === 'condition_id' && ! isset($vehicleData['condition_id'])) {
                    $vehicleData['condition_id'] = $vehicleData[$field];
                } elseif ($field === 'servicebog' && ! isset($vehicleData['servicebog'])) {
                    $vehicleData['servicebog'] = $vehicleData[$field];
                } else {
                    $extraDescription .= $field . ': ' . json_encode($vehicleData[$field]) . "\n";
                }
                unset($vehicleData[$field]);
            }
        }
        if ($extraDescription !== '') {
            $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '') . "\n\n" . $extraDescription);
        }

        $images = $vehicleData['images'] ?? null;
        unset($vehicleData['images']);

        $fillable = (new Vehicle)->getFillable();
        $vehicleData = array_intersect_key($vehicleData, array_flip($fillable));

        if (empty($vehicleData['dmr_fact_vehicle_id'])) {
            throw new \InvalidArgumentException('dmr_fact_vehicle_id is required');
        }

        $vehicle = Vehicle::create($vehicleData);

        if ($equipmentIds !== null) {
            $vehicle->equipment()->sync($equipmentIds);
        }

        if (is_array($images)) {
            $sortOrder = 0;
            foreach ($images as $file) {
                if (is_string($file)) {
                    $imagePath = str_replace('/storage/', '', parse_url($file, PHP_URL_PATH));

                    $thumbnailPath = null;
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($file, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                    } catch (\Exception $e) {
                    }

                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                } else {
                    $this->fileService->validateFile($file);
                    $uploadedUrl = $this->fileService->uploadFiles(
                        [$file],
                        'public',
                        'vehicles',
                        true,
                        false,
                        300,
                        300
                    )[0];

                    $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH));

                    $thumbnailPath = null;
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                    } catch (\Exception $e) {
                    }

                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        $vehicle = $vehicle->fresh(['images', 'equipment', 'dmrFactVehicle.drivmiddelLines']);
        if ($vehicle) {
            $this->ownershipTaxService->updateCalculatedOwnershipTax($vehicle);
        }

        return $vehicle;
    }

    /**
     * Update a vehicle
     */
    public function updateVehicle(Vehicle $vehicle, array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $vehicleData) {
            $equipmentIds = null;
            if (isset($vehicleData['equipment_ids']) && is_array($vehicleData['equipment_ids'])) {
                $equipmentIds = $vehicleData['equipment_ids'];
                unset($vehicleData['equipment_ids']);
            }

            $detailsFields = [
                // update vehicle details here
            ];
            foreach ($detailsFields as $field) {
                if (isset($vehicleData[$field])) {
                    if ($field === 'description') {
                        $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '') . "\n\n" . (string) $vehicleData[$field]);
                    } elseif ($field === 'condition_id') {
                        $vehicleData['condition_id'] = $vehicleData['condition_id'] ?? $vehicleData[$field];
                    } elseif ($field === 'servicebog') {
                        $vehicleData['servicebog'] = $vehicleData['servicebog'] ?? $vehicleData[$field];
                    }
                    unset($vehicleData[$field]);
                }
            }

            if ($equipmentIds !== null) {
                $vehicle->equipment()->sync($equipmentIds);
            }

            // Handle image updates if provided
            if (isset($vehicleData['images']) && is_array($vehicleData['images'])) {
                // Delete old images and thumbnails
                $oldImages = $vehicle->images;
                foreach ($oldImages as $oldImage) {
                    $this->fileService->deleteFiles([$oldImage->image_path]);
                    if ($oldImage->thumbnail_path) {
                        $this->fileService->deleteFiles([$oldImage->thumbnail_path]);
                    }
                    $oldImage->delete();
                }

                // Upload and create new images
                $sortOrder = 0;
                foreach ($vehicleData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a path/URL - extract relative path and try to generate thumbnail if it doesn't exist
                        // Convert URL like "http://localhost/storage/vehicles/abc.jpg" to "vehicles/abc.jpg"
                        $imagePath = str_replace('/storage/', '', parse_url($file, PHP_URL_PATH));
                        
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($file, 300, 300, 'public');
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Upload file with thumbnail generation
                        $this->fileService->validateFile($file);
                        $uploadedUrl = $this->fileService->uploadFiles(
                            [$file], 
                            'public', 
                            'vehicles',
                            true, // createThumbnails
                            false, // optimizeImages
                            300, // thumbnailWidth
                            300  // thumbnailHeight
                        )[0];
                        
                        // Extract relative path from URL (remove domain and /storage/ prefix)
                        // Convert URL like "http://localhost/storage/vehicles/abc.jpg" to "vehicles/abc.jpg"
                        $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH));
                        
                        // Extract thumbnail path from URL
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
                unset($vehicleData['images']);
            }

            $fillable = (new Vehicle)->getFillable();
            $vehicleData = array_intersect_key($vehicleData, array_flip($fillable));

            if (! empty($vehicleData)) {
                $vehicle->update($vehicleData);
            }

            $updatedVehicle = $vehicle->fresh(['images', 'equipment', 'dmrFactVehicle.drivmiddelLines']);

            if (! $updatedVehicle) {
                $updatedVehicle = Vehicle::with(['images', 'equipment', 'dmrFactVehicle.drivmiddelLines'])->findOrFail($vehicle->id);
            }

            $this->ownershipTaxService->updateCalculatedOwnershipTax($updatedVehicle);

            return $updatedVehicle;
        });
    }

    /**
     * Delete a vehicle
     */
    public function deleteVehicle(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            // Delete vehicle images and thumbnails
            $images = $vehicle->images;
            foreach ($images as $image) {
                $this->fileService->deleteFiles([$image->image_path]);
                if ($image->thumbnail_path) {
                    $this->fileService->deleteFiles([$image->thumbnail_path]);
                }
            }

            // Delete vehicle (soft delete)
            $vehicle->delete();
        });
    }

    /**
     * Get public vehicles with filters (vehicles + DMR + vehicle_equipment only).
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicVehicles(array $filters = [], int $perPage = 15, int $page = 1)
    {
        $query = Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->with(['images' => function ($query) {
                $query->orderBy('sort_order');
            }, 'dmrFactVehicle.variant.model.brand', 'dealer']);

        $this->applyPublicListingFilters($query, $filters);

        $this->applySorting($query, $filters['sort'] ?? 'standard');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply listing filters using only {@see Vehicle}, DMR relations, and {@see Vehicle::equipment()} (vehicle_equipment).
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyPublicListingFilters(Builder $query, array $filters): void
    {
        //update this code to work with vehicle and related tables we have in the database
        
    }

    /**
     * Get dealer vehicles (all statuses) with relations
     */
    public function getDealerVehicles(int $dealerId, array $filters = [], int $perPage = 15, int $page = 1)
    {
        $query = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->with([
                'images' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'equipment',
                'dmrFactVehicle.variant.model.brand',
            ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('registration', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['list_status_id'])) {
            $query->where('list_status_id', $filters['list_status_id']);
        }

        $this->applySorting($query, $filters['sort'] ?? 'standard');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get public dealer vehicles (published only) with filters
     * Similar to getPublicVehicles but filtered by dealer_id
     * 
     * @param int $dealerId
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicDealerVehicles(int $dealerId, array $filters = [], int $perPage = 15, int $page = 1)
    {
        $filters['dealer_id'] = $dealerId;

        return $this->getPublicVehicles($filters, $perPage, $page);
    }

    /**
     * Apply sorting to vehicle query (DMR-backed listings).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applySorting($query, string $sort = 'standard'): void
    {
        $joins = $query->getQuery()->joins ?? [];
        $hasJoins = ! empty($joins);
        $tablePrefix = $hasJoins ? 'vehicles.' : '';

        switch ($sort) {
            // add sorting cases here
            default:
                $query->orderBy($tablePrefix . 'id', 'desc');
                break;
        }
    }

}
