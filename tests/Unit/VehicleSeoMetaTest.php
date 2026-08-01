<?php

namespace Tests\Unit;

use App\Models\Dealer;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SeoService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class VehicleSeoMetaTest extends TestCase
{
    private SeoService $seo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seo = app(SeoService::class);
        Cache::flush();
    }

    public function test_title_includes_brand_model_year_price_and_site(): void
    {
        $title = $this->seo->buildVehiclePageTitle('VW', 'ID.7 Tourer Pro Style', '', '2026', '439.799');

        $this->assertSame('VW ID.7 Tourer Pro Style – 2026 | 439.799 kr | Bilskyen', $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
    }

    public function test_title_drops_price_then_variant_when_too_long(): void
    {
        // Full title with price exceeds ~60; without price the variant still fits.
        $withAll = $this->seo->buildVehiclePageTitle(
            'Audi',
            'SQ7',
            'TDI quattro Tiptronic',
            '2024',
            '1.149.900'
        );
        $this->assertLessThanOrEqual(60, mb_strlen($withAll));
        $this->assertStringContainsString('TDI', $withAll);
        $this->assertStringNotContainsString('1.149.900', $withAll);

        // Even without price, a very long variant forces dropping the variant.
        $stillLong = $this->seo->buildVehiclePageTitle(
            'Mercedes-Benz',
            'EQS SUV',
            'AMG Line Premium Plus Night Edition Long Wheelbase Extreme',
            '2025',
            '1.299.000'
        );
        $this->assertLessThanOrEqual(60, mb_strlen($stillLong));
        $this->assertStringContainsString('Bilskyen', $stillLong);
        $this->assertStringNotContainsString('Wheelbase Extreme', $stillLong);
    }

    public function test_description_skips_missing_price_and_mileage(): void
    {
        $desc = $this->seo->buildVehicleMetaDescription(
            'VW',
            'ID.7 Tourer Pro Style',
            '',
            'Au2Vest',
            '2026',
            null,
            null
        );

        $this->assertStringContainsString('VW ID.7 Tourer Pro Style til salg hos Au2Vest', $desc);
        $this->assertStringContainsString('2026', $desc);
        $this->assertStringNotContainsString(' km', $desc);
        $this->assertStringNotContainsString(' kr', $desc);
        $this->assertLessThanOrEqual(160, mb_strlen($desc));
    }

    public function test_description_includes_price_mileage_and_dealer(): void
    {
        $desc = $this->seo->buildVehicleMetaDescription(
            'VW',
            'ID.7 Tourer Pro Style',
            '',
            'Au2Vest',
            '2026',
            '100',
            '439.799'
        );

        $this->assertStringContainsString('Au2Vest', $desc);
        $this->assertStringContainsString('100 km', $desc);
        $this->assertStringContainsString('439.799 kr', $desc);
        $this->assertLessThanOrEqual(160, mb_strlen($desc));
    }

    public function test_keywords_include_brand_city(): void
    {
        $keywords = $this->seo->buildVehicleMetaKeywords('VW', 'ID.7', '2026', 'Hvidovre');

        $this->assertStringContainsString('VW ID.7', $keywords);
        $this->assertStringContainsString('brugt VW', $keywords);
        $this->assertStringContainsString('VW ID.7 2026', $keywords);
        $this->assertStringContainsString('brugt bil Hvidovre', $keywords);
    }

    public function test_resolve_for_vehicle_builds_schema_and_danish_price(): void
    {
        $owner = new User(['name' => 'Au2Vest']);
        $dealer = new Dealer(['city' => 'Hvidovre', 'slug' => 'au2vest']);
        $dealer->setRelation('owner', $owner);

        $vehicle = new Vehicle([
            'slug' => 'vw-id7-style-tourer-5d',
            'title' => 'Should not win as title',
            'price' => 439799,
            'km_driven' => 100,
            'model_year' => 2026,
        ]);
        $vehicle->setRelation('dealer', $dealer);
        $vehicle->setRelation('images', collect());
        $vehicle->setRelation('brand', (object) ['name' => 'VW']);
        // Accessors use brand relation or brand_id — stub brand_name via relation:
        // brand_name accessor checks relationLoaded('brand') && brand->name
        $brand = Mockery::mock();
        $brand->name = 'VW';
        $model = Mockery::mock();
        $model->name = 'ID.7 Tourer Pro Style';
        $variant = Mockery::mock();
        $variant->name = '';
        $vehicle->setRelation('brand', $brand);
        $vehicle->setRelation('model', $model);
        $vehicle->setRelation('variant', $variant);

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->andReturn(null);

        $resolved = $seo->resolveForVehicle($vehicle);

        $this->assertSame('VW ID.7 Tourer Pro Style – 2026 | 439.799 kr | Bilskyen', $resolved['meta_title']);
        $this->assertStringContainsString('Au2Vest', $resolved['meta_description']);
        $this->assertStringContainsString('439.799 kr', $resolved['meta_description']);
        $this->assertSame('index, follow', $resolved['robots']);
        $this->assertStringEndsWith('/biler/vw-id7-style-tourer-5d', $resolved['canonical_url']);
        $this->assertSame('Vehicle', $resolved['schema_type']);
        $this->assertIsArray($resolved['schema_json']);
        $this->assertSame('Vehicle', $resolved['schema_json']['@type']);
        $this->assertSame(439799.0, $resolved['schema_json']['offers']['price']);
    }
}
