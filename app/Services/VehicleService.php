<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Exceptions\NummerpladeApiException;
use App\Models\DmrFactVehicle;
use App\Models\ListingType;
use App\Models\SalesType;
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
     * Curated public listing sort options for the vehicles page: option value => Danish label.
     * "standard" maps via {@see self::LEGACY_SORT_ALIASES} / default normalization to {@see self::DEFAULT_PUBLIC_LISTING_SORT}.
     * "distance_*" sorts by Haversine km when {@see self::applySellerDistanceSort()} receives viewer coordinates.
     *
     * @return array<string, string>
     */
    public static function curatedPublicListingSortOptions(): array
    {
        return [
            'standard' => 'Standard',
            'price_asc' => 'Pris: (laveste først)',
            'price_desc' => 'Pris: (Højeste først)',
            'created_at_desc' => 'Dato: (Nyeste først)',
            'created_at_asc' => 'Dato: (Ældste først)',
            'model_year_desc' => 'Modelår: (Nyeste først)',
            'model_year_asc' => 'Modelår: (Ældste først)',
            'km_driven_desc' => 'Kilometerstand: (Højeste først)',
            'km_driven_asc' => 'Kilometerstand: (Laveste først)',
            'km_per_liter_desc' => 'Km/l: (Højeste først)',
            'km_per_liter_asc' => 'Km/l: (Laveste først)',
            'calculated_ownership_tax_desc' => 'Ejerafgift: (Højeste først)',
            'calculated_ownership_tax_asc' => 'Ejerafgift: (Laveste først)',
            'first_registration_date_desc' => '1. reg: (Nyeste først)',
            'first_registration_date_asc' => '1. reg: (Ældste først)',
            'distance_asc' => 'Afstand til sælger: (Korteste afstand)',
            'distance_desc' => 'Afstand til sælger: (Længste afstand)',
        ];
    }

    /**
     * Sort keys for API/constants (same set as the curated dropdown).
     *
     * @return list<string>
     */
    public static function curatedPublicListingSortKeys(): array
    {
        return array_keys(self::curatedPublicListingSortOptions());
    }

    /**
     * Whether the sort dropdown option should appear selected (uses raw ?sort= query so "Standard" vs "Dato: Nyeste" stay distinct).
     */
    public static function listingSortOptionIsSelected(string $optionValue, ?string $rawSortQuery): bool
    {
        $raw = ($rawSortQuery !== null && $rawSortQuery !== '') ? trim((string) $rawSortQuery) : null;

        if ($optionValue === 'standard') {
            return $raw === null || $raw === '' || strcasecmp($raw, 'standard') === 0;
        }

        if ($raw === null || $raw === '') {
            return false;
        }

        return self::normalizePublicListingSort($raw) === $optionValue;
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

        if ($s === 'distance_asc' || $s === 'distance_desc') {
            return $s;
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
        private VehicleImageUploadService $vehicleImageUploadService,
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
        $this->hydrateModelYearFromFirstRegistration($vehicleData, null);

        $this->hydrateVariantIdFromDmrFact($vehicleData);
        $this->hydrateFuelTypeIdFromDmrFact($vehicleData);

        $this->applySalesTypeLeasingRules($vehicleData);

        $fillable = (new Vehicle)->getFillable();
        $vehicleData = array_intersect_key($vehicleData, array_flip($fillable));

        $vehicleData['listing_type_id'] = ListingType::idOrDefaultPurchase($vehicleData['listing_type_id'] ?? null);

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
            $this->vehicleImageUploadService->attachVehicleImages($vehicle, $images);
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
                $this->vehicleImageUploadService->attachVehicleImages($vehicle, $vehicleData['images']);
                unset($vehicleData['images']);
            }

            $vehicleData = $this->normalizeIncomingVehiclePayload($vehicleData);
            $vehicleData = $this->deriveEnginePowerHpFromKw($vehicleData);
            $this->hydrateFirstRegistrationYearFromDate($vehicleData, $vehicle);
            $this->hydrateModelYearFromFirstRegistration($vehicleData, $vehicle);

            $this->hydrateVariantIdFromDmrFact($vehicleData, $vehicle->dmr_fact_vehicle_id);
            $this->hydrateFuelTypeIdFromDmrFact($vehicleData, $vehicle->dmr_fact_vehicle_id);

            $this->applySalesTypeLeasingRules($vehicleData);

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
     * Leasing columns apply only when sales type is "Leasingdetaljer"; otherwise clear them and disable the flag.
     *
     * @param  array<string, mixed>  $vehicleData
     */
    private function applySalesTypeLeasingRules(array &$vehicleData): void
    {
        if (! array_key_exists('sales_type_id', $vehicleData)) {
            return;
        }

        $raw = $vehicleData['sales_type_id'];
        if ($raw === null || $raw === '') {
            $this->clearLeasingVehicleAttributes($vehicleData);

            return;
        }

        $name = SalesType::query()->whereKey((int) $raw)->value('name');
        $isLeasingDetails = is_string($name) && trim($name) === 'Leasingdetaljer';

        if ($isLeasingDetails) {
            $vehicleData['leasing_enabled'] = true;

            return;
        }

        $this->clearLeasingVehicleAttributes($vehicleData);
    }

    /**
     * @param  array<string, mixed>  $vehicleData
     */
    private function clearLeasingVehicleAttributes(array &$vehicleData): void
    {
        $vehicleData['leasing_enabled'] = false;
        foreach ([
            'leasing_type',
            'leasing_customer_type',
            'leasing_first_payment',
            'leasing_residual_value',
            'leasing_duration',
            'leasing_annual_mileage',
            'leasing_total_cost',
        ] as $key) {
            $vehicleData[$key] = null;
        }
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

    /**
     * When {@see Vehicle::$model_year} is missing or empty in the payload, use
     * {@see Vehicle::$first_registration_year} or the year from {@see Vehicle::$first_registration_date}
     * (from payload or existing row on update). Does not replace a valid incoming or existing model year.
     *
     * @param  array<string, mixed>  $vehicleData
     */
    private function hydrateModelYearFromFirstRegistration(array &$vehicleData, ?Vehicle $existing): void
    {
        $incomingPresent = array_key_exists('model_year', $vehicleData);
        $incomingRaw = $incomingPresent ? ($vehicleData['model_year'] ?? null) : null;
        $incomingValid = $incomingRaw !== null && $incomingRaw !== '' && (int) $incomingRaw > 0;

        if ($incomingValid) {
            $vehicleData['model_year'] = (int) $incomingRaw;

            return;
        }

        $existingMy = $existing?->model_year;
        $existingValid = $existingMy !== null && (int) $existingMy > 0;

        if (! $incomingPresent && $existingValid) {
            return;
        }

        if ($incomingPresent && ! $incomingValid && $existingValid) {
            unset($vehicleData['model_year']);

            return;
        }

        $regYear = null;
        if (array_key_exists('first_registration_year', $vehicleData)) {
            $y = $vehicleData['first_registration_year'];
            if ($y !== null && $y !== '' && (int) $y > 0) {
                $regYear = (int) $y;
            }
        }
        if ($regYear === null && $existing?->first_registration_year) {
            $ry = (int) $existing->first_registration_year;
            if ($ry > 0) {
                $regYear = $ry;
            }
        }
        if ($regYear === null) {
            $dateRaw = null;
            if (array_key_exists('first_registration_date', $vehicleData)) {
                $v = $vehicleData['first_registration_date'];
                if ($v !== null && $v !== '') {
                    $dateRaw = $v;
                }
            } elseif ($existing?->first_registration_date) {
                $dateRaw = $existing->first_registration_date;
            }
            if ($dateRaw !== null && $dateRaw !== '') {
                try {
                    $regYear = (int) Carbon::parse($dateRaw)->year;
                } catch (\Throwable) {
                }
            }
        }

        if ($regYear !== null && $regYear > 0) {
            $vehicleData['model_year'] = $regYear;
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

        $this->applySorting($query, $filters['sort'] ?? null, $filters);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Count published vehicles matching public listing filters (no row hydration).
     *
     * @param  array<string, mixed>  $filters
     */
    public function countPublicVehiclesWithFilters(array $filters = []): int
    {
        $query = Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED);

        $this->applyPublicListingFilters($query, $filters);

        return (int) $query->count();
    }

    /**
     * Apply listing filters using {@see Vehicle}, DMR relations, {@see Vehicle::equipment()} (vehicle_equipment),
     * and {@see Vehicle::specifications()} for airbags (see {@see self::applyPublicAirbagsFilter()}).
     *
     * @param  array<string, mixed>  $filters
     */
    protected function applyPublicListingFilters(Builder $query, array $filters): void
    {
        $table = $query->getModel()->getTable();
        $f = $this->normalizePublicListingFilters($filters);

        if (! empty($f['search'])) {
            $term = trim((string) $f['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%')
                    ->orWhere('registration', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhereHas('brand', function (Builder $b) use ($term): void {
                        $b->where('name', 'like', '%'.$term.'%');
                    })
                    ->orWhereHas('model', function (Builder $m) use ($term): void {
                        $m->where('name', 'like', '%'.$term.'%');
                    });
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

        foreach (['category_id', 'condition_id', 'sales_type_id', 'price_type_id', 'type_id', 'list_status_id', 'measurement_norm_id'] as $col) {
            if (! isset($f[$col]) || $f[$col] === '' || $f[$col] === null) {
                continue;
            }
            $query->where($col, (int) $f[$col]);
        }

        // `model_year_id` is normalized to calendar year range fields in
        // {@see normalizePublicListingFilters()} (vehicles use `model_year`, not `model_year_id`).

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
            $query->where('price', '>=', (float) $f['price_from']);
        }
        if (isset($f['price_to'])) {
            $query->where('price', '<=', (float) $f['price_to']);
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

        foreach (['electrical_consumption_from' => '>=', 'electrical_consumption_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('electrical_consumption', $op, (float) $f[$k]);
        }

        foreach (['battery_capacity_from' => '>=', 'battery_capacity_to' => '<='] as $k => $op) {
            if (! isset($f[$k])) {
                continue;
            }
            $query->where('battery_capacity', $op, (float) $f[$k]);
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
            $query->where("{$table}.dealer_id", (int) $f['dealer_id']);
        }

        if (isset($f['seller_type'])) {
            $st = strtolower((string) $f['seller_type']);
            if ($st === 'private' || $st === '0') {
                $query->where(function (Builder $q) use ($table) {
                    $q->whereNull("{$table}.dealer_id")
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

        foreach (['brand_id', 'model_id', 'listing_type_id', 'fuel_type_id', 'body_type_id', 'gear_type_id', 'model_year_id'] as $k) {
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

        $this->hoistModelYearIdIntoCalendarFilters($f);

        return $f;
    }

    /**
     * Map API / legacy `model_year_id` (calendar year or `model_years.id`) onto
     * `model_year_from` / `model_year_to`, then remove the key. The `vehicles` table uses
     * `model_year` (year int); `model_year_id` is not a column after DMR revamp.
     *
     * @param  array<string, mixed>  $f
     */
    private function hoistModelYearIdIntoCalendarFilters(array &$f): void
    {
        if (! array_key_exists('model_year_id', $f) || $f['model_year_id'] === '' || $f['model_year_id'] === null) {
            return;
        }

        $raw = $f['model_year_id'];
        $candidates = is_array($raw) ? $raw : [$raw];
        $years = [];
        foreach ($candidates as $c) {
            $n = (int) $c;
            if ($n <= 0) {
                continue;
            }
            if ($n >= 1900 && $n <= 2100) {
                $years[] = $n;

                continue;
            }
            if (Schema::hasTable('model_years')) {
                $name = DB::table('model_years')->where('id', $n)->value('name');
                if ($name !== null && is_numeric(trim((string) $name))) {
                    $years[] = (int) trim((string) $name);
                }
            }
        }
        $years = array_values(array_unique($years));
        if ($years !== []) {
            $hasRange = ($f['model_year_from'] ?? $f['year_from'] ?? null) !== null && (string) ($f['model_year_from'] ?? $f['year_from'] ?? '') !== ''
                || ($f['model_year_to'] ?? $f['year_to'] ?? null) !== null && (string) ($f['model_year_to'] ?? $f['year_to'] ?? '') !== '';
            if (! $hasRange) {
                sort($years);
                $f['model_year_from'] = (string) $years[0];
                $f['model_year_to'] = (string) $years[count($years) - 1];
            }
        }

        unset($f['model_year_id']);
    }

    /**
     * Base query for dealer vehicle list (filters + eager loads, no pagination).
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<\App\Models\Vehicle>
     */
    public function buildDealerVehiclesQuery(int $dealerId, array $filters = [], bool $withSorting = true): Builder
    {
        $table = (new Vehicle)->getTable();

        $query = Vehicle::query()
            ->where("{$table}.dealer_id", $dealerId)
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
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('brand', function (Builder $b) use ($search): void {
                        $b->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('model', function (Builder $m) use ($search): void {
                        $m->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if (! empty($filters['list_status_id'])) {
            $query->where("{$table}.list_status_id", $filters['list_status_id']);
        }

        if ($withSorting) {
            $this->applySorting($query, $filters['sort'] ?? null, []);
        }

        return $query;
    }

    /**
     * Count vehicles per list_status_id using the same constraints as the dealer list query.
     *
     * @param  Builder<\App\Models\Vehicle>  $query
     * @return array<int, int>
     */
    public function aggregateListStatusCounts(Builder $query): array
    {
        $table = $query->getModel()->getTable();

        $countQuery = (clone $query)
            ->withoutGlobalScope('defaultOrder')
            ->withoutEagerLoads()
            ->reorder();

        $rows = $countQuery
            ->selectRaw("{$table}.list_status_id, COUNT(*) as aggregate")
            ->groupBy("{$table}.list_status_id")
            ->orderBy("{$table}.list_status_id")
            ->pluck('aggregate', 'list_status_id');

        $out = [];
        foreach (VehicleListStatus::values() as $id) {
            $out[$id] = (int) ($rows->get($id) ?? $rows->get((string) $id) ?? 0);
        }

        return $out;
    }

    /**
     * Get dealer vehicles (all statuses) with relations
     */
    public function getDealerVehicles(int $dealerId, array $filters = [], int $perPage = 15, int $page = 1)
    {
        return $this->buildDealerVehiclesQuery($dealerId, $filters)
            ->paginate($perPage, ['*'], 'page', $page);
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
     * @param  array<string, mixed>  $filters  Used for distance sort (viewer_latitude / viewer_longitude).
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Vehicle>  $query
     */
    protected function applySorting(Builder $query, ?string $sort = null, array $filters = []): void
    {
        $sort = self::normalizePublicListingSort($sort);

        $query->reorder();
        $this->applyBoostPriority($query);

        if ($sort === 'distance_asc' || $sort === 'distance_desc') {
            $this->applySellerDistanceSort($query, $sort, $filters);

            return;
        }

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

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Vehicle>  $query
     */
    private function applyBoostPriority(Builder $query): void
    {
        if (! Schema::hasTable('listing_boosts')) {
            return;
        }

        $table = $query->getModel()->getTable();
        $baseQuery = $query->getQuery();

        // Avoid SELECT * with a join — both tables have `id`, which would null out vehicle ids.
        if ($baseQuery->columns === null || $baseQuery->columns === ['*']) {
            $query->select("{$table}.*");
        }

        $query->leftJoin('listing_boosts as listing_boost_active', function ($join) use ($table) {
            $join->on('listing_boost_active.vehicle_id', '=', $table.'.id')
                ->where('listing_boost_active.expires_at', '>', now());
        });
        $query->orderByRaw('CASE WHEN listing_boost_active.id IS NOT NULL THEN 0 ELSE 1 END');
    }

    /**
     * Order by approximate distance (km) from viewer to {@see Location} matched on {@code vehicles.postcode}.
     * Falls back to id ordering when coordinates are missing or {@see locations} table is absent.
     *
     * @param  array<string, mixed>  $filters
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Vehicle>  $query
     */
    private function applySellerDistanceSort(Builder $query, string $sort, array $filters): void
    {
        $table = $query->getModel()->getTable();
        $lat = isset($filters['viewer_latitude']) ? (float) $filters['viewer_latitude'] : null;
        $lng = isset($filters['viewer_longitude']) ? (float) $filters['viewer_longitude'] : null;

        if ($lat === null || $lng === null || ! Schema::hasTable('locations')) {
            $query->orderByDesc($table.'.id');

            return;
        }

        $dir = $sort === 'distance_asc' ? 'asc' : 'desc';

        $locSub = DB::table('locations')
            ->select('postcode', DB::raw('MAX(latitude) as lat'), DB::raw('MAX(longitude) as lng'))
            ->groupBy('postcode');

        $query->select($table.'.*');
        $query->leftJoinSub($locSub, 'loc_sort', function ($join) use ($table): void {
            $join->on('loc_sort.postcode', '=', $table.'.postcode');
        });

        $kmExpr = '(6371 * ACOS(LEAST(1, GREATEST(-1,
            COS(RADIANS(?)) * COS(RADIANS(loc_sort.lat)) * COS(RADIANS(loc_sort.lng) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(loc_sort.lat))
        ))))';

        $query->orderByRaw(
            'CASE WHEN loc_sort.lat IS NULL OR loc_sort.lng IS NULL THEN 999999 ELSE '.$kmExpr.' END '.$dir,
            [$lat, $lng, $lat]
        )->orderByDesc($table.'.id');
    }
}
