<?php

namespace App\Services\VehicleImport;

use App\Constants\VehicleListStatus;
use App\Exceptions\NummerpladeApiException;
use App\Models\Vehicle;
use App\Services\DmrFactVehicleLookupService;

class VehicleImportRowResolver
{
    public function __construct(
        private VehicleImportLookupCache $lookupCache,
        private DmrFactVehicleLookupService $dmrFactVehicleLookupService,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *   payload: array<string, mixed>,
     *   warnings: list<array{field: string, value: string, message: string}>,
     *   errors: list<array{field: string, value: string, message: string}>
     * }
     */
    public function resolve(
        array $row,
        int $dealerId,
        bool $dmrRequested,
        ?VehicleImportBatchContext $context = null,
    ): array {
        $warnings = [];
        $errors = [];
        $payload = [];

        if ($dmrRequested) {
            try {
                $dmr = $this->fetchDmrPayload($row, $context);
                $payload = $this->mapDmrToVehiclePayload($dmr);
            } catch (NummerpladeApiException $e) {
                $errors[] = [
                    'field' => isset($row['registration']) && trim((string) $row['registration']) !== ''
                        ? 'registration'
                        : 'vin',
                    'value' => (string) ($row['registration'] ?? $row['vin'] ?? ''),
                    'message' => $e->getMessage(),
                ];

                return ['payload' => [], 'warnings' => $warnings, 'errors' => $errors];
            }
        }

        unset($row['list_status'], $row['vehicle_list_status'], $row['list_status_id'], $row['vehicle_list_status_id']);

        $this->applyExcelScalars($row, $payload);
        $this->applyExcelForeignKeys($row, $payload, $warnings, $errors);
        $this->applyExcelEquipmentAndSpecs($row, $payload);

        $payload['list_status_id'] = VehicleListStatus::PUBLISHED;
        if (empty($payload['published_at'])) {
            $payload['published_at'] = now()->toDateTimeString();
        }

        $registration = trim((string) ($payload['registration'] ?? ''));
        if ($registration !== '') {
            $normalized = $this->dmrFactVehicleLookupService->normalizeRegistration($registration);
            $payload['registration'] = $normalized;
            if ($context !== null && $context->hasSeenRegistration($normalized)) {
                $errors[] = [
                    'field' => 'registration',
                    'value' => $registration,
                    'message' => __('messages.api.vehicle_import_duplicate_registration_in_file'),
                ];
            } elseif ($this->registrationExistsForDealer($dealerId, $normalized)) {
                $errors[] = [
                    'field' => 'registration',
                    'value' => $registration,
                    'message' => __('messages.api.vehicle_import_duplicate_registration'),
                ];
            }
        }

        if (! array_key_exists('price', $payload) || $payload['price'] === '' || $payload['price'] === null) {
            $errors[] = [
                'field' => 'price',
                'value' => (string) ($row['price'] ?? ''),
                'message' => __('messages.api.vehicle_import_price_required'),
            ];
        }

        if (empty($payload['sales_type_id'])) {
            $errors[] = [
                'field' => 'sales_type',
                'value' => (string) ($row['sales_type'] ?? ''),
                'message' => __('messages.api.vehicle_import_sales_type_required'),
            ];
        }

        if (empty($payload['brand_id'])) {
            $errors[] = [
                'field' => 'brand',
                'value' => (string) ($row['brand'] ?? ''),
                'message' => __('messages.api.vehicle_import_brand_required'),
            ];
        }

        if (empty($payload['model_id'])) {
            $errors[] = [
                'field' => 'model',
                'value' => (string) ($row['model'] ?? ''),
                'message' => __('messages.api.vehicle_import_model_required'),
            ];
        }

        if (empty($payload['dmr_fact_vehicle_id']) && empty($payload['fuel_type_id'])) {
            $errors[] = [
                'field' => 'fuel_type',
                'value' => (string) ($row['fuel_type'] ?? ''),
                'message' => __('messages.api.vehicle_import_fuel_type_required'),
            ];
        }

        return ['payload' => $payload, 'warnings' => $warnings, 'errors' => $errors];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function fetchDmrPayload(array $row, ?VehicleImportBatchContext $context = null): array
    {
        $registration = trim((string) ($row['registration'] ?? ''));
        $vin = trim((string) ($row['vin'] ?? ''));

        if ($registration !== '') {
            $cacheKey = 'reg:'.strtoupper($registration);
            $cached = $context?->getCachedDmr($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $dmr = $this->dmrFactVehicleLookupService->lookupByRegistration($registration);
            $context?->cacheDmr($cacheKey, $dmr);

            return $dmr;
        }

        if ($vin !== '') {
            $cacheKey = 'vin:'.strtoupper($vin);
            $cached = $context?->getCachedDmr($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $dmr = $this->dmrFactVehicleLookupService->lookupByVin($vin);
            $context?->cacheDmr($cacheKey, $dmr);

            return $dmr;
        }

        throw NummerpladeApiException::invalidInput(__('messages.api.vehicle_import_dmr_identifier_required'));
    }

    /**
     * @param  array<string, mixed>  $dmr
     * @return array<string, mixed>
     */
    private function mapDmrToVehiclePayload(array $dmr): array
    {
        $payload = [
            'dmr_fact_vehicle_id' => $dmr['dmr_fact_vehicle_id'] ?? null,
            'registration' => $dmr['registration'] ?? null,
            'vin' => $dmr['vin'] ?? null,
            'km_per_liter' => $dmr['km_per_liter'] ?? null,
            'co2_emission' => $dmr['co2_emission'] ?? null,
            'electrical_consumption' => $dmr['electrical_consumption'] ?? null,
            'engine_power_kw' => $dmr['engine_power_kw'] ?? null,
            'engine_power_hp' => $dmr['engine_power_hp'] ?? null,
            'engine_size_cc' => $dmr['engine_size_cc'] ?? null,
            'engine_displacement_litres' => $dmr['engine_displacement_litres'] ?? null,
            'first_registration_date' => $dmr['first_registration_date'] ?? null,
            'first_registration_year' => $dmr['first_registration_year'] ?? null,
            'nox_emission' => $dmr['nox_emission'] ?? null,
            'particle_filter' => $dmr['particle_filter'] ?? null,
            'axle_count' => $dmr['axle_count'] ?? null,
            'door_count' => $dmr['door_count'] ?? null,
            'gear_count' => $dmr['gear_count'] ?? null,
            'max_speed' => $dmr['max_speed'] ?? null,
            'model_year' => $dmr['model_year'] ?? null,
            'ncap_test' => $dmr['ncap_test'] ?? null,
            'seats_min' => $dmr['seats_min'] ?? null,
            'seats_max' => $dmr['seats_max'] ?? null,
            'maximum_weight_kg' => $dmr['maximum_weight_kg'] ?? null,
            'registration_status' => $dmr['registration_status'] ?? null,
            'last_registration_change' => $dmr['last_registration_change'] ?? null,
        ];

        foreach (['brand' => 'brand_id', 'model' => 'model_id', 'variant' => 'variant_id', 'fuel_type' => 'fuel_type_id', 'body_type' => 'body_type_id', 'use' => 'vehicle_use_id', 'color' => 'colour_id', 'euronorm' => 'emission_norm_id'] as $dmrKey => $column) {
            if (isset($dmr[$dmrKey]['id'])) {
                $payload[$column] = (int) $dmr[$dmrKey]['id'];
            }
        }

        if (! empty($dmr['measurement_norm'])) {
            $id = $this->lookupCache->resolveFlat('measurement_norm_id', (string) $dmr['measurement_norm']);
            if ($id !== null) {
                $payload['measurement_norm_id'] = $id;
            }
        }

        if (! empty($dmr['equipments']) && is_string($dmr['equipments'])) {
            $payload['lookup_equipments'] = $dmr['equipments'];
        }

        if (! empty($dmr['specifications']) && is_array($dmr['specifications'])) {
            $payload['lookup_specifications'] = json_encode($dmr['specifications']);
        }

        $brandName = $dmr['brand']['name'] ?? null;
        $modelName = $dmr['model']['name'] ?? null;
        if ($brandName && $modelName) {
            $payload['title'] = trim($brandName.' '.$modelName);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $payload
     */
    private function applyExcelScalars(array $row, array &$payload): void
    {
        foreach (VehicleImportColumnDefinitions::SCALAR_COLUMNS as $header => $column) {
            if (! array_key_exists($header, $row)) {
                continue;
            }
            $value = $row[$header];
            if ($value === null || $value === '') {
                continue;
            }
            $payload[$column] = $this->castScalar($column, $value);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $payload
     * @param  list<array{field: string, value: string, message: string}>  $warnings
     * @param  list<array{field: string, value: string, message: string}>  $errors
     */
    private function applyExcelForeignKeys(array $row, array &$payload, array &$warnings, array &$errors): void
    {
        $brandId = $payload['brand_id'] ?? null;

        foreach (VehicleImportColumnDefinitions::FK_COLUMNS as $header => [$column, $required]) {
            if (! array_key_exists($header, $row)) {
                continue;
            }
            $raw = trim((string) $row[$header]);
            if ($raw === '') {
                continue;
            }

            $resolved = null;
            if ($header === 'brand') {
                $resolved = $this->lookupCache->resolveBrand($raw);
                if ($resolved !== null) {
                    $brandId = $resolved;
                }
            } elseif ($header === 'model') {
                if ($brandId === null) {
                    $errors[] = [
                        'field' => 'model',
                        'value' => $raw,
                        'message' => __('messages.api.vehicle_import_model_requires_brand'),
                    ];

                    continue;
                }
                $resolved = $this->lookupCache->resolveModel($raw, (int) $brandId);
            } elseif ($header === 'variant') {
                $modelId = $payload['model_id'] ?? null;
                if ($modelId === null) {
                    $errors[] = [
                        'field' => 'variant',
                        'value' => $raw,
                        'message' => __('messages.api.vehicle_import_variant_requires_model'),
                    ];

                    continue;
                }
                $resolved = $this->lookupCache->resolveVariant($raw, (int) $modelId);
            } else {
                $resolved = $this->lookupCache->resolveFlat($column, $raw);
            }

            if ($resolved === null) {
                if ($required) {
                    $errors[] = [
                        'field' => $header,
                        'value' => $raw,
                        'message' => __('messages.api.vehicle_import_lookup_not_found', ['field' => $header]),
                    ];
                } else {
                    $warnings[] = [
                        'field' => $header,
                        'value' => $raw,
                        'message' => __('messages.api.vehicle_import_lookup_skipped', ['field' => $header]),
                    ];
                }

                continue;
            }

            $payload[$column] = $resolved;
            if ($header === 'brand') {
                $brandId = $resolved;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $payload
     */
    private function applyExcelEquipmentAndSpecs(array $row, array &$payload): void
    {
        if (isset($row['equipment']) && trim((string) $row['equipment']) !== '') {
            $payload['lookup_equipments'] = trim((string) $row['equipment']);
        }

        if (isset($row['specifications']) && trim((string) $row['specifications']) !== '') {
            $payload['lookup_specifications'] = trim((string) $row['specifications']);
        }
    }

    private function castScalar(string $column, mixed $value): mixed
    {
        if (in_array($column, ['is_import', 'is_factory_new', 'particle_filter', 'ncap_test', 'leasing_enabled'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($column, ['price', 'km_driven', 'km_per_liter', 'co2_emission', 'engine_power_kw', 'engine_power_hp', 'engine_displacement_litres', 'annual_tax', 'internal_cost_price', 'leasing_first_payment', 'leasing_residual_value', 'leasing_total_cost', 'fuel_consumption_wltp', 'fuel_consumption_nedc'], true)) {
            return is_numeric($value) ? (float) $value : $value;
        }

        if (in_array($column, ['door_count', 'seats_min', 'seats_max', 'max_speed', 'axle_count', 'towing_weight', 'model_year', 'battery_capacity', 'range_km', 'gear_count', 'maximum_weight_kg', 'engine_size_cc', 'leasing_duration', 'leasing_annual_mileage', 'dmr_fact_vehicle_id', 'first_registration_year'], true)) {
            return is_numeric($value) ? (int) $value : $value;
        }

        return is_string($value) ? trim($value) : $value;
    }

    private function registrationExistsForDealer(int $dealerId, string $normalizedRegistration): bool
    {
        return Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->where('registration', $normalizedRegistration)
            ->exists();
    }
}
