<?php

namespace App\Services;

use App\Exceptions\NummerpladeApiException;
use App\Models\DmrBrand;
use App\Models\DmrBridgeVehicleDrivmiddel;
use App\Models\DmrFactVehicle;
use App\Models\DmrDriveEnergy;
use App\Models\DmrModel;
use App\Models\ModelYear;
use Illuminate\Support\Facades\Log;

/**
 * Local DMR dataset: lookup vehicle facts by registration number.
 * Returns a slim, explicit payload for API consumers (no legacy Nummerplade shape).
 *
 * @method array<int, array{id:int,name:string}> searchManualBrands(?string $search, int $limit)
 * @method array<int, array{id:int,name:string,brand_id:int}> searchManualModels(?string $search, ?int $brandId, int $limit)
 * @method array<int, array{id:int,name:string}> searchManualModelYears(?string $search, int $limit)
 * @method array<int, array{id:int,name:string}> searchManualFuelTypes(?string $search, int $limit)
 */
class DmrFactVehicleLookupService
{
    /**
     * Lookup by license plate. Throws NummerpladeApiException on miss or failure (HTTP layer compatibility).
     *
     * @param  bool  $advanced  Unused; kept for API parity with Nummerplade.
     */
    public function lookupByRegistration(string $registration, bool $advanced = false): array
    {
        try {
            $startTime = microtime(true);
            $normalizedRegistration = $this->normalizeRegistration($registration);
            $vehicle = $this->findFactVehicleByRegistration($normalizedRegistration);

            if (!$vehicle) {
                Log::info('DMR fact vehicle registration lookup miss', [
                    'method' => 'lookupByRegistration',
                    'registration' => $registration,
                    'registration_normalized' => $normalizedRegistration,
                    'source' => 'dmr_local',
                ]);

                throw NummerpladeApiException::invalidInput('Invalid registration or VIN provided');
            }

            $data = $this->mapFactVehicleToLookupResponse($vehicle, $normalizedRegistration);
            $processingTime = microtime(true) - $startTime;

            Log::info('DMR fact vehicle registration lookup hit', [
                'method' => 'lookupByRegistration',
                'registration' => $registration,
                'registration_normalized' => $normalizedRegistration,
                'source' => 'dmr_local',
                'dmr_fact_vehicle_id' => $vehicle->id,
                'processing_time' => round($processingTime, 3) . 's',
                'data_keys_count' => count($data),
            ]);

            return $data;
        } catch (\Throwable $e) {
            if ($e instanceof NummerpladeApiException) {
                throw $e;
            }

            Log::error('Error in DMR fact vehicle registration lookup', [
                'registration' => $registration,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'source' => 'dmr_local',
            ]);
            throw NummerpladeApiException::unknown('Unable to process local vehicle lookup data');
        }
    }

