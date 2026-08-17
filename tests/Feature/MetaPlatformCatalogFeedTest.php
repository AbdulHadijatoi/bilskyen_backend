<?php

namespace Tests\Feature;

use App\Http\Middleware\SeoRedirectMiddleware;
use App\Services\Feeds\VehicleFeedBuilderService;
use App\Services\PlatformSettingService;
use Mockery;
use Tests\TestCase;

class MetaPlatformCatalogFeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(SeoRedirectMiddleware::class);
    }
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_platform_csv_is_public_raw_csv_over_token(): void
    {
        $token = 'test-platform-feed-token';
        $csv = '"vehicle_id","title","availability"'."\n".'"1554","Mercedes GLE","IN_STOCK"';

        $settings = Mockery::mock(PlatformSettingService::class)->shouldIgnoreMissing();
        $settings->shouldReceive('get')
            ->with('marketing', 'meta_catalog_feed_token', '')
            ->andReturn($token);
        $this->app->instance(PlatformSettingService::class, $settings);

        $builder = Mockery::mock(VehicleFeedBuilderService::class);
        $builder->shouldReceive('toPlatformFacebookCsv')->once()->andReturn($csv);
        $this->app->instance(VehicleFeedBuilderService::class, $builder);

        $response = $this->get('/api/v1/feeds/platform/'.$token.'/vehicles.csv', [
            'User-Agent' => 'MetaCatalog/1.0',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertSame($csv, $response->getContent());
        $this->assertStringNotContainsString('<html', strtolower($response->getContent()));
        $this->assertStringNotContainsString('{', $response->getContent());
    }

    public function test_platform_csv_rejects_invalid_token(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class)->shouldIgnoreMissing();
        $settings->shouldReceive('get')
            ->with('marketing', 'meta_catalog_feed_token', '')
            ->andReturn('expected-token');
        $this->app->instance(PlatformSettingService::class, $settings);

        $builder = Mockery::mock(VehicleFeedBuilderService::class);
        $builder->shouldReceive('toPlatformFacebookCsv')->never();
        $this->app->instance(VehicleFeedBuilderService::class, $builder);

        $response = $this->get('/api/v1/feeds/platform/wrong-token/vehicles.csv', [
            'User-Agent' => 'MetaCatalog/1.0',
        ]);

        $response->assertStatus(401);
    }
}
