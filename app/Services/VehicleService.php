<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Exceptions\NummerpladeApiException;
use App\Models\DmrFactVehicle;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VehicleService
{
    /**
     * Canonical default when the `sort` query/body param is omitted (newest rows by {@see Vehicle::$created_at}).
     */
    public const DEFAULT_PUBLIC_LISTING_SORT = 'created_at_desc';

    /**
     * Maps legacy / human sort keys to `{column}_{asc|desc}` using only {@see Vehicle} table columns.
     *
     * @var array<string, string>
     */
    private const LEGACY_SORT_ALIASES = [
        'best_match' => self::DEFAULT_PUBLIC_LISTING_SORT,
        'standard' => self::DEFAULT_PUBLIC_LISTING_SORT,
        'date_desc' => self::DEFAULT_PUBLIC_LISTING_SORT,
        'date_asc' => 'created_at_asc',
        'year_desc' => 'model_year_desc',
        'year_asc' => 'model_year_asc',
        'mileage_desc' => 'km_driven_desc',
        'mileage_asc' => 'km_driven_asc',
        'range_desc' => 'range_km_desc',
        'range_asc' => 'range_km_asc',
        'battery_desc' => 'battery_capacity_desc',
        'battery_asc' => 'battery_capacity_asc',
        'brand_asc' => 'brand_id_asc',
        'brand_desc' => 'brand_id_desc',
        'engine_power_desc' => 'engine_power_hp_desc',
        'engine_power_asc' => 'engine_power_hp_asc',
        'top_speed_desc' => 'max_speed_desc',
        'top_speed_asc' => 'max_speed_asc',
        'ownership_tax_desc' => 'calculated_ownership_tax_desc',
        'ownership_tax_asc' => 'calculated_ownership_tax_asc',
        'first_reg_desc' => 'first_registration_date_desc',
        'first_reg_asc' => 'first_registration_date_asc',
        'distance_desc' => self::DEFAULT_PUBLIC_LISTING_SORT,
        'distance_asc' => self::DEFAULT_PUBLIC_LISTING_SORT,
    ];

    /**
     * Columns omitted from the public sort dropdown / `vehicle_sort_keys` (internal IDs, timestamps, blobs, JSON,
     * VIN/title/colour, booleans, long text).
     * Sorting by these via `?sort=` is still allowed when valid on {@see Vehicle}.
     *
     * @var list<string>
     */
    private const SORT_DROPDOWN_EXCLUDED_COLUMNS = [
        'id',
        'updated_at',
        'deleted_at',
        'slug',
        'user_id',
        'dealer_id',
        'dmr_fact_vehicle_id',
        'description',
        'drive_axles',
        'vin',
        'title',
        'colour_id',
        'particle_filter',
        'ncap_test',
        'is_import',
        'is_factory_new',
    ];

    /**
     * Preferred column order for listing sort UI (then remaining columns A–Z).
     * Excludes {@see self::SORT_DROPDOWN_EXCLUDED_COLUMNS}.
     *
     * @var list<string>
     */
    private const PUBLIC_LISTING_SORT_COLUMN_PRIORITY = [
        'created_at',
        'published_at',
        'price',
        'model_year',
        'km_driven',
    ];

    /**
     * `sort` option keys for the listing UI and `/api/v1/constants` → `vehicle_sort_keys`.
     * Subset of vehicles columns (see {@see self::SORT_DROPDOWN_EXCLUDED_COLUMNS}).
     *
     * @return list<string>
     */
    public static function publicListingSortOptionKeys(): array
    {
        $cols = array_values(array_filter(
            Vehicle::listingSortableTableColumns(),
            static fn (string $c): bool => ! in_array($c, self::SORT_DROPDOWN_EXCLUDED_COLUMNS, true)
        ));

        $ordered = [];
        foreach (self::PUBLIC_LISTING_SORT_COLUMN_PRIORITY as $c) {
            if (in_array($c, $cols, true)) {
                $ordered[] = $c;
            }
        }
        foreach ($cols as $c) {
            if (! in_array($c, $ordered, true)) {
                $ordered[] = $c;
            }
        }

        $keys = [];
        foreach ($ordered as $col) {
            $keys[] = $col.'_asc';
            $keys[] = $col.'_desc';
        }

        return $keys;
    }

    /**
     * Canonical `sort` string: `{vehicles_column}_asc` or `{vehicles_column}_desc`.
     * Default matches newest rows first by {@see Vehicle::$created_at} descending.
     */
    public static function normalizePublicListingSort(?string $sort): string
    {
        $s = $sort === null || $sort === '' ? null : trim((string) $sort);
        if ($s === null || $s === '') {
            return self::DEFAULT_PUBLIC_LISTING_SORT;
        }

        $s = self::LEGACY_SORT_ALIASES[$s] ?? $s;

        if (preg_match('/^([a-z0-9_]+)_(asc|desc)$/', $s, $m)) {
            $col = $m[1];
            $dir = $m[2];
            if (in_array($col, Vehicle::listingSortableTableColumns(), true)) {
                return $col.'_'.$dir;
            }
        }

        return self::DEFAULT_PUBLIC_LISTING_SORT;
    }

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
                    $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '')."\n\n".(string) $vehicleData[$field]);
                } elseif ($field === 'condition_id' && ! isset($vehicleData['condition_id'])) {
                    $vehicleData['condition_id'] = $vehicleData[$field];
                } elseif ($field === 'servicebog' && ! isset($vehicleData['servicebog'])) {
                    $vehicleData['servicebog'] = $vehicleData[$field];
                } else {
                    $extraDescription .= $field.': '.json_encode($vehicleData[$field])."\n";
                }
                unset($vehicleData[$field]);
            }
        }
        if ($extraDescription !== '') {
            $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '')."\n\n".$extraDescription);
        }

        $images = $vehicleData['images'] ?? null;
        unset($vehicleData['images']);

        $vehicleData = $this->normalizeIncomingVehiclePayload($vehicleData);
        $vehicleData = $this->deriveEnginePowerHpFromKw($vehicleData);
        $this->hydrateFirstRegistrationYearFromDate($vehicleData, null);

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
            LookupService::forgetLookupCacheGroup('equipments');
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
                        $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '')."\n\n".(string) $vehicleData[$field]);
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

            $vehicleData = $this->normalizeIncomingVehiclePayload($vehicleData);
            $vehicleData = $this->deriveEnginePowerHpFromKw($vehicleData);
            $this->hydrateFirstRegistrationYearFromDate($vehicleData, $vehicle);

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
                    LookupService::forgetLookupCacheGroup('equipments');
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
     * Map legacy / frontend keys to {@see Vehicle} column names before mass assignment.
     *
     * @param  array<string, mixed>  $vehicleData
     * @return array<string, mixed>
     */
    private function normalizeIncomingVehiclePayload(array $vehicleData): array
    {
        if (array_key_exists('vehicle_list_status_id', $vehicleData) && ! array_key_exists('list_status_id', $vehicleData)) {
            $vehicleData['list_status_id'] = $vehicleData['vehicle_list_status_id'];
            unset($vehicleData['vehicle_list_status_id']);
        }

        if (array_key_exists('use_id', $vehicleData) && ! array_key_exists('vehicle_use_id', $vehicleData)) {
            $vehicleData['vehicle_use_id'] = $vehicleData['use_id'];
            unset($vehicleData['use_id']);
        }

        if (array_key_exists('co2_emissions', $vehicleData) && ! array_key_exists('co2_emission', $vehicleData)) {
            $vehicleData['co2_emission'] = $vehicleData['co2_emissions'];
            unset($vehicleData['co2_emissions']);
        }

        if (array_key_exists('engine_power', $vehicleData) && ! array_key_exists('engine_power_kw', $vehicleData)) {
            $vehicleData['engine_power_kw'] = $vehicleData['engine_power'];
            unset($vehicleData['engine_power']);
        }

        if (array_key_exists('fuel_efficiency', $vehicleData)) {
            if (! array_key_exists('km_per_liter', $vehicleData) || $vehicleData['km_per_liter'] === null || $vehicleData['km_per_liter'] === '') {
                $vehicleData['km_per_liter'] = $vehicleData['fuel_efficiency'];
            }
            unset($vehicleData['fuel_efficiency']);
        }

        if (array_key_exists('transmission_id', $vehicleData)) {
            $tid = $vehicleData['transmission_id'];
            $hasGear = array_key_exists('gear_type_id', $vehicleData) && $vehicleData['gear_type_id'] !== null && $vehicleData['gear_type_id'] !== '';
            if (! $hasGear && $tid !== null && $tid !== '') {
                $vehicleData['gear_type_id'] = (int) $tid;
            }
            unset($vehicleData['transmission_id']);
        }

        foreach ([
            'wholesale_price_includes_delivery',
            'leasing_enabled',
            'is_import',
            'is_factory_new',
            'particle_filter',
            'ncap_test',
        ] as $boolKey) {
            if (! array_key_exists($boolKey, $vehicleData)) {
                continue;
            }
            $v = $vehicleData[$boolKey];
            if (is_bool($v)) {
                continue;
            }
            if ($v === null) {
                continue;
            }
            if (is_string($v)) {
                $lower = strtolower(trim($v));
                if (in_array($lower, ['1', 'true', 'yes', 'on'], true)) {
                    $vehicleData[$boolKey] = true;
                } elseif (in_array($lower, ['0', 'false', 'no', 'off', ''], true)) {
                    $vehicleData[$boolKey] = false;
                }
            } elseif (is_numeric($v)) {
                $vehicleData[$boolKey] = (bool) (int) $v;
            }
        }

        return $vehicleData;
    }

    /**
     * When kW is set and HP is omitted, derive HP using the same factor as the dealer panel (kW × 1.36).
     *
     * @param  array<string, mixed>  $vehicleData
     * @return array<string, mixed>
     */
    /**
     * Persist {@see Vehicle::$first_registration_year} when omitted: use year from
     * {@see Vehicle::$first_registration_date} (payload or existing row on update).
     *
     * @param  array<string, mixed>  $vehicleData
     */
    private function hydrateFirstRegistrationYearFromDate(array &$vehicleData, ?Vehicle $existing): void
    {
        $yearKeyPresent = array_key_exists('first_registration_year', $vehicleData);
        $rawYear = $yearKeyPresent ? ($vehicleData['first_registration_year'] ?? null) : null;
        if ($rawYear !== null && $rawYear !== '' && (int) $rawYear > 0) {
            return;
        }

        $dateRaw = null;
        if (array_key_exists('first_registration_date', $vehicleData)) {
            $v = $vehicleData['first_registration_date'];
            if ($v === null || $v === '') {
                return;
            }
            $dateRaw = $v;
        } elseif ($existing?->first_registration_date) {
            $dateRaw = $existing->first_registration_date;
        }

        if ($dateRaw === null || $dateRaw === '') {
            return;
        }

        try {
            $vehicleData['first_registration_year'] = (int) Carbon::parse($dateRaw)->year;
        } catch (\Throwable) {
        }
    }

    private function deriveEnginePowerHpFromKw(array $vehicleData): array
    {
        if (! array_key_exists('engine_power_kw', $vehicleData)) {
            return $vehicleData;
        }

        $kw = $vehicleData['engine_power_kw'];
        if ($kw === null || $kw === '') {
            return $vehicleData;
        }

        $hp = $vehicleData['engine_power_hp'] ?? null;
        if ($hp !== null && $hp !== '') {
            return $vehicleData;
        }

        $k = (float) $kw;
        if ($k <= 0) {
            return $vehicleData;
        }

        $vehicleData['engine_power_hp'] = round($k * 1.36, 3);

        return $vehicleData;
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
            'salesType',
            'fuelType',
            'gearType',
            'brand',
            'model',
            'variant',
        ];
        if ($with !== []) {
            $baseWith = array_merge($baseWith, $with);
        }

        $query = Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->with($baseWith);

        $this->applyPublicListingFilters($query, $filters);

        $this->applySorting($query, $filters['sort'] ?? null);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply listing filters using {@see Vehicle}, DMR relations, {@see Vehicle::equipment()} (vehicle_equipment),
     * and {@see Vehicle::specifications()} for airbags (see {@see self::applyPublicAirbagsFilter()}).
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

        foreach (['category_id', 'condition_id', 'sales_type_id', 'price_type_id', 'type_id', 'model_year_id'] as $col) {
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
            $query->where('km_per_liter', $op, (float) $f[$k]);
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

        $airbagsMin = null;
        if (isset($f['specifications_airbags']) && $f['specifications_airbags'] !== '' && $f['specifications_airbags'] !== null) {
            $airbagsMin = (int) $f['specifications_airbags'];
        } elseif (isset($f['airbags']) && $f['airbags'] !== '' && $f['airbags'] !== null) {
            $airbagsMin = (int) $f['airbags'];
        }
        if ($airbagsMin !== null) {
            $this->applyPublicAirbagsFilter($query, $airbagsMin);
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
     * Minimum airbags: {@see Vehicle::specifications()} pivot {@code count} for specs whose name matches "airbag",
     * optionally OR {@code vehicles.airbags} when that column exists (legacy / denormalized).
     *
     * @param  Builder<\App\Models\Vehicle>  $query
     */
    private function applyPublicAirbagsFilter(Builder $query, int $min): void
    {
        $hasPivot = Schema::hasTable('vehicle_specifications') && Schema::hasTable('specifications');
        $hasColumn = Schema::hasColumn('vehicles', 'airbags');

        if ($hasPivot && $hasColumn) {
            $query->where(function (Builder $outer) use ($min): void {
                $outer->whereHas('specifications', function (Builder $q) use ($min): void {
                    $q->whereRaw('LOWER(specifications.name) LIKE ?', ['%airbag%'])
                        ->where('vehicle_specifications.count', '>=', $min);
                })->orWhere('airbags', '>=', $min);
            });

            return;
        }

        if ($hasPivot) {
            $query->whereHas('specifications', function (Builder $q) use ($min): void {
                $q->whereRaw('LOWER(specifications.name) LIKE ?', ['%airbag%'])
                    ->where('vehicle_specifications.count', '>=', $min);
            });

            return;
        }

        if ($hasColumn) {
            $query->where('airbags', '>=', $min);
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

        if (isset($f['transmission_id']) && $f['transmission_id'] !== '' && $f['transmission_id'] !== null) {
            if (! isset($f['gear_type_id']) || $f['gear_type_id'] === '' || $f['gear_type_id'] === null) {
                $f['gear_type_id'] = $f['transmission_id'];
            }
            unset($f['transmission_id']);
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
                'vehicleListStatus' => static function ($q): void {
                    $q->select('id', 'name');
                },
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

        $this->applySorting($query, $filters['sort'] ?? null);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get public dealer vehicles (published only) with filters
     * Similar to getPublicVehicles but filtered by dealer_id
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicDealerVehicles(int $dealerId, array $filters = [], int $perPage = 15, int $page = 1)
    {
        $filters['dealer_id'] = $dealerId;

        return $this->getPublicVehicles($filters, $perPage, $page);
    }

    /**
     * Apply sorting to vehicle query using only {@see Vehicle} table columns.
     * Clears prior ORDER BY (including the defaultOrder global scope) so user sort wins.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Vehicle>  $query
     */
    protected function applySorting(Builder $query, ?string $sort = null): void
    {
        $sort = self::normalizePublicListingSort($sort);

        $query->reorder();

        if (! preg_match('/^([a-z0-9_]+)_(asc|desc)$/', $sort, $m)) {
            $query->orderByDesc($query->getModel()->getTable().'.id');

            return;
        }

        $column = $m[1];
        $direction = $m[2] === 'asc' ? 'asc' : 'desc';

        if (! in_array($column, Vehicle::listingSortableTableColumns(), true)) {
            $query->orderByDesc($query->getModel()->getTable().'.id');

            return;
        }

        $table = $query->getModel()->getTable();
        $qualified = $table.'.'.$column;

        $query->orderBy($qualified, $direction);
        if ($column !== 'id') {
            $query->orderByDesc($table.'.id');
        }
    }
}
