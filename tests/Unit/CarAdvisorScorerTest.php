<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\CarAdvisorScorer;
use Tests\TestCase;

class CarAdvisorScorerTest extends TestCase
{
    private CarAdvisorScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new CarAdvisorScorer;
    }

    public function test_over_budget_scores_lower_than_in_budget(): void
    {
        $profile = [
            'budget_max' => 150000,
            'use_case' => 'city',
            'needs' => ['city'],
            'priorities' => ['budget'],
        ];

        $inBudget = $this->makeVehicle([
            'price' => 140000,
            'model_year' => 2021,
            'km_driven' => 40000,
            'seats_max' => 5,
            'calculated_ownership_tax' => 3000,
            'engine_power_hp' => 110,
            'title' => 'City Hatch',
        ], 'Hatchback', 'Petrol');

        $overBudget = $this->makeVehicle([
            'price' => 220000,
            'model_year' => 2021,
            'km_driven' => 40000,
            'seats_max' => 5,
            'calculated_ownership_tax' => 3000,
            'engine_power_hp' => 110,
            'title' => 'Expensive Hatch',
        ], 'Hatchback', 'Petrol');

        $in = $this->scorer->score($inBudget, $profile);
        $over = $this->scorer->score($overBudget, $profile);

        $this->assertGreaterThan($over['score'], $in['score']);
        $this->assertNotEmpty($over['tradeoffs']);
    }

    public function test_family_space_prefers_estate_seats(): void
    {
        $profile = [
            'budget_max' => 200000,
            'use_case' => 'family',
            'needs' => ['stroller', 'space', 'family'],
            'priorities' => ['space'],
        ];

        $estate = $this->makeVehicle([
            'price' => 180000,
            'model_year' => 2019,
            'km_driven' => 80000,
            'seats_max' => 5,
            'calculated_ownership_tax' => 4500,
            'engine_power_hp' => 150,
            'title' => 'Family Estate',
        ], 'Estate', 'Diesel');

        $coupe = $this->makeVehicle([
            'price' => 180000,
            'model_year' => 2019,
            'km_driven' => 80000,
            'seats_max' => 2,
            'calculated_ownership_tax' => 4500,
            'engine_power_hp' => 200,
            'title' => 'Sport Coupe',
        ], 'Coupe', 'Petrol');

        $estateScore = $this->scorer->score($estate, $profile);
        $coupeScore = $this->scorer->score($coupe, $profile);

        $this->assertGreaterThan($coupeScore['score'], $estateScore['score']);
        $this->assertGreaterThanOrEqual(70, $estateScore['components']['space']);
        $this->assertLessThan(50, $coupeScore['components']['space']);
    }

    public function test_low_tax_priority_penalizes_high_ownership_tax(): void
    {
        $profile = [
            'budget_max' => 200000,
            'use_case' => 'mixed',
            'needs' => ['low_tax', 'low_ownership_cost'],
            'priorities' => ['ownership tax'],
        ];

        $cheapTax = $this->makeVehicle([
            'price' => 150000,
            'model_year' => 2020,
            'km_driven' => 50000,
            'seats_max' => 5,
            'calculated_ownership_tax' => 1800,
            'engine_power_hp' => 100,
            'title' => 'Efficient Car',
        ], 'Hatchback', 'Petrol');

        $expensiveTax = $this->makeVehicle([
            'price' => 150000,
            'model_year' => 2020,
            'km_driven' => 50000,
            'seats_max' => 5,
            'calculated_ownership_tax' => 18000,
            'engine_power_hp' => 100,
            'title' => 'Tax Heavy Car',
        ], 'Hatchback', 'Petrol');

        $low = $this->scorer->score($cheapTax, $profile);
        $high = $this->scorer->score($expensiveTax, $profile);

        $this->assertGreaterThan($high['components']['ownership'], $low['components']['ownership']);
        $this->assertGreaterThan($high['score'], $low['score']);
    }

    public function test_fair_price_market_boosts_score(): void
    {
        $profile = [
            'budget_max' => 200000,
            'use_case' => 'mixed',
            'needs' => [],
            'priorities' => ['value'],
        ];

        $vehicle = $this->makeVehicle([
            'price' => 160000,
            'model_year' => 2020,
            'km_driven' => 60000,
            'seats_max' => 5,
            'calculated_ownership_tax' => 4000,
            'engine_power_hp' => 120,
            'title' => 'Market Car',
        ], 'Hatchback', 'Petrol');

        $below = $this->scorer->score($vehicle, $profile, [
            'label' => 'below_market',
            'median_price' => 180000,
            'diff_percent' => -11.1,
        ]);
        $above = $this->scorer->score($vehicle, $profile, [
            'label' => 'above_market',
            'median_price' => 140000,
            'diff_percent' => 14.3,
        ]);

        $this->assertGreaterThan($above['components']['market'], $below['components']['market']);
        $this->assertGreaterThan($above['score'], $below['score']);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function makeVehicle(array $attrs, string $body, string $fuel): Vehicle
    {
        $vehicle = new Vehicle($attrs);
        $vehicle->setRelation('bodyType', (object) ['name' => $body]);
        $vehicle->setRelation('fuelType', (object) ['name' => $fuel]);
        $vehicle->setRelation('gearType', (object) ['name' => 'Manual']);
        $vehicle->setRelation('images', collect());

        return $vehicle;
    }
}
