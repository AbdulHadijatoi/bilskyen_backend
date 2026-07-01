<?php

namespace Tests\Unit;

use App\Models\Dealer;
use App\Models\Vehicle;
use App\Services\Finance\FinanceCalculatorService;
use App\Services\PlatformSettingService;
use Tests\TestCase;

class FinanceCalculatorR7Test extends TestCase
{
    public function test_monthly_payment_calculation(): void
    {
        $service = app(FinanceCalculatorService::class);
        $result = $service->calculateMonthlyPayment(300000, 30000, 4.9, 60);

        $this->assertGreaterThan(0, $result['monthly_payment']);
        $this->assertSame(270000.0, $result['principal']);
        $this->assertSame(60, $result['term_months']);
    }

    public function test_zero_principal_returns_zero_payment(): void
    {
        $service = app(FinanceCalculatorService::class);
        $result = $service->calculateMonthlyPayment(100000, 100000, 5.0, 48);

        $this->assertEquals(0, $result['monthly_payment']);
    }

    public function test_platform_disabled_hides_calculator_for_dealer(): void
    {
        $this->mock(PlatformSettingService::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('finance', 'calculator_enabled', true)
                ->andReturn('false');
        });

        $dealer = new Dealer(['finance_calculator_enabled' => true]);
        $service = app(FinanceCalculatorService::class);

        $this->assertFalse($service->isCalculatorEnabledForDealer($dealer));
    }

    public function test_dealer_can_disable_when_platform_enabled(): void
    {
        $this->mock(PlatformSettingService::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('finance', 'calculator_enabled', true)
                ->andReturn('true');
        });

        $dealer = new Dealer(['finance_calculator_enabled' => false]);
        $service = app(FinanceCalculatorService::class);

        $this->assertFalse($service->isCalculatorEnabledForDealer($dealer));
    }

    public function test_dealer_inherits_platform_default_when_null(): void
    {
        $this->mock(PlatformSettingService::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('finance', 'calculator_enabled', true)
                ->andReturn('true');
        });

        $dealer = new Dealer(['finance_calculator_enabled' => null]);
        $service = app(FinanceCalculatorService::class);

        $this->assertTrue($service->isCalculatorEnabledForDealer($dealer));
    }

    public function test_should_not_show_calculator_for_zero_price_vehicle(): void
    {
        $this->mock(PlatformSettingService::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('finance', 'calculator_enabled', true)
                ->andReturn('true');
        });

        $dealer = new Dealer(['finance_calculator_enabled' => true]);
        $vehicle = new Vehicle(['price' => 0]);
        $vehicle->setRelation('dealer', $dealer);

        $service = app(FinanceCalculatorService::class);

        $this->assertFalse($service->shouldShowCalculatorForVehicle($vehicle));
    }

    public function test_should_show_calculator_for_priced_vehicle_when_enabled(): void
    {
        $this->mock(PlatformSettingService::class, function ($mock) {
            $mock->shouldReceive('get')
                ->with('finance', 'calculator_enabled', true)
                ->andReturn('true');
        });

        $dealer = new Dealer(['finance_calculator_enabled' => null]);
        $vehicle = new Vehicle(['price' => 250000]);
        $vehicle->setRelation('dealer', $dealer);

        $service = app(FinanceCalculatorService::class);

        $this->assertTrue($service->shouldShowCalculatorForVehicle($vehicle));
    }
}
