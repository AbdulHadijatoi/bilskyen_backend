<?php

namespace Tests\Unit;

use App\Services\DawaGeocodeService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DawaGeocodeServiceTest extends TestCase
{
    public function test_geocode_reads_wgs84_from_dawa_mini_payload(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response([
                [
                    'x' => 12.5683,
                    'y' => 55.6761,
                ],
            ], 200),
        ]);

        $coords = (new DawaGeocodeService())->geocode('Nørre Voldgade 2', '1358');

        $this->assertSame(55.6761, $coords['latitude']);
        $this->assertSame(12.5683, $coords['longitude']);
    }

    public function test_geocode_returns_null_when_dawa_fails(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/*' => Http::response('error', 500),
        ]);

        $this->assertNull((new DawaGeocodeService())->geocode('Somewhere', '2100'));
    }

    public function test_apply_to_payload_leaves_coords_unset_when_empty(): void
    {
        Http::fake();

        $payload = (new DawaGeocodeService())->applyToPayload([
            'address' => '',
            'postcode' => '',
        ]);

        $this->assertArrayNotHasKey('latitude', $payload);
        Http::assertNothingSent();
    }

    public function test_geocode_postcode_reads_visueltcenter_mini_payload(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre/*' => Http::response([
                'nr' => '2100',
                'visueltcenter_x' => 12.57364633,
                'visueltcenter_y' => 55.7109795,
            ], 200),
        ]);

        $coords = (new DawaGeocodeService())->geocodePostcode('2100');

        $this->assertSame(55.7109795, $coords['latitude']);
        $this->assertSame(12.57364633, $coords['longitude']);
    }

    public function test_geocode_postcode_rejects_non_danish_codes(): void
    {
        Http::fake();

        $this->assertNull((new DawaGeocodeService())->geocodePostcode('77150'));
        Http::assertNothingSent();
    }

    public function test_resolve_skips_street_lookup_when_address_is_empty(): void
    {
        Http::fake([
            'api.dataforsyningen.dk/postnumre/*' => Http::response([
                'visueltcenter_x' => 12.57,
                'visueltcenter_y' => 55.71,
            ], 200),
            'api.dataforsyningen.dk/adresser*' => Http::response([['x' => 1, 'y' => 2]], 200),
        ]);

        $coords = (new DawaGeocodeService())->resolve(null, '2100');

        $this->assertSame(55.71, $coords['latitude']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/postnumre/2100'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/adresser'));
    }
}
