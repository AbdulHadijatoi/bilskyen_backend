<?php

namespace Tests\Unit;

use App\Models\Condition;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Services\PlatformSettingService;
use App\Services\Syndication\MetaCatalogFeedUrlService;
use App\Services\Syndication\MetaVehicleCatalogMapper;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class MetaVehicleCatalogMapperTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function test_headers_include_availability_condition_and_twenty_images(): void
    {
        $headers = (new MetaVehicleCatalogMapper)->headers();

        $this->assertContains('availability', $headers);
        $this->assertContains('condition', $headers);
        $this->assertContains('image[0].url', $headers);
        $this->assertContains('image[19].url', $headers);
        $this->assertNotContains('image[20].url', $headers);
        $this->assertSame(20, MetaVehicleCatalogMapper::MAX_IMAGES);
    }

    public function test_row_uses_stable_vehicle_id_price_format_and_https(): void
    {
        $mapper = new MetaVehicleCatalogMapper;
        $vehicle = $this->makeVehicle();
        $vehicle->setRelation('condition', new Condition(['name' => 'Fremragende']));

        $row = $mapper->toRow($vehicle);

        $this->assertSame('1554', $row['vehicle_id']);
        $this->assertSame('479800.00 DKK', $row['price']);
        $this->assertSame('IN_STOCK', $row['availability']);
        $this->assertSame('EXCELLENT', $row['condition']);
        $this->assertSame('Used', $row['state_of_vehicle']);
        $this->assertStringStartsWith('https://', $row['url']);
        $this->assertArrayHasKey('image[19].url', $row);
        $this->assertSame('', $row['image[19].url']);
        $this->assertStringContainsString('Ægte', $row['description']);
    }

    public function test_condition_mapping_and_new_state_of_vehicle(): void
    {
        $mapper = new MetaVehicleCatalogMapper;

        $this->assertSame('GOOD', $mapper->mapCondition(null));
        $this->assertSame('GOOD', $mapper->mapCondition('God'));
        $this->assertSame('FAIR', $mapper->mapCondition('Rimelig'));
        $this->assertSame('POOR', $mapper->mapCondition('Dårlig'));
        $this->assertSame('EXCELLENT', $mapper->mapCondition('Ny'));

        $vehicle = $this->makeVehicle(['km_driven' => 20]);
        $row = $mapper->toRow($vehicle);
        $this->assertSame('New', $row['state_of_vehicle']);
        $this->assertSame('GOOD', $row['condition']);
    }

    public function test_csv_includes_header_and_https_image_urls(): void
    {
        $image = new VehicleImage(['image_path' => 'vehicles/1554/a.jpg', 'sort_order' => 0]);
        $vehicle = $this->makeVehicle();
        $vehicle->setRelation('images', collect([$image]));

        $csv = (new MetaVehicleCatalogMapper)->toCsv(new Collection([$vehicle]));

        $this->assertStringContainsString('"availability"', $csv);
        $this->assertStringContainsString('"condition"', $csv);
        $this->assertStringContainsString('"1554"', $csv);
        $this->assertStringContainsString('https://', $csv);
        $this->assertStringNotContainsString('<html', strtolower($csv));
    }

    public function test_force_https_rewrites_http_urls(): void
    {
        $this->assertSame(
            'https://bilskyen.dk/api/v1/feeds/platform/abc/vehicles.csv',
            MetaVehicleCatalogMapper::forceHttps('http://bilskyen.dk/api/v1/feeds/platform/abc/vehicles.csv')
        );
        $this->assertSame('https://already.secure/x', MetaVehicleCatalogMapper::forceHttps('https://already.secure/x'));
        $this->assertSame('', MetaVehicleCatalogMapper::forceHttps(''));
    }

    public function test_platform_feed_url_is_https(): void
    {
        config(['app.url' => 'http://bilskyen.dk']);
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')->with('marketing', 'meta_catalog_feed_token', '')->andReturn('abcToken');

        $url = (new MetaCatalogFeedUrlService($settings))->platformFeedUrl();

        $this->assertSame('https://bilskyen.dk/api/v1/feeds/platform/abcToken/vehicles.csv', $url);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeVehicle(array $overrides = []): Vehicle
    {
        $vehicle = new Vehicle(array_merge([
            'title' => 'Mercedes GLE',
            'description' => '<p>Ægte dansk bil</p>',
            'price' => 479800,
            'slug' => 'mercedes-gle',
            'km_driven' => 12000,
            'model_year' => 2022,
            'vin' => 'WDC123',
        ], $overrides));
        $vehicle->id = 1554;
        $vehicle->setRelation('images', collect());
        $vehicle->setRelation('brand', null);
        $vehicle->setRelation('model', null);
        $vehicle->setRelation('fuelType', null);
        $vehicle->setRelation('gearType', null);
        $vehicle->setRelation('bodyType', null);
        $vehicle->setRelation('colour', null);
        $vehicle->setRelation('condition', null);
        $vehicle->setRelation('dealer', null);

        return $vehicle;
    }
}
