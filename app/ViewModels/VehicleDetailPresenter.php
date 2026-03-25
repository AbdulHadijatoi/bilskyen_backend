<?php

namespace App\ViewModels;

use App\Models\Vehicle;
use Illuminate\Support\Carbon;

/**
 * Read-only view of legacy vehicle_details fields backed by Vehicle + DmrFactVehicle.
 * Used by Blade templates that still reference $vehicle->details.
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
            'seller_phone' => $v->user?->phone ?? $v->dealer?->owner?->phone,
            'wholesale_price' => null,
            'annual_tax' => null,
            'internal_cost_price' => null,
            'type_name_resolved' => $d?->vehicleUse?->name,
            'use_name' => $d?->vehicleUse?->name,
            'price_type_name' => null,
            'condition_name' => $v->relationLoaded('condition')
                ? $v->condition?->name
                : $v->condition()->value('name'),
            'sales_type_name' => null,
            'salesType' => null,
            'transmission_name' => $v->gear_type_name,
            'vin_location' => null,
            'color_name' => $d?->colour?->name,
            'body_type_name' => $d?->bodyType?->name,
            'variant' => $d?->variant ? (object) ['name' => $d->variant->name] : null,
            'total_weight' => $d?->teknisk_total_vaegt,
            'vehicle_weight' => null,
            'technical_total_weight' => $d?->teknisk_total_vaegt,
            'minimum_weight' => null,
            'gross_combination_weight' => null,
            'towing_weight_brakes' => null,
            'engine_displacement' => $this->engineDisplacementCc($d),
            'engine_code' => null,
            'engine_cylinders' => null,
            'doors' => $d?->antal_doere,
            'minimum_seats' => $d?->siddepladser_minimum,
            'maximum_seats' => $d?->siddepladser_maksimum,
            'top_speed' => $d?->maksimum_hastighed,
            'airbags' => null,
            'ncap_five' => $d?->ncap_test,
            'integrated_child_seats' => null,
            'seat_belt_alarms' => null,
            'euronom' => $d?->emissionNorm ? (object) ['name' => $d->emissionNorm->name] : null,
            'wheels' => null,
            'axles' => $d?->aksel_antal,
            'drive_axles' => null,
            'wheelbase' => null,
            'category' => null,
            'extra_equipment' => $d?->oevrigt_udstyr,
            'registration_status' => $d?->registrationStatus?->name,
            'registration_status_updated_date' => $this->dateOrNull($d?->registrering_status_dato),
            'expire_date' => null,
            'status_updated_date' => null,
            'last_inspection_date' => null,
            'last_inspection_result' => null,
            'last_inspection_odometer' => null,
            'leasing_period_start' => null,
            'leasing_period_end' => null,
            'co2_emissions' => $d?->emission_co !== null ? (int) round((float) $d->emission_co) : null,
            'fuel_consumption_wltp' => null,
            'fuel_consumption_nedc' => null,
            'is_import' => null,
            'is_factory_new' => null,
            'views_count' => null,
            'type_id', 'type_name', 'use_id', 'color_id', 'body_type_id', 'variant_id', 'transmission_id', 'euronom_id', 'price_type_id', 'sales_type_id' => null,
            default => null,
        };
    }

    private function engineDisplacementCc(?object $d): ?int
    {
        if ($d === null || $d->motor_slag_volumen === null) {
            return null;
        }

        // DMR stores displacement in liters; legacy UI expected cc.
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
