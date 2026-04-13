<?php

namespace App\Services;

use App\Models\DmrVariant;
use App\Models\Vehicle;
use App\Models\VehicleSpecDefinition;
use Illuminate\Support\Carbon;

/**
 * Single place for vehicle “detail” eager loads and the canonical detail payload (web + API).
 */
class VehicleDetailPresentationService
{
    /**
     * Eager-load relations needed for {@see buildDetailPayload()}.
     * Controllers should merge this with route-specific loads (e.g. images, dealer.owner).
     *
     * @return array<string, callable(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation): void>
     */
    public function detailEagerLoads(): array
    {
        $nameOnly = static function ($q): void {
            $q->select('id', 'name');
        };

        return [
            'specifications' => static function ($q): void {
                $q->select('specifications.id', 'specifications.name');
            },
            'equipment' => static function ($q): void {
                $q->select('equipments.id', 'equipments.name');
            },
            'brand' => $nameOnly,
            'model' => $nameOnly,
            'variant' => $nameOnly,
            'fuelType' => $nameOnly,
            'bodyType' => $nameOnly,
            'colour' => $nameOnly,
            'emissionNorm' => $nameOnly,
            'vehicleUse' => $nameOnly,
            'measurementNorm' => $nameOnly,
            'gearType' => $nameOnly,
            'condition' => $nameOnly,
            'vehicleListStatus' => $nameOnly,
            'listingType' => $nameOnly,
            'salesType' => $nameOnly,
            'priceType' => $nameOnly,
            'category' => $nameOnly,
            'dmrFactVehicle' => static function ($q): void {
                $q->select('id', 'oevrigt_udstyr', 'teknisk_total_vaegt');
            },
            'dmrFactVehicle.registrationStatus' => $nameOnly,
        ];
    }

