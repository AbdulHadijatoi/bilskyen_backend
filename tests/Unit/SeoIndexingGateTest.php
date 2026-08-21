<?php

namespace Tests\Unit;

use App\Http\Controllers\SeoController;
use App\Http\Middleware\SecurityHeaders;
use App\Services\PlatformSettingService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SeoIndexingGateTest extends TestCase
{
    private SeoService $seo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seo = app(SeoService::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_indexing_disabled_outside_production(): void
    {
        $this->app['env'] = 'staging';

        $this->assertFalse($this->seo->isIndexingEnabled());
        $this->assertSame("User-agent: *\nDisallow: /", $this->seo->getRobotsTxt());
        $this->assertStringNotContainsString('Sitemap:', $this->seo->getRobotsTxt());

        $xml = $this->seo->getSitemapXml();
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringNotContainsString('<url>', $xml);
    }

    public function test_indexing_enabled_in_production_robots_allows_and_links_sitemap(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://example.test']);

        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')->with('seo', 'robots_mode', 'default')->andReturn('default');
        $settings->shouldReceive('get')->with('seo', 'robots_custom_body', '')->andReturn('');
        $this->app->instance(PlatformSettingService::class, $settings);

        $this->assertTrue($this->seo->isIndexingEnabled());

        $robots = $this->seo->getRobotsTxt();
        $this->assertStringContainsString('Allow: /', $robots);
        $this->assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $robots);
        $this->assertDoesNotMatchRegularExpression('/^Disallow: \/$/m', $robots);
    }

    public function test_robots_sitemap_line_is_https_when_app_url_is_http(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'http://example.test']);

        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')->with('seo', 'robots_mode', 'default')->andReturn('default');
        $settings->shouldReceive('get')->with('seo', 'robots_custom_body', '')->andReturn('');
        $this->app->instance(PlatformSettingService::class, $settings);

        $robots = $this->seo->getRobotsTxt();
        $this->assertStringContainsString('Sitemap: https://example.test/sitemap.xml', $robots);
        $this->assertStringNotContainsString('Sitemap: http://', $robots);
    }

    public function test_canonical_public_url_rewrites_http_and_dedupes_slash(): void
    {
        config(['app.url' => 'http://example.test']);

        $this->assertSame('https://example.test/', $this->seo->canonicalPublicUrl('http://example.test/'));
        $this->assertSame('https://example.test/biler', $this->seo->canonicalPublicUrl('http://example.test/biler/'));
        $this->assertSame('https://example.test/biler', $this->seo->canonicalPublicUrl('/biler'));
        $this->assertSame(
            $this->seo->canonicalPublicUrl('http://example.test/biler'),
            $this->seo->canonicalPublicUrl('https://example.test/biler/')
        );
    }

    public function test_robots_controller_disallows_all_when_not_production(): void
    {
        $this->app['env'] = 'staging';

        $response = app(SeoController::class)->robots();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertSame("User-agent: *\nDisallow: /", $response->getContent());
    }

    public function test_sitemap_controller_empty_when_not_production(): void
    {
        $this->app['env'] = 'staging';

        $response = app(SeoController::class)->sitemap();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertStringNotContainsString('<url>', $response->getContent());
    }

    public function test_security_headers_set_x_robots_tag_outside_production(): void
    {
        $this->app['env'] = 'local';

        $middleware = new SecurityHeaders;
        $response = $middleware->handle(Request::create('/', 'GET'), fn () => new Response('ok'));

        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
    }

    public function test_security_headers_omit_x_robots_tag_in_production(): void
    {
        $this->app['env'] = 'production';

        $middleware = new SecurityHeaders;
        $response = $middleware->handle(Request::create('/', 'GET'), fn () => new Response('ok'));

        $this->assertFalse($response->headers->has('X-Robots-Tag'));
    }

    public function test_custom_robots_rewrites_http_sitemap_to_https(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'http://bilskyen.dk']);

        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')->with('seo', 'robots_mode', 'default')->andReturn('custom');
        $settings->shouldReceive('get')->with('seo', 'robots_custom_body', '')->andReturn(
            "User-agent: *\nAllow: /\n\nSitemap: http://bilskyen.dk/sitemap.xml"
        );
        $this->app->instance(PlatformSettingService::class, $settings);

        $robots = $this->seo->getRobotsTxt();
        $this->assertStringContainsString('Sitemap: https://bilskyen.dk/sitemap.xml', $robots);
        $this->assertStringNotContainsString('Sitemap: http://', $robots);
    }

    public function test_forget_public_caches_clears_env_suffixed_and_legacy_keys(): void
    {
        $env = app()->environment();
        $sitemapKey = SeoService::sitemapCacheKey($env);
        $robotsKey = SeoService::robotsCacheKey($env);

        Cache::put($sitemapKey, '<xml/>', 60);
        Cache::put($robotsKey, 'robots', 60);
        Cache::put('sitemap_xml', 'legacy', 60);
        Cache::put('robots_txt', 'legacy-robots', 60);
        Cache::put('sitemap_xml_'.$env, 'old-env', 60);

        $this->assertStringContainsString('_v2', $sitemapKey);

        SeoService::forgetPublicCaches();

        $this->assertNull(Cache::get($sitemapKey));
        $this->assertNull(Cache::get($robotsKey));
        $this->assertNull(Cache::get('sitemap_xml'));
        $this->assertNull(Cache::get('robots_txt'));
        $this->assertNull(Cache::get('sitemap_xml_'.$env));
    }

    public function test_sitemap_controller_uses_versioned_cache_key(): void
    {
        $this->app['env'] = 'staging';
        Cache::flush();

        $response = app(SeoController::class)->sitemap();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull(Cache::get(SeoService::sitemapCacheKey('staging')));
        $this->assertNull(Cache::get('sitemap_xml_staging'));
    }
}
