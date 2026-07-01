<?php

namespace App\Services;

use App\Exceptions\NummerpladeApiException;
use App\Models\DmrBrand;
use App\Models\DmrBridgeVehicleDrivmiddel;
use App\Models\DmrFactVehicle;
use App\Models\DmrDriveEnergy;
use App\Models\DmrModel;
use Illuminate\Support\Facades\Log;

/**
 * Local DMR dataset: lookup vehicle facts by registration number.
 *
 * @method array<int, array{id:int,name:string}> searchManualBrands(?string $search, int $limit)
 * @method array<int, array{id:int,name:string,brand_id:int}> searchManualModels(?string $search, ?int $brandId, int $limit)
 * @method array<int, array{id:int,name:string}> searchManualFuelTypes(?string $search, int $limit)
 */
class DmrFactVehicleLookupService
{
    public function lookupByRegistration(string $registration): array
    {
        try {
            $normalizedRegistration = $this->normalizeRegistration($registration);
            $vehicle = $this->findFactVehicleByRegistration($normalizedRegistration);

            if (!$vehicle) {
                throw NummerpladeApiException::invalidInput(__('messages.api.invalid_registration_or_vin'));
            }

            return $this->mapFactVehicleToLookupResponse($vehicle, $normalizedRegistration);
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
            throw NummerpladeApiException::unknown(__('messages.api.dmr_local_lookup_failed'));
        }
    }

