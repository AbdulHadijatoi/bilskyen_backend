<?php

namespace App\Services\VehicleImport;

/**
 * Excel column headers (Danish) and mapping to vehicles table / relations.
 */
class VehicleImportColumnDefinitions
{
    /**
     * Ordered import columns: canonical internal key => Danish spreadsheet header.
     *
     * @var array<string, string>
     */
    public const COLUMN_HEADERS_DA = [
        'registration' => 'Registrering',
        'vin' => 'Stelnummer',
        'title' => 'Titel',
        'price' => 'Pris',
        'sales_type' => 'Salgstype',
        'brand' => 'Mærke',
        'model' => 'Model',
        'variant' => 'Variant',
        'fuel_type' => 'Brændstof',
        'gear_type' => 'Geartype',
        'body_type' => 'Karosseri',
        'colour' => 'Farve',
        'emission_norm' => 'Emissionsnorm',
        'vehicle_use' => 'Anvendelse',
        'condition' => 'Stand',
        'listing_type' => 'Annoncetype',
        'price_type' => 'Pristype',
        'category' => 'Kategori',
        'km_driven' => 'Kilometer',
        'description' => 'Beskrivelse',
        'seller_phone' => 'Telefon',
        'model_year' => 'Modelår',
        'first_registration_date' => 'Første registrering',
        'production_date' => 'Produktionsdato',
        'last_inspection_date' => 'Sidste syn',
        'co2_emission' => 'CO2-udledning',
        'engine_power_kw' => 'Motoreffekt (kW)',
        'engine_power_hp' => 'Motoreffekt (hk)',
        'engine_displacement_litres' => 'Motorstørrelse (L)',
        'door_count' => 'Antal døre',
        'seats_min' => 'Min. sæder',
        'seats_max' => 'Max. sæder',
        'equipment' => 'Udstyr',
        'image_urls' => 'Billeder',
    ];

    /** @var list<string> */
    public const TEMPLATE_HEADERS = [
        'Registrering',
        'Stelnummer',
        'Titel',
        'Pris',
        'Salgstype',
        'Mærke',
        'Model',
        'Variant',
        'Brændstof',
        'Geartype',
        'Karosseri',
        'Farve',
        'Emissionsnorm',
        'Anvendelse',
        'Stand',
        'Annoncetype',
        'Pristype',
        'Kategori',
        'Kilometer',
        'Beskrivelse',
        'Telefon',
        'Modelår',
        'Første registrering',
        'Produktionsdato',
        'Sidste syn',
        'CO2-udledning',
        'Motoreffekt (kW)',
        'Motoreffekt (hk)',
        'Motorstørrelse (L)',
        'Antal døre',
        'Min. sæder',
        'Max. sæder',
        'Udstyr',
        'Billeder',
    ];

    /** @var array<string, string> Example values keyed by Danish header */
    public const SAMPLE_ROW = [
        'Registrering' => 'AB12345',
        'Stelnummer' => '',
        'Titel' => 'Volvo XC60',
        'Pris' => '249900',
        'Salgstype' => 'Kontantpris',
        'Mærke' => 'Volvo',
        'Model' => 'XC60',
        'Variant' => '',
        'Brændstof' => 'Benzin',
        'Geartype' => 'Automatisk',
        'Karosseri' => '',
        'Farve' => '',
        'Emissionsnorm' => '',
        'Anvendelse' => '',
        'Stand' => '',
        'Annoncetype' => '',
        'Pristype' => '',
        'Kategori' => '',
        'Kilometer' => '85000',
        'Beskrivelse' => '',
        'Telefon' => '',
        'Modelår' => '',
        'Første registrering' => '',
        'Produktionsdato' => '',
        'Sidste syn' => '',
        'CO2-udledning' => '',
        'Motoreffekt (kW)' => '',
        'Motoreffekt (hk)' => '',
        'Motorstørrelse (L)' => '',
        'Antal døre' => '',
        'Min. sæder' => '',
        'Max. sæder' => '',
        'Udstyr' => '',
        'Billeder' => 'https://example.com/billede1.jpg;https://example.com/billede2.jpg',
    ];