    /**
     * Canonical detail array for Blade and JSON. Requires relations from {@see detailEagerLoads()}.
     *
     * @return array<string, mixed>
     */
    public function buildDetailPayload(Vehicle $vehicle): array
    {
        $v = $vehicle;
        $attrs = $v->getAttributes();

        $equipment = $v->relationLoaded('equipment')
            ? $v->equipment->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values()->all()
            : [];

        $specifications = [];
        if ($v->relationLoaded('specifications')) {
            foreach ($v->specifications as $spec) {
                $specifications[] = [
                    'name' => $spec->name,
                    'count' => (int) ($spec->pivot->count ?? 1),
                ];
            }
        }

        $modelYear = $v->model_year;
        $modelYearDisplay = $modelYear !== null ? (string) $modelYear : null;

        $specDefinitions = [];
        if ($v->brand_id && $v->model_id && $modelYear !== null && $modelYear !== '' && (int) $modelYear > 0) {
            $rows = VehicleSpecDefinition::query()
                ->matchingVehicle($v)
                ->orderBy('name')
                ->get(['name', 'value', 'variant_ids']);

            $allVariantIds = $rows
                ->flatMap(static fn (VehicleSpecDefinition $row) => $row->normalizedVariantIds())
                ->unique()
                ->filter()
                ->values()
                ->all();

            $namesById = [];
            if ($allVariantIds !== []) {
                $namesById = DmrVariant::query()
                    ->whereIn('id', $allVariantIds)
                    ->pluck('name', 'id')
                    ->all();
            }

            $specDefinitions = $rows
                ->map(static function (VehicleSpecDefinition $row) use ($namesById) {
                    $variantsScope = null;
                    if (! $row->isModelWide()) {
                        $labels = [];
                        foreach ($row->normalizedVariantIds() as $vid) {
                            if (isset($namesById[$vid])) {
                                $labels[] = (string) $namesById[$vid];
                            }
                        }
                        $variantsScope = $labels !== [] ? implode(', ', $labels) : null;
                    }

                    return [
                        'name' => $row->name,
                        'value' => $row->value,
                        'variants_scope' => $variantsScope,
                    ];
                })
                ->values()
                ->all();
        }

        $dmr = $v->dmrFactVehicle;
        $technicalFromDmr = $dmr?->teknisk_total_vaegt;
        $extraEquipmentText = $dmr?->oevrigt_udstyr;

        $date = static function ($value): ?string {
            if ($value === null) {
                return null;
            }
            if ($value instanceof Carbon) {
                return $value->format('Y-m-d');
            }

            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        };

        return [
            'id' => $v->id,
            'slug' => $v->slug,
            'dmr_fact_vehicle_id' => $v->dmr_fact_vehicle_id,
            'title' => $v->title,
            'registration' => $v->registration,
            'vin' => $v->vin,
            'price' => $v->price,
            'description' => $v->description,
            'servicebog' => $v->servicebog,
            'seller_phone' => $v->seller_phone,
            'annual_tax' => $v->annual_tax,
            'internal_cost_price' => $attrs['internal_cost_price'] ?? null,
            'fuel_consumption_wltp' => $v->fuel_consumption_wltp,
            'fuel_consumption_nedc' => $v->fuel_consumption_nedc,
            'production_date' => $date($v->production_date),
            'cover_image_index' => $v->cover_image_index,
            'engine_type' => $v->engine_type,
            'views_count' => $v->views_count,

            'address' => $v->address,
            'postcode' => $v->postcode,
            'seller_address' => $v->address,
            'seller_postcode' => $v->postcode,

            'km_driven' => $v->km_driven,
            'km_per_liter' => $v->km_per_liter,
            'co2_emission' => $v->co2_emission,
            'electrical_consumption' => $v->electrical_consumption,
            'engine_power_kw' => $v->engine_power_kw,
            'engine_power_hp' => $v->engine_power_hp,
            'engine_size_cc' => $v->engine_size_cc,
            'engine_displacement_litres' => $v->engine_displacement_litres,
            'calculated_ownership_tax' => $v->calculated_ownership_tax,
            'towing_weight' => $v->towing_weight,
            'charging_type' => $v->charging_type,
            'max_speed' => $v->max_speed,
            'door_count' => $v->door_count,
            'seats_min' => $v->seats_min,
            'seats_max' => $v->seats_max,
            'axle_count' => $v->axle_count,
            'gear_count' => $v->gear_count,
            'ncap_test' => $v->ncap_test,
            'particle_filter' => $v->particle_filter,
            'maximum_weight_kg' => $v->maximum_weight_kg,
            'nox_emission' => $v->nox_emission,
            'registration_status' => $v->registration_status,
            'last_registration_change' => $date($v->last_registration_change),

            'first_registration_date' => $date($v->first_registration_date),
            'first_registration_year' => $v->first_registration_year,
            'last_inspection_date' => $date($v->last_inspection_date),

            'model_year' => $modelYear,
            'model_year_name' => $modelYearDisplay,
            'model_year_display' => $modelYearDisplay,

            'is_import' => $v->is_import,
            'is_factory_new' => $v->is_factory_new,

            'brand_name' => $v->brand?->name,
            'model_name' => $v->model?->name,
            'variant_name' => $v->variant?->name,
            'fuel_type_name' => $v->fuelType?->name,
            'colour_name' => $v->colour?->name,
            'body_type_name' => $v->bodyType?->name,
            'use_name' => $v->vehicleUse?->name,
            'emission_norm_name' => $v->emissionNorm?->name,
            'measurement_norm_name' => $v->measurementNorm?->name,
            'gear_type_name' => $v->gearType?->name,
            'transmission_name' => $v->gearType?->name,
            'condition_name' => $v->condition?->name,
            'vehicle_list_status_name' => $v->vehicleListStatus?->name,
            'status' => $v->vehicleListStatus?->name !== null && $v->vehicleListStatus->name !== ''
                ? strtolower($v->vehicleListStatus->name)
                : null,
            'listing_type_name' => $v->listingType?->name,
            'sales_type_name' => $v->salesType?->name,
            'price_type_name' => $v->priceType?->name,
            'category_name' => $v->category?->name,

            'leasing_enabled' => (bool) ($attrs['leasing_enabled'] ?? false),
            'leasing_type' => $attrs['leasing_type'] ?? null,
            'leasing_customer_type' => $attrs['leasing_customer_type'] ?? null,
            'leasing_first_payment' => $attrs['leasing_first_payment'] ?? null,
            'leasing_residual_value' => $attrs['leasing_residual_value'] ?? null,
            'leasing_duration' => $attrs['leasing_duration'] ?? null,
            'leasing_annual_mileage' => $attrs['leasing_annual_mileage'] ?? null,
            'leasing_total_cost' => $attrs['leasing_total_cost'] ?? null,

            'list_status_id' => $v->list_status_id,
            'brand_id' => $v->brand_id,
            'model_id' => $v->model_id,
            'variant_id' => $v->variant_id,
            'body_type_id' => $v->body_type_id,
            'vehicle_use_id' => $v->vehicle_use_id,
            'listing_type_id' => $v->listing_type_id,
            'sales_type_id' => $v->sales_type_id,
            'price_type_id' => $v->price_type_id,
            'category_id' => $v->category_id,
            'condition_id' => $v->condition_id,
            'fuel_type_id' => $v->fuel_type_id,
            'gear_type_id' => $v->gear_type_id,
            'colour_id' => $v->colour_id,
            'emission_norm_id' => $v->emission_norm_id,

            'equipment' => $equipment,
            'specifications' => $specifications,
            'spec_definitions' => $specDefinitions,

            'dmr' => [
                'extra_equipment' => $extraEquipmentText,
                'technical_total_weight_kg' => $technicalFromDmr,
                'registration_status_name' => $dmr?->registrationStatus?->name,
            ],

            'technical_total_weight_kg' => $v->maximum_weight_kg ?? $technicalFromDmr,

            'ownership_tax' => $v->calculated_ownership_tax,

            'battery_capacity' => $attrs['battery_capacity'] ?? null,
            'range_km' => $attrs['range_km'] ?? null,
        ];
    }
}