    public function normalizeRegistration(string $registration): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($registration))) ?? '';
    }

    /**
     * One Eloquent query with constrained eager loads (selected columns only) — no N+1.
     */
    protected function findFactVehicleByRegistration(string $normalizedRegistration): ?DmrFactVehicle
    {
        if ($normalizedRegistration === '') {
            return null;
        }

        return DmrFactVehicle::query()
            ->where("registrering_nummer",$normalizedRegistration)
            ->orderByDesc('registrering_status_dato')
            ->orderByDesc('foerste_registrering_dato')
            ->orderByDesc('id')
            ->with([
                'variant' => fn ($q) => $q->select('id', 'model_id', 'name'),
                'variant.model' => fn ($q) => $q->select('id', 'brand_id', 'name'),
                'variant.model.brand' => fn ($q) => $q->select('id', 'name'),
                'vehicleUse' => fn ($q) => $q->select('id', 'name'),
                'bodyType' => fn ($q) => $q->select('id', 'name'),
                'colour' => fn ($q) => $q->select('id', 'name'),
                'emissionNorm' => fn ($q) => $q->select('id', 'name'),
                'registrationStatus' => fn ($q) => $q->select('id', 'name'),
                'drivmiddelLines' => fn ($q) => $q
                    ->orderBy('line_order')
                    ->select([
                        'id',
                        'vehicle_id',
                        'line_order',
                        'drive_energy_id',
                        'measurement_norm_id',
                        'drivmiddel_primaer',
                        'motor_km_per_liter',
                        'miljoe_co2_udslip',
                        'motor_elektrisk_forbrug',
                    ]),
                'drivmiddelLines.driveEnergy' => fn ($q) => $q->select('id', 'name'),
                'drivmiddelLines.measurementNorm' => fn ($q) => $q->select('id', 'name'),
                'equipmentLines' => fn ($q) => $q
                    ->orderBy('line_order')
                    ->select([
                        'id',
                        'vehicle_id',
                        'line_order',
                        'equipment_type_id',
                        'antal',
                    ]),
                'equipmentLines.equipmentType' => fn ($q) => $q->select('id', 'name'),
            ])
            ->first();
    }

    protected function resolvePrimaryDrivmiddelLine(DmrFactVehicle $vehicle): ?DmrBridgeVehicleDrivmiddel
    {
        $lines = $vehicle->drivmiddelLines;
        if ($lines->isEmpty()) {
            return null;
        }

        $sorted = $lines->sortBy('line_order')->values();

        return $sorted->first(fn (DmrBridgeVehicleDrivmiddel $line) => (bool) $line->drivmiddel_primaer)
            ?? $sorted->first();
    }

    /**
     * @param  object|null  $row  Model with id and name (e.g. DmrBrand)
     * @return array{id: int, name: string}|null
     */
    protected function idName(?object $row): ?array
    {
        if ($row === null || $row->id === null) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
        ];
    }

    protected function mapFactVehicleToLookupResponse(DmrFactVehicle $vehicle, string $normalizedRegistration): array
    {
        $variant = $vehicle->variant;
        $model = $variant?->model;
        $brand = $model?->brand;
        $primary = $this->resolvePrimaryDrivmiddelLine($vehicle);

        $equipments = $vehicle->equipmentLines
            ->sortBy('line_order')
            ->map(fn ($line) => [
                'name' => $line->equipmentType?->name,
                'antal' => $line->antal,
            ])
            ->filter(fn ($item) => !empty($item['name']))
            ->values()
            ->all();

        $kw = $vehicle->motor_stoerste_effekt !== null ? (float) $vehicle->motor_stoerste_effekt : null;
        $cc = $vehicle->motor_slag_volumen !== null ? (float) $vehicle->motor_slag_volumen : null;

        $fuelType = $primary?->driveEnergy;
        $measurementNorm = $primary?->measurementNorm?->name;

        return [
            'dmr_fact_vehicle_id' => (int) $vehicle->id,
            'registration' => $vehicle->registrering_nummer ?? $normalizedRegistration,
            'brand' => $this->idName($brand),
            'model' => $this->idName($model),
            'variant' => $this->idName($variant),
            'body_type' => $this->idName($vehicle->bodyType),
            'use' => $this->idName($vehicle->vehicleUse),
            'color' => $this->idName($vehicle->colour),
            'fuel_type' => $this->idName($fuelType),
            'measurement_norm' => $measurementNorm,
            'motor_km_per_liter' => $primary && $primary->motor_km_per_liter !== null ? (float) $primary->motor_km_per_liter : null,
            'miljoe_co2_udslip' => $primary && $primary->miljoe_co2_udslip !== null ? (float) $primary->miljoe_co2_udslip : null,
            'motor_elektrisk_forbrug' => $primary && $primary->motor_elektrisk_forbrug !== null ? (float) $primary->motor_elektrisk_forbrug : null,
            'drivmiddel_primaer' => $primary ? (bool) $primary->drivmiddel_primaer : null,
            'engine_power_kw' => $kw,
            'engine_power_hp' => $this->kwToHp($kw),
            'engine_displacement_cc' => $cc !== null ? (int) round($cc) : null,
            'engine_displacement_litres' => $this->ccToDisplayLitres($cc),
            'euronorm' => $this->idName($vehicle->emissionNorm),
            'first_registration_date' => $vehicle->foerste_registrering_dato?->format('Y-m-d'),
            'chassis_number' => $vehicle->stel_nummer,
            'co2_emission' => $vehicle->emission_co !== null ? (float) $vehicle->emission_co : null,
            'nox_emission' => $vehicle->emission_nox !== null ? (float) $vehicle->emission_nox : null,
            'particle_filter' => $this->formatYesNo($vehicle->partikel_filter),
            'axle_count' => $vehicle->aksel_antal,
            'door_count' => $vehicle->antal_doere,
            'gear_count' => $vehicle->antal_gear,
            'max_speed' => $vehicle->maksimum_hastighed,
            'model_year' => $vehicle->model_aar,
            'model_year_effective' => $this->modelYearEffective($vehicle),
            'ncap_test' => $this->formatYesNo($vehicle->ncap_test),
            'equipments' => $equipments,
            'equipments_other' => $vehicle->oevrigt_udstyr,
            'seats_min' => $vehicle->siddepladser_minimum,
            'seats_max' => $vehicle->siddepladser_maksimum,
            'maximum_weight_kg' => $vehicle->teknisk_total_vaegt,
            'registration_status' => $vehicle->registrationStatus?->name,
            'last_registration_change' => $vehicle->registrering_status_dato?->format('Y-m-d'),
        ];
    }

    protected function formatYesNo(?bool $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value ? 'Yes' : 'No';
    }

    protected function kwToHp(?float $kw): ?float
    {
        if ($kw === null) {
            return null;
        }

        return round($kw * 1.36, 2);
    }

    /**
     * Round displacement (cc) to nearest 100, then express as litres (e.g. 1582 → 1.6).
     */
    protected function ccToDisplayLitres(?float $cc): ?float
    {
        if ($cc === null) {
            return null;
        }

        $roundedCc = round($cc / 100) * 100;

        return round($roundedCc / 1000, 1);
    }

    protected function modelYearEffective(DmrFactVehicle $vehicle): ?int
    {
        if ($vehicle->model_aar !== null) {
            return (int) $vehicle->model_aar;
        }

        if ($vehicle->foerste_registrering_dato !== null) {
            return (int) $vehicle->foerste_registrering_dato->format('Y');
        }

        return null;
    }

    /**
     * Manual dropdown search: limit hard-capped to 10 for performance.
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchManualBrands(?string $search, int $limit): array
    {
        $limit = max(1, (int) $limit);
        $limit = min(10, $limit);

        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrBrand::query()->select(['id', 'name'])->orderBy('name');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (DmrBrand $b) => ['id' => (int) $b->id, 'name' => (string) $b->name])
            ->values()
            ->all();
    }

    /**
     * Manual dropdown search: limit hard-capped to 10 for performance.
     *
     * @return array<int, array{id:int,name:string,brand_id:int}>
     */
    public function searchManualModels(?string $search, ?int $brandId, int $limit): array
    {
        $limit = max(1, (int) $limit);
        $limit = min(10, $limit);

        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrModel::query()->select(['id', 'name', 'brand_id'])->orderBy('name');
        if ($brandId !== null) {
            $brandId = (int) $brandId;
            if ($brandId > 0) {
                $query->where('brand_id', $brandId);
            }
        }
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (DmrModel $m) => [
                'id' => (int) $m->id,
                'name' => (string) $m->name,
                'brand_id' => (int) $m->brand_id,
            ])
            ->values()
            ->all();
    }

    /**
     * Manual dropdown search: model years in `model_years` table.
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchManualModelYears(?string $search, int $limit): array
    {
        $limit = max(1, (int) $limit);
        $limit = min(10, $limit);

        $searchTerm = $search !== null ? trim($search) : '';

        $query = ModelYear::query()->select(['id', 'name'])->orderBy('name', 'desc');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (ModelYear $y) => ['id' => (int) $y->id, 'name' => (string) $y->name])
            ->values()
            ->all();
    }

    /**
     * Manual dropdown search: drive energies in `dmr_drive_energies`.
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchManualFuelTypes(?string $search, int $limit): array
    {
        $limit = max(1, (int) $limit);
        $limit = min(10, $limit);

        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrDriveEnergy::query()->select(['id', 'name'])->orderBy('name');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->limit($limit)->get()
            ->map(fn (DmrDriveEnergy $f) => ['id' => (int) $f->id, 'name' => (string) $f->name])
            ->values()
            ->all();
    }

    /**
     * Resolve a single candidate `dmr_fact_vehicle_id` based on the manual dropdown selections.
     *
     * This is used to allow manual submissions to satisfy the `dmr_fact_vehicle_id` requirement
     * without loading the full DMR dataset client-side.
     */
    public function resolveDmrFactVehicleIdByManual(
        int $manualBrandId,
        int $manualModelId,
        int $manualModelYearId,
        int $manualFuelTypeId
    ): ?int {
        $modelYear = ModelYear::query()->select(['id', 'name'])->find($manualModelYearId);
        if (!$modelYear) {
            return null;
        }

        $yearName = (string) $modelYear->name;
        $yearInt = is_numeric($yearName) ? (int) $yearName : null;
        if (!$yearInt) {
            return null;
        }

        $candidate = DmrFactVehicle::query()
            ->where('model_aar', $yearInt)
            ->whereHas('variant.model', function ($q) use ($manualBrandId, $manualModelId) {
                $q->where('brand_id', $manualBrandId);
                $q->where('id', $manualModelId);
            })
            ->whereHas('drivmiddelLines', function ($q) use ($manualFuelTypeId) {
                $q->where('drive_energy_id', $manualFuelTypeId);
            })
            ->orderByDesc('registrering_status_dato')
            ->orderByDesc('foerste_registrering_dato')
            ->orderByDesc('id')
            ->first();

        return $candidate ? (int) $candidate->id : null;
    }
}
