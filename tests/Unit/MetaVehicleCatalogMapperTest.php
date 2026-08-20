<?php

namespace Tests\Unit;

use App\Models\Condition;
use App\Models\Dealer;
use App\Models\Location;
use App\Models\MarketplaceCity;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Services\PlatformSettingService;
use App\Services\Syndication\MetaCatalogFeedUrlService;
use App\Services\Syndication\MetaVehicleCatalogMapper;
use App\Support\CompanyProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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

    public function test_dealer_row_gets_region_from_marketplace_city(): void
    {
        $city = new MarketplaceCity(['name' => 'Aarhus', 'slug' => 'aarhus', 'region' => 'Midtjylland']);
        $dealer = new Dealer([
            'slug' => 'aarhus-biler',
            'address' => 'Søndergade 12',
            'city' => 'Aarhus',
            'postcode' => '8000',
            'country_code' => 'DK',
        ]);
        $dealer->id = 88;
        $dealer->setRelation('marketplaceCity', $city);

        $vehicle = $this->makeVehicle();
        $vehicle->setRelation('dealer', $dealer);

        $row = (new MetaVehicleCatalogMapper)->toRow($vehicle);

        $this->assertSame('Søndergade 12', $row['address.addr1']);
        $this->assertSame('Aarhus', $row['address.city']);
        $this->assertSame('Midtjylland', $row['address.region']);
        $this->assertSame('8000', $row['address.postal_code']);
        $this->assertNotSame('', $row['address.region']);
    }

    public function test_private_listing_uses_vehicle_address_and_location_region(): void
    {
        $this->ensureLocationsTable();
        Location::query()->create([
            'city' => 'Odense',
            'postcode' => '5000',
            'region' => 'Syddanmark',
            'country_code' => 'DK',
        ]);

        $vehicle = $this->makeVehicle([
            'address' => 'Vestergade 4',
            'postcode' => '5000',
        ]);
        $vehicle->setRelation('dealer', null);

        $row = (new MetaVehicleCatalogMapper)->toRow($vehicle);

        $this->assertSame('Vestergade 4', $row['address.addr1']);
        $this->assertSame('Odense', $row['address.city']);
        $this->assertSame('Syddanmark', $row['address.region']);
        $this->assertSame('5000', $row['address.postal_code']);
    }

    public function test_empty_description_is_synthesized_from_title(): void
    {
        $vehicle = $this->makeVehicle(['description' => '']);
        $row = (new MetaVehicleCatalogMapper)->toRow($vehicle);

        $this->assertStringContainsString('Mercedes GLE', $row['description']);
        $this->assertStringContainsString('2022', $row['description']);
        $this->assertStringContainsString('Bilskyen', $row['description']);
    }

    public function test_year_falls_back_to_first_registration_date(): void
    {
        $vehicle = $this->makeVehicle([
            'model_year' => null,
            'first_registration_year' => null,
            'first_registration_date' => '2019-06-15',
        ]);
        $row = (new MetaVehicleCatalogMapper)->toRow($vehicle);

        $this->assertSame('2019', $row['year']);
    }

    public function test_csv_omits_placeholder_and_missing_images(): void
    {
        $placeholder = new VehicleImage(['image_path' => 'placeholder-vehicle.jpg', 'sort_order' => 0]);
        $missing = $this->makeVehicle(['slug' => 'no-photo']);
        $missing->id = 2001;
        $missing->setRelation('images', collect([$placeholder]));

        $empty = $this->makeVehicle(['slug' => 'empty-photos']);
        $empty->id = 2002;
        $empty->setRelation('images', collect());

        $okImage = new VehicleImage(['image_path' => 'vehicles/1554/a.jpg', 'sort_order' => 0]);
        $ok = $this->makeVehicle();
        $ok->setRelation('images', collect([$okImage]));

        $rowMissing = (new MetaVehicleCatalogMapper)->toRow($missing);
        $this->assertSame('', $rowMissing['image[0].url']);

        $csv = (new MetaVehicleCatalogMapper)->toCsv(new Collection([$missing, $empty, $ok]));

        $this->assertStringContainsString('"1554"', $csv);
        $this->assertStringNotContainsString('"2001"', $csv);
        $this->assertStringNotContainsString('"2002"', $csv);
        $this->assertStringNotContainsString('placeholder-vehicle', $csv);
    }

    public function test_company_office_street_is_not_used_as_listing_address(): void
    {
        $vehicle = $this->makeVehicle(['address' => '', 'postcode' => '']);
        $row = (new MetaVehicleCatalogMapper)->toRow($vehicle);

        $this->assertSame('', $row['address.addr1']);
        $this->assertStringNotContainsString('Smedeland 7', $row['address.addr1']);
        $this->assertSame('Smedeland 7', CompanyProfile::street());
    }

    public function test_preview_ready_requires_city_street_and_region(): void
    {
        $city = new MarketplaceCity(['name' => 'Aarhus', 'slug' => 'aarhus', 'region' => 'Midtjylland']);
        $dealer = new Dealer([
            'slug' => 'aarhus-biler',
            'address' => 'Søndergade 12',
            'city' => 'Aarhus',
            'postcode' => '8000',
        ]);
        $dealer->setRelation('marketplaceCity', $city);

        $image = new VehicleImage(['image_path' => 'vehicles/1554/a.jpg', 'sort_order' => 0]);
        $brand = (object) ['name' => 'Mercedes'];
        $model = (object) ['name' => 'GLE'];
        $vehicle = $this->makeVehicle();
        $vehicle->setRelation('dealer', $dealer);
        $vehicle->setRelation('images', collect([$image]));
        $vehicle->setRelation('brand', $brand);
        $vehicle->setRelation('model', $model);

        $preview = (new MetaVehicleCatalogMapper)->preview($vehicle);
        $this->assertTrue($preview['ready']);
        $this->assertTrue(collect($preview['readiness'])->firstWhere('key', 'region')['ok']);
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

    private function ensureLocationsTable(): void
    {
        if (Schema::hasTable('locations')) {
            return;
        }

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('postcode');
            $table->string('region');
            $table->string('country_code')->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
        });
    }
}
