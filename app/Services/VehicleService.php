<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\DmrBodyType;
use App\Models\DmrBrand;
use App\Models\DmrColour;
use App\Models\DmrEmissionNorm;
use App\Models\DmrFactVehicle;
use App\Models\DmrModel;
use App\Models\DmrVehicleUse;
use App\Constants\VehicleListStatus;
use App\Exceptions\NummerpladeApiException;
use App\Services\DmrLookupAssociationService;
use App\Services\DmrFactVehicleLookupService;
use App\Services\FileService;
use App\Services\OwnershipTaxService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    public function __construct(
        private FileService $fileService,
        private OwnershipTaxService $ownershipTaxService,
        private DmrLookupAssociationService $dmrLookupAssociationService,
        private DmrFactVehicleLookupService $dmrFactVehicleLookupService,
    ) {}

    /**
     * Preview payload for dealer vehicle create (local DMR; legacy name kept for API route).
     */
    public function fetchVehicleDataFromNummerplade(?string $registration, ?string $vin): array
    {
        if ($registration !== null && trim($registration) !== '') {
            return $this->dmrFactVehicleLookupService->lookupByRegistration($registration);
        }
        if ($vin !== null && trim($vin) !== '') {
            return $this->dmrFactVehicleLookupService->lookupByVin($vin);
        }

        throw NummerpladeApiException::invalidInput('Registration or VIN is required');
    }

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

        $lookupCsv = null;
        if (array_key_exists('lookup_equipments', $vehicleData)) {
            $raw = $vehicleData['lookup_equipments'];
            unset($vehicleData['lookup_equipments']);
            $lookupCsv = is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
        }

        $lookupSpecsJson = null;
        if (array_key_exists('lookup_specifications', $vehicleData)) {
            $raw = $vehicleData['lookup_specifications'];
            unset($vehicleData['lookup_specifications']);
            $lookupSpecsJson = is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
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

        $this->hydrateVariantIdFromDmrFact($vehicleData);
        $this->hydrateFuelTypeIdFromDmrFact($vehicleData);

        $fillable = (new Vehicle)->getFillable();
        $vehicleData = array_intersect_key($vehicleData, array_flip($fillable));

        $dmrId = $vehicleData['dmr_fact_vehicle_id'] ?? null;
        if ($dmrId === '' || $dmrId === null) {
            $vehicleData['dmr_fact_vehicle_id'] = null;
            if (empty($vehicleData['brand_id']) || empty($vehicleData['model_id']) || empty($vehicleData['fuel_type_id'])) {
                throw new \InvalidArgumentException('dmr_fact_vehicle_id is required unless brand_id, model_id, and fuel_type_id are provided');
            }
        } else {
            $vehicleData['dmr_fact_vehicle_id'] = (int) $dmrId;
        }

        $vehicle = Vehicle::create($vehicleData);

        $checkboxIds = [];
        if ($equipmentIds !== null) {
            $checkboxIds = array_values(array_filter(array_map('intval', $equipmentIds)));
        }
        $lookupEquipIds = $this->dmrLookupAssociationService->resolveEquipmentIdsFromLookupString($lookupCsv);
        if ($lookupCsv !== null && $lookupCsv !== '') {
            Cache::forget('constants_equipments');
        }
        $allEquipmentIds = array_values(array_unique(array_merge($checkboxIds, $lookupEquipIds)));
        if ($allEquipmentIds !== []) {
            $vehicle->equipment()->sync($allEquipmentIds);
        }

        $specSync = $this->dmrLookupAssociationService->resolveSpecificationSyncFromLookupJson($lookupSpecsJson);
        if ($specSync !== []) {
            $vehicle->specifications()->sync($specSync);
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

        $vehicle = $vehicle->fresh(['images', 'equipment', 'specifications', 'dmrFactVehicle.drivmiddelLines']);
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
            $hasEquipmentPayload = array_key_exists('equipment_ids', $vehicleData)
                || array_key_exists('lookup_equipments', $vehicleData);
            $hasSpecPayload = array_key_exists('lookup_specifications', $vehicleData);

            $equipmentIds = null;
            if (array_key_exists('equipment_ids', $vehicleData)) {
                $equipmentIds = is_array($vehicleData['equipment_ids']) ? $vehicleData['equipment_ids'] : null;
                unset($vehicleData['equipment_ids']);
            }

            $lookupCsv = null;
            if (array_key_exists('lookup_equipments', $vehicleData)) {
                $raw = $vehicleData['lookup_equipments'];
                unset($vehicleData['lookup_equipments']);
                $lookupCsv = is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
            }

            $lookupSpecsJson = null;
            if (array_key_exists('lookup_specifications', $vehicleData)) {
                $raw = $vehicleData['lookup_specifications'];
                unset($vehicleData['lookup_specifications']);
                $lookupSpecsJson = is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
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

            $this->hydrateVariantIdFromDmrFact($vehicleData, $vehicle->dmr_fact_vehicle_id);
            $this->hydrateFuelTypeIdFromDmrFact($vehicleData, $vehicle->dmr_fact_vehicle_id);

            $fillable = (new Vehicle)->getFillable();
            $vehicleData = array_intersect_key($vehicleData, array_flip($fillable));

            if (! empty($vehicleData)) {
                $vehicle->update($vehicleData);
            }

            if ($hasEquipmentPayload) {
                $checkboxIds = [];
                if ($equipmentIds !== null) {
                    $checkboxIds = array_values(array_filter(array_map('intval', $equipmentIds)));
                }
                $lookupEquipIds = $this->dmrLookupAssociationService->resolveEquipmentIdsFromLookupString($lookupCsv);
                if ($lookupCsv !== null && $lookupCsv !== '') {
                    Cache::forget('constants_equipments');
                }
                $allEquipmentIds = array_values(array_unique(array_merge($checkboxIds, $lookupEquipIds)));
                $vehicle->equipment()->sync($allEquipmentIds);
            }

            if ($hasSpecPayload) {
                $specSync = $this->dmrLookupAssociationService->resolveSpecificationSyncFromLookupJson($lookupSpecsJson);
                $vehicle->specifications()->sync($specSync);
            }

            $updatedVehicle = $vehicle->fresh(['images', 'equipment', 'specifications', 'dmrFactVehicle.drivmiddelLines']);

            if (! $updatedVehicle) {
                $updatedVehicle = Vehicle::with(['images', 'equipment', 'specifications', 'dmrFactVehicle.drivmiddelLines'])->findOrFail($vehicle->id);
            }

            $this->ownershipTaxService->updateCalculatedOwnershipTax($updatedVehicle);

            return $updatedVehicle;
        });
    }

    /**
     * When `variant_id` is absent or empty, copy from linked `dmr_fact_vehicles.variant_id`.
     */
    private function hydrateVariantIdFromDmrFact(array &$vehicleData, ?int $fallbackDmrFactId = null): void
    {
        $current = $vehicleData['variant_id'] ?? null;
        if ($current !== null && $current !== '') {
            return;
        }

        $dmrId = $vehicleData['dmr_fact_vehicle_id'] ?? $fallbackDmrFactId;
        if (! $dmrId) {
            return;
        }

        $variantId = DmrFactVehicle::query()->whereKey($dmrId)->value('variant_id');
        if ($variantId !== null) {
            $vehicleData['variant_id'] = $variantId;
        }
    }

    /**
     * When `fuel_type_id` is absent or empty, use primary drivmiddel line’s `drive_energy_id` (DMR drive energy).
     */
    private function hydrateFuelTypeIdFromDmrFact(array &$vehicleData, ?int $fallbackDmrFactId = null): void
    {
        $current = $vehicleData['fuel_type_id'] ?? null;
        if ($current !== null && $current !== '') {
            return;
        }

        $dmrId = $vehicleData['dmr_fact_vehicle_id'] ?? $fallbackDmrFactId;
        if (! $dmrId) {
            return;
        }

        $energyId = DB::table('dmr_bridge_vehicle_drivmiddel')
            ->where('vehicle_id', $dmrId)
            ->whereNotNull('drive_energy_id')
            ->orderByDesc('drivmiddel_primaer')
            ->orderBy('line_order')
            ->orderBy('id')
            ->value('drive_energy_id');

        if ($energyId !== null) {
            $vehicleData['fuel_type_id'] = (int) $energyId;
        }
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
        return $this->getPublicVehiclesWithAdvancedFilters([], $filters, $perPage, $page);
    }

    /**
     * Public listing search (web + API). Same filters as {@see getPublicVehicles()}.
     *
     * @param  array<int, string>  $with  Extra eager-load relation paths
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicVehiclesWithAdvancedFilters(array $with = [], array $filters = [], int $perPage = 15, int $page = 1)
    {
        $baseWith = [
            'images' => function ($query) {
                $query->orderBy('sort_order');
            },
            'dmrFactVehicle.variant.model.brand',
            'dealer',
        ];
        if ($with !== []) {
            $baseWith = array_merge($baseWith, $with);
        }

        $query = Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->with($baseWith);

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
        $f = $this->normalizePublicListingFilters($filters);

        if (! empty($f['search'])) {
            $term = trim((string) $f['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('registration', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        $this->whereInIds($query, 'brand_id', $f['brand_id'] ?? null);
        $this->whereInIds($query, 'model_id', $f['model_id'] ?? null);
        $this->whereInIds($query, 'listing_type_id', $f['listing_type_id'] ?? null);
        if (! empty($f['fuel_type_id'])) {
            $fuelIds = is_array($f['fuel_type_id']) ? array_map('intval', $f['fuel_type_id']) : [(int) $f['fuel_type_id']];
            $fuelIds = array_values(array_filter($fuelIds, fn ($v) => $v > 0));
            if ($fuelIds !== []) {
                $query->where(function (Builder $outer) use ($fuelIds) {
                    $outer->whereIn('fuel_type_id', $fuelIds)
                        ->orWhereHas('dmrFactVehicle.drivmiddelLines', function (Builder $q) use ($fuelIds) {
                            $q->whereIn('drive_energy_id', $fuelIds);
                        });
                });
            }
        }
        $this->whereInIds($query, 'body_type_id', $f['body_type_id'] ?? null);
        $this->whereInIds($query, 'gear_type_id', $f['gear_type_id'] ?? null);

        if (! empty($f['variant_id'])) {
            $ids = array_map('intval', (array) $f['variant_id']);
            $ids = array_values(array_filter($ids));
            if ($ids !== []) {
                $query->where(function (Builder $outer) use ($ids) {
                    $outer->whereIn('variant_id', $ids)
                        ->orWhereHas('dmrFactVehicle', function (Builder $q) use ($ids) {
                            $q->whereIn('variant_id', $ids);
                        });
                });
            }
        }

        foreach (['category_id', 'condition_id', 'sales_type_id', 'price_type_id', 'type_id', 'transmission_id', 'model_year_id'] as $col) {
            if (! isset($f[$col]) || $f[$col] === '' || $f[$col] === null) {
                continue;
            }
            $query->where($col, (int) $f[$col]);
        }

        if (isset($f['use_id']) && $f['use_id'] !== '' && $f['use_id'] !== null) {
            $query->where('vehicle_use_id', (int) $f['use_id']);
        } elseif (isset($f['vehicle_use_id']) && $f['vehicle_use_id'] !== '' && $f['vehicle_use_id'] !== null) {
            $query->where('vehicle_use_id', (int) $f['vehicle_use_id']);
        }

        foreach (['colour_id' => 'colour_id', 'color_id' => 'colour_id', 'emission_norm_id' => 'emission_norm_id'] as $inKey => $column) {
            if (! array_key_exists($inKey, $f)) {
                continue;
            }
            $val = $f[$inKey];
            if ($val === null || $val === '') {
                continue;
            }
            if (is_array($val)) {
                $query->whereIn($column, array_map('intval', $val));
            } else {
                $query->where($column, (int) $val);
            }
        }

        if (isset($f['price_from'])) {
            $query->where('price', '>=', (int) $f['price_from']);
        }
        if (isset($f['price_to'])) {
            $query->where('price', '<=', (int) $f['price_to']);
        }

        $mileageFrom = $f['mileage_from'] ?? $f['km_driven_from'] ?? null;
        $mileageTo = $f['mileage_to'] ?? $f['km_driven_to'] ?? null;
        if ($mileageFrom !== null && $mileageFrom !== '') {
            $query->where('km_driven', '>=', (int) $mileageFrom);
        }
        if ($mileageTo !== null && $mileageTo !== '') {
            $query->where('km_driven', '<=', (int) $mileageTo);
        }

        $myFrom = $f['model_year_from'] ?? $f['year_from'] ?? null;
        $myTo = $f['model_year_to'] ?? $f['year_to'] ?? null;
        if ($myFrom !== null && $myFrom !== '') {
            $query->where('model_year', '>=', (int) $myFrom);
        }
        if ($myTo !== null && $myTo !== '') {
            $query->where('model_year', '<=', (int) $myTo);
        }

        if (isset($f['first_registration_year_from'])) {
            $query->where('first_registration_year', '>=', (int) $f['first_registration_year_from']);
        }
        if (isset($f['first_registration_year_to'])) {
            $query->where('first_registration_year', '<=', (int) $f['first_registration_year_to']);
        }

        if (isset($f['ownership_tax_from'])) {
            $query->where('calculated_ownership_tax', '>=', (int) $f['ownership_tax_from']);
        }
        if (isset($f['ownership_tax_to'])) {
            $query->where('calculated_ownership_tax', '<=', (int) $f['ownership_tax_to']);
        }

        foreach (['engine_power_kw_from' => '>=', 'engine_power_kw_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('engine_power_kw', $op, (float) $f[$k]);
        }
        foreach (['engine_power_from' => '>=', 'engine_power_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('engine_power_hp', $op, (float) $f[$k]);
        }

        foreach (['electrical_consumption_from' => '>=', 'electrical_consumption_to' => '<=', 'battery_capacity_from' => '>=', 'battery_capacity_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('electrical_consumption', $op, (float) $f[$k]);
        }

        foreach (['km_per_liter_from' => '>=', 'km_per_liter_to' => '<=', 'fuel_efficiency_from' => '>=', 'fuel_efficiency_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $col = str_contains($k, 'fuel_efficiency') ? 'fuel_efficiency' : 'km_per_liter';
            $query->where($col, $op, (float) $f[$k]);
        }

        foreach (['range_km_from' => '>=', 'range_km_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('range_km', $op, (int) $f[$k]);
        }

        foreach (['max_speed_from' => '>=', 'max_speed_to' => '<=', 'top_speed_from' => '>=', 'top_speed_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('max_speed', $op, (int) $f[$k]);
        }

        foreach (['maximum_weight_kg_from' => '>=', 'maximum_weight_kg_to' => '<=', 'weight_from' => '>=', 'weight_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('maximum_weight_kg', $op, (int) $f[$k]);
        }

        foreach (['door_count', 'doors', 'seats_min', 'seats_max', 'axle_count', 'axles', 'wheels', 'engine_cylinders', 'towing_weight'] as $field) {
            if (! isset($f[$field])) {
                continue;
            }
            $column = match ($field) {
                'doors' => 'door_count',
                'axles' => 'axle_count',
                default => $field,
            };
            $query->where($column, '>=', (int) $f[$field]);
        }

        if (isset($f['specifications_airbags'])) {
            $query->where('airbags', '>=', (int) $f['specifications_airbags']);
        } elseif (isset($f['airbags'])) {
            $query->where('airbags', '>=', (int) $f['airbags']);
        }

        foreach (['engine_displacement_from' => '>=', 'engine_displacement_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('engine_displacement_litres', $op, (float) $f[$k]);
        }

        if (! empty($f['charging_type'])) {
            $query->where('charging_type', $f['charging_type']);
        }

        if (isset($f['euronom_id']) && ! isset($f['emission_norm_id'])) {
            $query->where('emission_norm_id', (int) $f['euronom_id']);
        }

        if (isset($f['ncap_test']) || isset($f['ncap_five'])) {
            $query->where('ncap_test', true);
        }

        if (isset($f['is_import'])) {
            $query->where('is_import', (bool) $f['is_import']);
        }
        if (isset($f['is_factory_new'])) {
            $query->where('is_factory_new', (bool) $f['is_factory_new']);
        }

        $driveTokens = $f['drive_axle_count'] ?? $f['drive_axles'] ?? null;
        if (is_array($driveTokens) && $driveTokens !== []) {
            $query->where(function (Builder $q) use ($driveTokens) {
                foreach ($driveTokens as $tok) {
                    $q->orWhereJsonContains('drive_axles', (int) $tok)
                        ->orWhereJsonContains('drive_axles', (string) $tok);
                }
            });
        }

        $equipIds = $f['equipment_ids'] ?? null;
        if ($equipIds === null && isset($f['equipment_id'])) {
            $equipIds = [$f['equipment_id']];
        }
        if (is_array($equipIds) && $equipIds !== []) {
            $ids = array_map('intval', $equipIds);
            $query->whereHas('equipment', function (Builder $q) use ($ids) {
                $q->whereIn('equipments.id', $ids);
            });
        }

        if (isset($f['dealer_id'])) {
            $query->where('dealer_id', (int) $f['dealer_id']);
        }

        if (isset($f['seller_type'])) {
            $st = strtolower((string) $f['seller_type']);
            if ($st === 'private' || $st === '0') {
                $query->where(function (Builder $q) {
                    $q->whereNull('dealer_id')
                        ->orWhereHas('dealer', function (Builder $dq) {
                            $dq->where('cvr', 'like', 'INDIVIDUAL-%');
                        });
                });
            } elseif ($st === 'dealer' || $st === '1') {
                $query->whereHas('dealer', function (Builder $dq) {
                    $dq->where('cvr', 'not like', 'INDIVIDUAL-%');
                });
            }
        }
    }

    /**
     * @param  array<int, int|string>|int|string|null  $ids
     */
    private function whereInIds(Builder $query, string $column, $ids): void
    {
        if ($ids === null || $ids === '' || $ids === []) {
            return;
        }
        $list = is_array($ids) ? array_map('intval', $ids) : [(int) $ids];
        $list = array_values(array_filter($list, fn ($v) => $v > 0));
        if ($list === []) {
            return;
        }
        $query->whereIn($column, $list);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizePublicListingFilters(array $filters): array
    {
        $f = $filters;

        if (isset($f['variant_id'])) {
            $v = $f['variant_id'];
            if (is_string($v)) {
                $f['variant_id'] = array_values(array_filter(array_map('intval', explode(',', $v))));
            } elseif (! is_array($v)) {
                $f['variant_id'] = [(int) $v];
            }
        }

        foreach (['brand_id', 'model_id', 'listing_type_id', 'fuel_type_id', 'body_type_id', 'gear_type_id'] as $k) {
            if (! isset($f[$k])) {
                continue;
            }
            if (is_string($f[$k]) && str_contains($f[$k], ',')) {
                $f[$k] = array_values(array_filter(array_map('intval', explode(',', $f[$k]))));
            }
        }

        if (isset($f['color_id']) && ! isset($f['colour_id'])) {
            $f['colour_id'] = $f['color_id'];
        }

        if (isset($f['mileage_from']) || isset($f['mileage_to'])) {
            // already set
        } elseif (isset($f['km_driven_from']) || isset($f['km_driven_to'])) {
            $f['mileage_from'] = $f['km_driven_from'] ?? null;
            $f['mileage_to'] = $f['km_driven_to'] ?? null;
        }

        return $f;
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
        $sort = $sort === '' ? 'standard' : $sort;

        switch ($sort) {
            case 'best_match':
            case 'standard':
                $query->orderByDesc('id');
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc')->orderByDesc('id');
                break;
            case 'price_desc':
                $query->orderByDesc('price')->orderByDesc('id');
                break;
            case 'date_desc':
                $query->orderByDesc('published_at')->orderByDesc('id');
                break;
            case 'date_asc':
                $query->orderBy('published_at', 'asc')->orderBy('id', 'asc');
                break;
            case 'year_desc':
                $query->orderByDesc('model_year')->orderByDesc('id');
                break;
            case 'year_asc':
                $query->orderBy('model_year', 'asc')->orderBy('id', 'asc');
                break;
            case 'mileage_desc':
                $query->orderByDesc('km_driven')->orderByDesc('id');
                break;
            case 'mileage_asc':
                $query->orderBy('km_driven', 'asc')->orderBy('id', 'asc');
                break;
            case 'fuel_efficiency_desc':
                $query->orderByDesc('km_per_liter')->orderByDesc('id');
                break;
            case 'fuel_efficiency_asc':
                $query->orderBy('km_per_liter', 'asc')->orderBy('id', 'asc');
                break;
            case 'range_desc':
                $query->orderByDesc('range_km')->orderByDesc('id');
                break;
            case 'range_asc':
                $query->orderBy('range_km', 'asc')->orderBy('id', 'asc');
                break;
            case 'battery_desc':
                $query->orderByDesc('electrical_consumption')->orderByDesc('id');
                break;
            case 'battery_asc':
                $query->orderBy('electrical_consumption', 'asc')->orderBy('id', 'asc');
                break;
            case 'brand_asc':
                $query->leftJoin('dmr_brands as sort_brand', 'sort_brand.id', '=', 'vehicles.brand_id')
                    ->select('vehicles.*')
                    ->orderBy('sort_brand.name', 'asc')
                    ->orderByDesc('vehicles.id');
                break;
            case 'brand_desc':
                $query->leftJoin('dmr_brands as sort_brand', 'sort_brand.id', '=', 'vehicles.brand_id')
                    ->select('vehicles.*')
                    ->orderByDesc('sort_brand.name')
                    ->orderByDesc('vehicles.id');
                break;
            case 'engine_power_desc':
                $query->orderByDesc('engine_power_hp')->orderByDesc('id');
                break;
            case 'engine_power_asc':
                $query->orderBy('engine_power_hp', 'asc')->orderBy('id', 'asc');
                break;
            case 'towing_weight_desc':
                $query->orderByDesc('towing_weight')->orderByDesc('id');
                break;
            case 'towing_weight_asc':
                $query->orderBy('towing_weight', 'asc')->orderBy('id', 'asc');
                break;
            case 'top_speed_desc':
                $query->orderByDesc('max_speed')->orderByDesc('id');
                break;
            case 'top_speed_asc':
                $query->orderBy('max_speed', 'asc')->orderBy('id', 'asc');
                break;
            case 'ownership_tax_desc':
                $query->orderByDesc('calculated_ownership_tax')->orderByDesc('id');
                break;
            case 'ownership_tax_asc':
                $query->orderBy('calculated_ownership_tax', 'asc')->orderBy('id', 'asc');
                break;
            case 'first_reg_desc':
                $query->orderByDesc('first_registration_date')->orderByDesc('id');
                break;
            case 'first_reg_asc':
                $query->orderBy('first_registration_date', 'asc')->orderBy('id', 'asc');
                break;
            case 'distance_asc':
            case 'distance_desc':
                $query->orderByDesc('id');
                break;
            default:
                $query->orderByDesc('id');
                break;
        }
    }

}
