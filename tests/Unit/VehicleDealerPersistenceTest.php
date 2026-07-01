<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\VehicleService;
use ReflectionMethod;
use Tests\TestCase;

class VehicleDealerPersistenceTest extends TestCase
{
    public function test_vehicle_fillable_includes_supported_vehicle_columns(): void
    {
        $fillable = (new Vehicle)->getFillable();
        foreach ([
            'seller_phone',
            'price',
            'annual_tax',
            'fuel_consumption_wltp',
            'fuel_consumption_nedc',
            'views_count',
        ] as $column) {
            $this->assertContains($column, $fillable, 'Missing fillable: '.$column);
        }
    }

    public function test_vehicle_service_derives_engine_power_hp_from_kw(): void
    {
        $service = $this->app->make(VehicleService::class);
        $method = new ReflectionMethod(VehicleService::class, 'deriveEnginePowerHpFromKw');
        $method->setAccessible(true);

        $data = $method->invoke($service, ['engine_power_kw' => 100]);
        $this->assertSame(136.0, $data['engine_power_hp']);

        $data2 = $method->invoke($service, ['engine_power_kw' => 100, 'engine_power_hp' => 200]);
        $this->assertSame(200, $data2['engine_power_hp']);
    }

    public function test_vehicle_service_normalizes_legacy_keys(): void
    {
        $service = $this->app->make(VehicleService::class);
        $method = new ReflectionMethod(VehicleService::class, 'normalizeIncomingVehiclePayload');
        $method->setAccessible(true);

        $data = $method->invoke($service, [
            'vehicle_list_status_id' => 2,
            'use_id' => 5,
            'co2_emissions' => 120,
            'engine_power' => 88,
        ]);
        $this->assertSame(2, $data['list_status_id']);
        $this->assertSame(5, $data['vehicle_use_id']);
        $this->assertSame(120, $data['co2_emission']);
        $this->assertSame(88, $data['engine_power_kw']);
        $this->assertArrayNotHasKey('vehicle_list_status_id', $data);
        $this->assertArrayNotHasKey('use_id', $data);
        $this->assertArrayNotHasKey('co2_emissions', $data);
        $this->assertArrayNotHasKey('engine_power', $data);
    }

    public function test_normalize_then_derives_hp_matches_dealer_panel_flow(): void
    {
        $service = $this->app->make(VehicleService::class);
        $normalize = new ReflectionMethod(VehicleService::class, 'normalizeIncomingVehiclePayload');
        $normalize->setAccessible(true);
        $derive = new ReflectionMethod(VehicleService::class, 'deriveEnginePowerHpFromKw');
        $derive->setAccessible(true);

        $data = $normalize->invoke($service, [
            'vehicle_list_status_id' => 1,
            'engine_power' => 100,
        ]);
        $data = $derive->invoke($service, $data);

        $this->assertSame(1, $data['list_status_id']);
        $this->assertSame(100.0, (float) $data['engine_power_kw']);
        $this->assertSame(136.0, $data['engine_power_hp']);
    }

    public function test_hydrate_first_registration_year_from_date_on_create_payload(): void
    {
        $service = $this->app->make(VehicleService::class);
        $method = new ReflectionMethod(VehicleService::class, 'hydrateFirstRegistrationYearFromDate');
        $method->setAccessible(true);

        $data = ['first_registration_date' => '2019-06-15'];
        $method->invokeArgs($service, [&$data, null]);
        $this->assertSame(2019, $data['first_registration_year']);
    }

    public function test_hydrate_first_registration_year_skips_when_year_explicit(): void
    {
        $service = $this->app->make(VehicleService::class);
        $method = new ReflectionMethod(VehicleService::class, 'hydrateFirstRegistrationYearFromDate');
        $method->setAccessible(true);

        $data = ['first_registration_date' => '2019-06-15', 'first_registration_year' => 2020];
        $method->invokeArgs($service, [&$data, null]);
        $this->assertSame(2020, $data['first_registration_year']);
    }

    public function test_hydrate_first_registration_year_uses_existing_vehicle_date_when_payload_omits_date(): void
    {
        $service = $this->app->make(VehicleService::class);
        $method = new ReflectionMethod(VehicleService::class, 'hydrateFirstRegistrationYearFromDate');
        $method->setAccessible(true);

        $vehicle = new Vehicle;
        $vehicle->first_registration_date = '2018-03-01';

        $data = [];
        $method->invokeArgs($service, [&$data, $vehicle]);
        $this->assertSame(2018, $data['first_registration_year']);
    }

    public function test_normalize_maps_fuel_efficiency_and_transmission_id_to_canonical_columns(): void
    {
        $service = $this->app->make(VehicleService::class);
        $method = new ReflectionMethod(VehicleService::class, 'normalizeIncomingVehiclePayload');
        $method->setAccessible(true);

        $data = $method->invoke($service, [
            'fuel_efficiency' => 18.5,
            'transmission_id' => 2,
        ]);
        $this->assertEqualsWithDelta(18.5, (float) $data['km_per_liter'], 0.001);
        $this->assertSame(2, $data['gear_type_id']);
        $this->assertArrayNotHasKey('fuel_efficiency', $data);
        $this->assertArrayNotHasKey('transmission_id', $data);
    }

    public function test_normalize_does_not_overwrite_km_per_liter_when_both_sent(): void
    {
        $service = $this->app->make(VehicleService::class);
        $method = new ReflectionMethod(VehicleService::class, 'normalizeIncomingVehiclePayload');
        $method->setAccessible(true);

        $data = $method->invoke($service, [
            'km_per_liter' => 20,
            'fuel_efficiency' => 18.5,
        ]);
        $this->assertSame(20, $data['km_per_liter']);
    }
}
