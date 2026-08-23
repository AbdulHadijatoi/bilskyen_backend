<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use App\Services\DawaGeocodeService;
use App\Services\VehicleMapLocationService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VehicleMapLocationServiceTest extends TestCase
{
    public function test_it_returns_stored_street_coordinates_without_calling_dawa(): void
    {
        Http::fake();

        $vehicle = new Vehicle([
            'latitude' => 55.6761,
            'longitude' => 12.5683,
            'address' => 'Nørre Voldgade 2',
            'postcode' => '1358',
        ]);

        $point = $this->service()->pointFor($vehicle);

        $this->assertSame(55.6761, $point['latitude']);
        $this->assertSame(12.5683, $point['longitude']);
        Http::assertNothingSent();
    }

    public function test_it_geocodes_a_real_street_address(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/adresser*' => Http::response([
                ['x' => 12.5665, 'y' => 55.6789],
            ], 200),
        ]);

        $vehicle = new Vehicle([
            'address' => 'Nørre Voldgade 2',
            'postcode' => '1358',
        ]);

        $point = $this->service()->pointFor($vehicle);

        $this->assertSame(55.6789, $point['latitude']);
        $this->assertSame(12.5665, $point['longitude']);
    }

    public function test_it_falls_back_to_postcode_centroid_for_placeholder_addresses(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre/*' => Http::response([
                'visueltcenter_x' => 12.5736,
                'visueltcenter_y' => 55.711,
            ], 200),
            'api.dataforsyningen.dk/adresser*' => Http::response([['x' => 1, 'y' => 2]], 200),
        ]);

        $vehicle = new Vehicle([
            'address' => 'Address, 2100 city',
            'postcode' => '2100',
        ]);

        $point = $this->service()->pointFor($vehicle, 'Address, 2100 city', '2100');

        $this->assertSame(55.711, $point['latitude']);
        $this->assertSame(12.5736, $point['longitude']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/adresser'));
    }

    private function service(): VehicleMapLocationService
    {
        return new VehicleMapLocationService(new DawaGeocodeService());
    }
}
