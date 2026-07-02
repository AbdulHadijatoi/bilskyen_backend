<?php

namespace App\ViewModels;

use App\Models\Vehicle;
use Illuminate\Support\Carbon;

/**
 * Read-only view for Blade templates that reference {@see $vehicle->details}.
 * Prefers columns on {@see Vehicle}; falls back to linked DMR fact data where useful.
 */
class VehicleDetailPresenter
{
    public function __construct(private Vehicle $vehicle) {}

    public function __isset(string $name): bool
    {
        $v = $this->__get($name);

        return $v !== null;
    }

    public function __get(string $name): mixed
    {
        $v = $this->vehicle;
        $d = $v->dmrFactVehicle;

        return match ($name) {
            'description' => $v->description,
            'servicebog' => $v->servicebog,
            'condition_id' => $v->condition_id,
            'seller_phone' => $v->seller_phone ?? $v->user?->phone ?? $v->dealer?->owner?->phone,
            'annual_tax' => $v->annual_tax,
            'type_name_resolved' => $d?->vehicleUse?->name,
            'use_name' => $v->vehicleUse?->name ?? $d?->vehicleUse?->name,
            'price_type_name' => $v->relationLoaded('priceType')
                ? $v->priceType?->name
                : $v->priceType()->value('name'),
            'condition_name' => $v->relationLoaded('condition')
                ? $v->condition?->name
                : $v->condition()->value('name'),
            'sales_type_name' => $v->relationLoaded('salesType')
                ? $v->salesType?->name
                : $v->salesType()->value('name'),
            'salesType' => $v->relationLoaded('salesType') ? $v->salesType : $v->salesType()->first(),
            'transmission_name' => $v->gear_type_name,
            'vin_location' => null,
            'color_name' => $v->colour?->name ?? $d?->colour?->name,
            'body_type_name' => $v->bodyType?->name ?? $d?->bodyType?->name,
            'variant' => $v->variant ? (object) ['name' => $v->variant->name] : ($d?->variant ? (object) ['name' => $d->variant->name] : null),
            'variant_id' => $v->variant_id,
            'total_weight' => $v->maximum_weight_kg ?? $d?->teknisk_total_vaegt,
            'vehicle_weight' => null,
            'technical_total_weight' => $v->maximum_weight_kg ?? $d?->teknisk_total_vaegt,
            'minimum_weight' => null,
            'gross_combination_weight' => null,
            'towing_weight_brakes' => null,
            'engine_displacement' => $this->engineDisplacementCc($v, $d),
            'engine_code' => null,
            'engine_cylinders' => null,
            'doors' => $v->door_count ?? $d?->antal_doere,
            'minimum_seats' => $v->seats_min ?? $d?->siddepladser_minimum,
            'maximum_seats' => $v->seats_max ?? $d?->siddepladser_maksimum,
            'top_speed' => $v->max_speed ?? $d?->maksimum_hastighed,
            'airbags' => null,
            'ncap_five' => $v->ncap_test ?? $d?->ncap_test,
            'integrated_child_seats' => null,
            'seat_belt_alarms' => null,
            'euronom' => $v->emissionNorm ? (object) ['name' => $v->emissionNorm->name] : ($d?->emissionNorm ? (object) ['name' => $d->emissionNorm->name] : null),
            'euronom_id' => $v->emission_norm_id,
            'wheels' => null,
            'axles' => $v->axle_count ?? $d?->aksel_antal,
            'drive_axles' => null,
            'wheelbase' => null,
            'category' => null,
            'extra_equipment' => $d?->oevrigt_udstyr,
            'registration_status' => $v->registration_status ?? $d?->registrationStatus?->name,
            'registration_status_updated_date' => $this->dateOrNull($d?->registrering_status_dato),
            'expire_date' => null,
            'status_updated_date' => null,
            'last_inspection_date' => $this->dateOrNull($v->last_inspection_date),
            'last_inspection_result' => $v->last_inspection_result,
            'last_inspection_odometer' => $v->last_inspection_odometer,
            'leasing_period_start' => null,
            'leasing_period_end' => null,
            'co2_emissions' => $this->co2EmissionsDisplay($v, $d),
            'fuel_consumption_wltp' => $v->fuel_consumption_wltp,
            'fuel_consumption_nedc' => $v->fuel_consumption_nedc,
            'is_import' => $v->is_import,
            'is_factory_new' => $v->is_factory_new,
            'views_count' => $v->views_count,
            'type_id' => null,
            'type_name' => null,
            'use_id' => $v->vehicle_use_id,
            'color_id' => $v->colour_id,
            'body_type_id' => $v->body_type_id,
            'transmission_id' => null,
            'price_type_id' => $v->price_type_id,
            'sales_type_id' => $v->sales_type_id,
            default => null,
        };
    }

    private function co2EmissionsDisplay(Vehicle $v, ?object $d): ?int
    {
        if ($v->co2_emission !== null) {
            return (int) round((float) $v->co2_emission);
        }
        if ($d?->emission_co !== null) {
            return (int) round((float) $d->emission_co);
        }

        return null;
    }

    private function engineDisplacementCc(Vehicle $v, ?object $d): ?int
    {
        if ($v->engine_displacement_litres !== null) {
            return (int) round((float) $v->engine_displacement_litres * 1000);
        }
        if ($d === null || $d->motor_slag_volumen === null) {
            return null;
        }

        return (int) round((float) $d->motor_slag_volumen * 1000);
    }

    private function dateOrNull(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
