<?php

namespace App\Services\VehicleImport;

/**
 * Excel column headers and mapping to vehicles table / relations.
 */
class VehicleImportColumnDefinitions
{
    /** @var list<string> */
    public const TEMPLATE_HEADERS = [
        'registration',
        'vin',
        'title',
        'price',
        'sales_type',
        'brand',
        'model',
        'variant',
        'fuel_type',
        'gear_type',
        'body_type',
        'colour',
        'emission_norm',
        'vehicle_use',
        'condition',
        'listing_type',
        'price_type',
        'category',
        'km_driven',
        'description',
        'address',
        'postcode',
        'seller_phone',
        'model_year',
        'first_registration_date',
        'production_date',
        'last_inspection_date',
        'co2_emission',
        'engine_power_kw',
        'engine_power_hp',
        'engine_displacement_litres',
        'door_count',
        'seats_min',
        'seats_max',
        'equipment',
    ];

    /** @var array<string, string> Example row for template and UI sample */
    public const SAMPLE_ROW = [
        'registration' => 'AB12345',
        'vin' => '',
        'title' => 'Volvo XC60',
        'price' => '249900',
        'sales_type' => 'Køb',
        'brand' => 'Volvo',
        'model' => 'XC60',
        'variant' => '',
        'fuel_type' => 'Benzin',
        'gear_type' => 'Automatisk',
        'body_type' => '',
        'colour' => '',
        'emission_norm' => '',
        'vehicle_use' => '',
        'condition' => '',
        'listing_type' => '',
        'price_type' => '',
        'category' => '',
        'km_driven' => '85000',
        'description' => '',
        'address' => '',
        'postcode' => '',
        'seller_phone' => '',
        'model_year' => '',
        'first_registration_date' => '',
        'production_date' => '',
        'last_inspection_date' => '',
        'co2_emission' => '',
        'engine_power_kw' => '',
        'engine_power_hp' => '',
        'engine_displacement_litres' => '',
        'door_count' => '',
        'seats_min' => '',
        'seats_max' => '',
        'equipment' => '',
    ];

    /**
     * Excel header => vehicles column (scalar).
     *
     * @var array<string, string>
     */
    public const SCALAR_COLUMNS = [
        'registration' => 'registration',
        'vin' => 'vin',
        'title' => 'title',
        'price' => 'price',
        'description' => 'description',
        'address' => 'address',
        'postcode' => 'postcode',
        'seller_phone' => 'seller_phone',
        'km_driven' => 'km_driven',
        'battery_capacity' => 'battery_capacity',
        'range_km' => 'range_km',
        'charging_type' => 'charging_type',
        'model_year' => 'model_year',
        'km_per_liter' => 'km_per_liter',
        'fuel_consumption_wltp' => 'fuel_consumption_wltp',
        'fuel_consumption_nedc' => 'fuel_consumption_nedc',
        'maximum_weight_kg' => 'maximum_weight_kg',
        'first_registration_date' => 'first_registration_date',
        'first_registration_year' => 'first_registration_year',
        'production_date' => 'production_date',
        'last_inspection_date' => 'last_inspection_date',
        'co2_emission' => 'co2_emission',
        'engine_power_kw' => 'engine_power_kw',
        'engine_power_hp' => 'engine_power_hp',
        'engine_displacement_litres' => 'engine_displacement_litres',
        'engine_type' => 'engine_type',
        'door_count' => 'door_count',
        'seats_min' => 'seats_min',
        'seats_max' => 'seats_max',
        'max_speed' => 'max_speed',
        'axle_count' => 'axle_count',
        'towing_weight' => 'towing_weight',
        'is_import' => 'is_import',
        'is_factory_new' => 'is_factory_new',
        'internal_cost_price' => 'internal_cost_price',
        'annual_tax' => 'annual_tax',
        'servicebog' => 'servicebog',
        'registration_status' => 'registration_status',
        'published_at' => 'published_at',
        'leasing_enabled' => 'leasing_enabled',
        'leasing_type' => 'leasing_type',
        'leasing_customer_type' => 'leasing_customer_type',
        'leasing_first_payment' => 'leasing_first_payment',
        'leasing_residual_value' => 'leasing_residual_value',
        'leasing_duration' => 'leasing_duration',
        'leasing_annual_mileage' => 'leasing_annual_mileage',
        'leasing_total_cost' => 'leasing_total_cost',
        'dmr_fact_vehicle_id' => 'dmr_fact_vehicle_id',
    ];

    /**
     * Excel header => [db_column, required].
     *
     * @var array<string, array{0: string, 1: bool}>
     */
    public const FK_COLUMNS = [
        'brand' => ['brand_id', true],
        'model' => ['model_id', true],
        'variant' => ['variant_id', false],
        'fuel_type' => ['fuel_type_id', true],
        'body_type' => ['body_type_id', false],
        'colour' => ['colour_id', false],
        'color' => ['colour_id', false],
        'emission_norm' => ['emission_norm_id', false],
        'euronorm' => ['emission_norm_id', false],
        'vehicle_use' => ['vehicle_use_id', false],
        'use' => ['vehicle_use_id', false],
        'gear_type' => ['gear_type_id', false],
        'transmission' => ['gear_type_id', false],
        'condition' => ['condition_id', false],
        'listing_type' => ['listing_type_id', false],
        'sales_type' => ['sales_type_id', true],
        'price_type' => ['price_type_id', false],
        'category' => ['category_id', false],
        'measurement_norm' => ['measurement_norm_id', false],
    ];

    public const MAX_ROWS = 200;
}
