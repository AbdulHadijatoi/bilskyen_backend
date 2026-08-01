<?php

namespace Tests\Unit;

use App\Http\Middleware\SeoRedirectMiddleware;
use App\Models\SeoRedirect;
use App\Services\Seo\SchemaBuilderService;
use App\Services\Seo\SeoRedirectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoR5Test extends TestCase
{
    public function test_redirect_path_normalization(): void
    {
        $this->assertSame('/old-page', SeoRedirect::normalizePath('old-page'));
        $this->assertSame('/', SeoRedirect::normalizePath('/'));
    }

    public function test_schema_builder_local_business(): void
    {
        $service = new SchemaBuilderService;
        $json = $service->build('LocalBusiness', [
            'name' => 'Bilskyen',
            'url' => 'https://example.test',
        ]);

        $this->assertSame('LocalBusiness', $json['@type']);
        $this->assertSame('Bilskyen', $json['name']);
    }

    public function test_schema_builder_faq_page(): void
    {
        $service = new SchemaBuilderService;
        $json = $service->build('FAQPage', [
            'faqs' => [
                ['question' => 'Q1', 'answer' => 'A1'],
            ],
        ]);

        $this->assertSame('FAQPage', $json['@type']);
        $this->assertCount(1, $json['mainEntity']);
    }

    public function test_prefix_redirect_appends_suffix(): void
    {
        Cache::forget('seo_redirects_map');

        $redirect = new SeoRedirect([
            'from_path' => '/vehicles',
            'to_path' => '/biler',
            'match_type' => SeoRedirect::MATCH_PREFIX,
            'redirect_type' => 301,
            'is_active' => true,
        ]);
        $redirect->id = 42;

        $service = new class($redirect) extends SeoRedirectService {
            public function __construct(private SeoRedirect $stub) {}

            public function activeMaps(): array
            {
                return [
                    'exact' => [],
                    'prefix' => [[
                        'from' => '/vehicles',
                        'to' => '/biler',
                        'type' => 301,
                        'id' => 42,
                    ]],
                ];
            }

            public function resolve(Request $request): ?SeoRedirect
            {
                $path = SeoRedirect::normalizePath($request->getPathInfo());
                if ($path === '/vehicles' || str_starts_with($path, '/vehicles/')) {
                    return $this->stub;
                }

                return null;
            }

            public function recordHit(SeoRedirect $redirect): void {}
        };

        $this->assertSame('/biler/vw-id7', $service->destinationPath($redirect, '/vehicles/vw-id7'));
        $this->assertSame('/biler', $service->destinationPath($redirect, '/vehicles'));
        $this->assertSame(
            '/biler/vw-id7/enquire',
            $service->destinationPath($redirect, '/vehicles/vw-id7/enquire')
        );

        $middleware = new SeoRedirectMiddleware($service);
        $request = Request::create('/vehicles/vw-id7?city=copenhagen', 'GET');
        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertTrue($response->isRedirect());
        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringContainsString('/biler/vw-id7', $response->headers->get('Location'));
        $this->assertStringContainsString('city=copenhagen', $response->headers->get('Location'));
    }

    public function test_exact_redirect_does_not_match_children(): void
    {
        $redirect = new SeoRedirect([
            'from_path' => '/about',
            'to_path' => '/om-os',
            'match_type' => SeoRedirect::MATCH_EXACT,
            'redirect_type' => 301,
            'is_active' => true,
        ]);

        $service = new SeoRedirectService;
        $this->assertSame('/om-os', $service->destinationPath($redirect, '/about'));
    }
}