    /**
     * Normalized spreadsheet header => canonical column key (Danish + legacy English).
     *
     * @var array<string, string>
     */
    public const HEADER_ALIASES = [
        'registrering' => 'registration',
        'stelnummer' => 'vin',
        'vin' => 'vin',
        'titel' => 'title',
        'title' => 'title',
        'pris' => 'price',
        'price' => 'price',
        'salgstype' => 'sales_type',
        'sales_type' => 'sales_type',
        'mærke' => 'brand',
        'maerke' => 'brand',
        'brand' => 'brand',
        'model' => 'model',
        'variant' => 'variant',
        'brændstof' => 'fuel_type',
        'braendstof' => 'fuel_type',
        'fuel_type' => 'fuel_type',
        'geartype' => 'gear_type',
        'gear_type' => 'gear_type',
        'transmission' => 'gear_type',
        'karosseri' => 'body_type',
        'body_type' => 'body_type',
        'farve' => 'colour',
        'colour' => 'colour',
        'color' => 'colour',
        'emissionsnorm' => 'emission_norm',
        'emission_norm' => 'emission_norm',
        'euronorm' => 'emission_norm',
        'anvendelse' => 'vehicle_use',
        'vehicle_use' => 'vehicle_use',
        'use' => 'vehicle_use',
        'stand' => 'condition',
        'condition' => 'condition',
        'annoncetype' => 'listing_type',
        'listing_type' => 'listing_type',
        'pristype' => 'price_type',
        'price_type' => 'price_type',
        'kategori' => 'category',
        'category' => 'category',
        'kilometer' => 'km_driven',
        'km_driven' => 'km_driven',
        'beskrivelse' => 'description',
        'description' => 'description',
        'adresse' => 'address',
        'address' => 'address',
        'postnummer' => 'postcode',
        'postcode' => 'postcode',
        'telefon' => 'seller_phone',
        'seller_phone' => 'seller_phone',
        'modelår' => 'model_year',
        'modelar' => 'model_year',
        'model_year' => 'model_year',
        'første_registrering' => 'first_registration_date',
        'foerste_registrering' => 'first_registration_date',
        'first_registration_date' => 'first_registration_date',
        'produktionsdato' => 'production_date',
        'production_date' => 'production_date',
        'sidste_syn' => 'last_inspection_date',
        'last_inspection_date' => 'last_inspection_date',
        'co2_udledning' => 'co2_emission',
        'co2_emission' => 'co2_emission',
        'motoreffekt_(kw)' => 'engine_power_kw',
        'engine_power_kw' => 'engine_power_kw',
        'motoreffekt_(hk)' => 'engine_power_hp',
        'engine_power_hp' => 'engine_power_hp',
        'motorstørrelse_(l)' => 'engine_displacement_litres',
        'motorstoerrelse_(l)' => 'engine_displacement_litres',
        'engine_displacement_litres' => 'engine_displacement_litres',
        'antal_døre' => 'door_count',
        'antal_doere' => 'door_count',
        'door_count' => 'door_count',
        'min._sæder' => 'seats_min',
        'min._saeder' => 'seats_min',
        'seats_min' => 'seats_min',
        'max._sæder' => 'seats_max',
        'max._saeder' => 'seats_max',
        'seats_max' => 'seats_max',
        'udstyr' => 'equipment',
        'equipment' => 'equipment',
        'billeder' => 'image_urls',
        'billede_urls' => 'image_urls',
        'image_urls' => 'image_urls',
        'images' => 'image_urls',
        'registration' => 'registration',
    ];

    public static function resolveCanonicalKey(string $normalizedHeader): string
    {
        if ($normalizedHeader === '') {
            return '';
        }

        return self::HEADER_ALIASES[$normalizedHeader] ?? $normalizedHeader;
    }

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

    public const MAX_IMAGE_URLS_PER_ROW = 10;
}
