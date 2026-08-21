<?php

namespace Tests\Unit;

use App\Http\Middleware\CanonicalUrlMiddleware;
use App\Http\Middleware\PublicHtmlCache;
use App\Services\Seo\SchemaBuilderService;
use App\Support\CompanyProfile;
use App\Support\SchemaBrandName;
use Illuminate\Http\Request;
use Tests\TestCase;

class Phase2SeoTest extends TestCase
{
    public function test_layouts_do_not_load_tailwind_play_cdn(): void
    {
        foreach ([
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/layouts/auth.blade.php'),
            resource_path('views/cms/preview-frame.blade.php'),
        ] as $path) {
            $this->assertStringNotContainsString('cdn.tailwindcss.com', file_get_contents($path));
        }
    }

    public function test_organization_schema_has_tax_id_and_address(): void
    {
        $graph = (new SchemaBuilderService)->sitewideGraph();

        $this->assertSame('https://schema.org', $graph['@context']);
        $types = array_column($graph['@graph'], '@type');
        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);

        $org = collect($graph['@graph'])->firstWhere('@type', 'Organization');
        $this->assertSame('45251853', $org['taxID']);
        $this->assertSame('PostalAddress', $org['address']['@type']);
        $this->assertSame('Smedeland 7', $org['address']['streetAddress']);
        $this->assertArrayNotHasKey('potentialAction', collect($graph['@graph'])->firstWhere('@type', 'WebSite'));
    }

    public function test_vehicle_offer_includes_availability_and_condition(): void
    {
        $json = (new SchemaBuilderService)->build('Vehicle', [
            'name' => 'VW Polo',
            'brand' => SchemaBrandName::normalize('VW'),
            'price' => 149900,
            'url' => 'https://example.test/biler/vw-polo',
            'image' => 'https://example.test/photo.jpg',
            'availability' => 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/UsedCondition',
            'seller' => [
                '@type' => 'AutoDealer',
                '@id' => 'https://example.test/dealer-carhouse#dealer',
                'name' => 'Carhouse',
                'url' => 'https://example.test/dealer-carhouse',
            ],
        ]);

        $this->assertSame('Volkswagen', $json['brand']['name']);
        $this->assertSame('https://example.test/photo.jpg', $json['image']);
        $this->assertSame('https://schema.org/InStock', $json['offers']['availability']);
        $this->assertSame('https://schema.org/UsedCondition', $json['offers']['itemCondition']);
        $this->assertSame('https://example.test/dealer-carhouse#dealer', $json['offers']['seller']['@id']);
        $this->assertArrayNotHasKey('potentialAction', $json);
    }

    public function test_schema_brand_normalizes_aliases_and_all_caps(): void
    {
        $this->assertSame('Volkswagen', SchemaBrandName::normalize('VW'));
        $this->assertSame('Toyota', SchemaBrandName::normalize('TOYOTA'));
        $this->assertSame('BMW', SchemaBrandName::normalize('BMW'));
    }

    public function test_canonical_middleware_strips_trailing_slash_and_keeps_query(): void
    {
        $middleware = new CanonicalUrlMiddleware;
        $request = Request::create('http://localhost/biler/?sort=price', 'GET');
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertTrue($response->isRedirect());
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('http://localhost/biler?sort=price', $response->headers->get('Location'));
    }

    public function test_canonical_middleware_collapses_www_to_apex_https(): void
    {
        config(['app.url' => 'https://bilskyen.dk']);
        $middleware = new CanonicalUrlMiddleware;
        $request = Request::create('http://www.bilskyen.dk/biler/', 'GET');
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertTrue($response->isRedirect());
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://bilskyen.dk/biler', $response->headers->get('Location'));
    }

    public function test_public_html_cache_for_guests_only(): void
    {
        $middleware = new PublicHtmlCache;

        $guest = Request::create('/', 'GET');
        $guestResponse = $middleware->handle($guest, fn () => response('<html></html>', 200, ['Content-Type' => 'text/html']));
        $this->assertStringContainsString('public', $guestResponse->headers->get('Cache-Control'));

        $authed = Request::create('/', 'GET', [], ['bilskyen_auth' => '1']);
        $authedResponse = $middleware->handle($authed, fn () => response('<html></html>', 200, ['Content-Type' => 'text/html']));
        $this->assertStringNotContainsString('public', (string) $authedResponse->headers->get('Cache-Control'));
    }

    public function test_placeholder_phone_is_not_public(): void
    {
        $this->assertFalse(CompanyProfile::isPublicPhone('+45 12 34 56 78'));
        $this->assertFalse(CompanyProfile::isPublicPhone(''));
        $this->assertNull(CompanyProfile::publicPhone('+45 12 34 56 78'));
        $this->assertStringNotContainsString('Dealership Lane', CompanyProfile::publicAddress('123 Dealership Lane, Copenhagen, Denmark'));
        $this->assertStringContainsString('Smedeland 7', CompanyProfile::addressLine());
    }

    public function test_production_http_app_url_is_forced_to_https(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'http://bilskyen.dk']);

        \App\Providers\AppServiceProvider::forcePublicHttpsIfProduction();

        $this->assertSame('https://bilskyen.dk', config('app.url'));
        $this->assertStringStartsWith('https://', url('/biler'));
    }
}
