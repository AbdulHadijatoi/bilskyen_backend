<?php

namespace Tests\Unit;

use App\Models\MarketplaceCity;
use App\Services\SeoService;
use Mockery;
use Tests\TestCase;

class CitySeoRobotsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function seo(): SeoService
    {
        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->andReturn(null);

        return $seo;
    }

    public function test_below_threshold_city_cars_are_noindex(): void
    {
        $city = new MarketplaceCity([
            'name' => 'København',
            'slug' => 'koebenhavn',
            'is_active' => true,
            'published_vehicle_count' => 0,
            'dealer_count' => 0,
        ]);

        $resolved = $this->seo()->resolveForCityCars($city);

        $this->assertFalse($city->isCarsIndexable());
        $this->assertSame('noindex, follow', $resolved['robots']);
    }

    public function test_indexable_city_cars_are_index_follow(): void
    {
        $city = new MarketplaceCity([
            'name' => 'Horsens',
            'slug' => 'horsens',
            'is_active' => true,
            'published_vehicle_count' => MarketplaceCity::MIN_VEHICLES_FOR_INDEX,
            'dealer_count' => 2,
        ]);

        $resolved = $this->seo()->resolveForCityCars($city);

        $this->assertTrue($city->isCarsIndexable());
        $this->assertSame('index, follow', $resolved['robots']);
    }

    public function test_below_threshold_city_dealers_are_noindex(): void
    {
        $city = new MarketplaceCity([
            'name' => 'Aarhus',
            'slug' => 'aarhus',
            'is_active' => true,
            'published_vehicle_count' => 10,
            'dealer_count' => 0,
        ]);

        $resolved = $this->seo()->resolveForCityDealers($city);

        $this->assertFalse($city->isDealersIndexable());
        $this->assertSame('noindex, follow', $resolved['robots']);
    }
}