    public function normalizeRegistration(string $registration): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($registration))) ?? '';
    }

    public function normalizeVin(string $vin): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($vin))) ?? '';
    }

    /**
     * Same eager loads for registration and VIN resolution — no N+1.
     *
     * @return array<string, mixed>
     */
    protected function factVehicleEagerLoads(): array
    {
        return [
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
        ];
    }

    public function lookupByVin(string $vin): array
    {
        try {
            $normalizedVin = $this->normalizeVin($vin);
            $vehicle = $this->findFactVehicleByVin($normalizedVin);

            if (! $vehicle) {
                throw NummerpladeApiException::invalidInput(__('messages.api.invalid_registration_or_vin'));
            }

            $fallbackRegistration = $vehicle->registrering_nummer ?? $normalizedVin;

            return $this->mapFactVehicleToLookupResponse($vehicle, $fallbackRegistration);
        } catch (\Throwable $e) {
            if ($e instanceof NummerpladeApiException) {
                throw $e;
            }

            Log::error('Error in DMR fact vehicle VIN lookup', [
                'vin' => $vin,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'source' => 'dmr_local',
            ]);
            throw NummerpladeApiException::unknown(__('messages.api.dmr_local_lookup_failed'));
        }
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
            ->with($this->factVehicleEagerLoads())
            ->where('registrering_nummer', $normalizedRegistration)
            ->orderByDesc('registrering_status_dato')
            ->orderByDesc('foerste_registrering_dato')
            ->orderByDesc('id')
            ->first();
    }

    protected function findFactVehicleByVin(string $normalizedVin): ?DmrFactVehicle
    {
        if ($normalizedVin === '' || strlen($normalizedVin) < 5) {
            return null;
        }

        return DmrFactVehicle::query()
            ->with($this->factVehicleEagerLoads())
            ->where(function ($q) use ($normalizedVin) {
                $q->where('stel_nummer', $normalizedVin)
                    ->orWhereRaw(
                        "UPPER(REPLACE(REPLACE(TRIM(COALESCE(stel_nummer, '')), ' ', ''), '-', '')) = ?",
                        [$normalizedVin]
                    );
            })
            ->orderByDesc('registrering_status_dato')
            ->orderByDesc('foerste_registrering_dato')
            ->orderByDesc('id')
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
                'count' => $line->antal,
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
            'km_per_liter' => $primary && $primary->motor_km_per_liter !== null ? (float) $primary->motor_km_per_liter : null,
            'co2_emission' => $primary && $primary->miljoe_co2_udslip !== null ? (float) $primary->miljoe_co2_udslip : null,
            'electrical_consumption' => $primary && $primary->motor_elektrisk_forbrug !== null ? (float) $primary->motor_elektrisk_forbrug : null,
            // 'drivmiddel_primaer' => $primary ? (bool) $primary->drivmiddel_primaer : null,
            'engine_power_kw' => $kw,
            'engine_power_hp' => $this->kwToHp($kw),
            'engine_size_cc' => $cc !== null ? (int) round($cc) : null,
            'engine_displacement_litres' => $this->ccToDisplayLitres($cc),
            'first_registration_date' => $vehicle->foerste_registrering_dato?->format('Y-m-d'),
            'first_registration_year' => $vehicle->foerste_registrering_dato?->format('Y'),
            'vin' => $vehicle->stel_nummer,
            'co2_emission_2' => $vehicle->emission_co !== null ? (float) $vehicle->emission_co : null,
            'nox_emission' => $vehicle->emission_nox !== null ? (float) $vehicle->emission_nox : null,
            'particle_filter' => $vehicle->partikel_filter && intval($vehicle->partikel_filter) > 0 ? true : false,
            'axle_count' => $vehicle->aksel_antal,
            'door_count' => $vehicle->antal_doere,
            'gear_count' => $vehicle->antal_gear,
            'max_speed' => $vehicle->maksimum_hastighed,
            'model_year' => $this->modelYearEffective($vehicle),
            'ncap_test' => $vehicle->ncap_test && intval($vehicle->ncap_test) > 0 ? true : false,
            'seats_min' => $vehicle->siddepladser_minimum,
            'seats_max' => $vehicle->siddepladser_maksimum,
            'maximum_weight_kg' => $vehicle->teknisk_total_vaegt,
            'registration_status' => $vehicle->registrationStatus?->name,
            'last_registration_change' => $vehicle->registrering_status_dato?->format('Y-m-d'),
            'measurement_norm' => $measurementNorm, // this will be added in measurement_norms table
            'equipments' => $vehicle->oevrigt_udstyr,
            'brand' => $this->idName($brand),
            'model' => $this->idName($model),
            'variant' => $this->idName($variant),
            'euronorm' => $this->idName($vehicle->emissionNorm),
            'body_type' => $this->idName($vehicle->bodyType),
            'use' => $this->idName($vehicle->vehicleUse),
            'color' => $this->idName($vehicle->colour),
            'fuel_type' => $this->idName($fuelType),
            'specifications' => $equipments, // each spec name will be added in specifications table and count will be added in vehicle_specs table against vehicle_id and spec_id
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

        return round((float) $kw * 1.36, 2);
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
     * Manual dropdown: all brands when not searching; filtered list when searching (capped).
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchManualBrands(?string $search, int $limit): array
    {
        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrBrand::query()->select(['id', 'name'])->orderBy('name');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
            $limit = max(1, min((int) $limit, 500));

            return $query->limit($limit)->get()
                ->map(fn (DmrBrand $b) => ['id' => (int) $b->id, 'name' => (string) $b->name])
                ->values()
                ->all();
        }

        return $query->get()
            ->map(fn (DmrBrand $b) => ['id' => (int) $b->id, 'name' => (string) $b->name])
            ->values()
            ->all();
    }

    /**
     * Manual dropdown: models for a brand only. Empty when no brand is selected.
     *
     * @return array<int, array{id:int,name:string,brand_id:int}>
     */
    public function searchManualModels(?string $search, ?int $brandId, int $limit): array
    {
        if ($brandId === null || (int) $brandId <= 0) {
            return [];
        }

        $limit = max(1, (int) $limit);
        $limit = min(500, $limit);

        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrModel::query()
            ->select(['id', 'name', 'brand_id'])
            ->where('brand_id', (int) $brandId)
            ->whereNotIn('name', ['-', '.'])
            ->orderBy('name');

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
     * Manual dropdown search: drive energies in `dmr_drive_energies`.
     *
     * @return array<int, array{id:int,name:string}>
     */
    public function searchManualFuelTypes(?string $search, int $limit): array
    {

        $searchTerm = $search !== null ? trim($search) : '';

        $query = DmrDriveEnergy::query()->select(['id', 'name'])->orderBy('name');
        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        return $query->get()
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
        $yearInt = $manualModelYearId;
        if ($yearInt < 1950 || $yearInt > 2100) {
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
