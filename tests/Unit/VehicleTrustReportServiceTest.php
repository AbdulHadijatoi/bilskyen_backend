<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\VehicleTrustReportService;
use Tests\TestCase;

class VehicleTrustReportServiceTest extends TestCase
{
    public function test_inspection_passed_recognizes_godkendt(): void
    {
        $service = new VehicleTrustReportService;

        $this->assertTrue($service->inspectionPassed('Godkendt'));
        $this->assertFalse($service->inspectionPassed('Udbedring'));
    }

    public function test_build_for_vehicle_includes_registry_flag(): void
    {
        $service = new VehicleTrustReportService;
        $vehicle = new Vehicle([
            'registration' => 'AB12345',
            'price' => 150000,
            'first_registration_date' => '2020-03-01',
            'km_driven' => 23320,
        ]);

        $report = $service->buildForVehicle($vehicle);

        $this->assertTrue($report['has_registry_data']);
        $this->assertArrayHasKey('trust_badge', $report);
        $this->assertSame('AB12345', $report['registry']['registration']);
        $this->assertSame('2020-03-01', $report['registry']['first_registration_date']);
        $this->assertSame(23320.0, $report['registry']['km_driven']);
    }
}
