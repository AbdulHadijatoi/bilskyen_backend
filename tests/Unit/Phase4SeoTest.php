<?php

namespace Tests\Unit;

use App\Console\Commands\SeoGscInspectCommand;
use App\Constants\VehicleListStatus;
use App\Models\MarketplaceCity;
use App\Models\Vehicle;
use App\Observers\VehicleIndexNowObserver;
use App\Services\CityIndexService;
use App\Services\Seo\IndexNowService;
use App\Services\SeoService;
use App\Support\CompanyProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class Phase4SeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.indexnow.key' => 'abcdef12-3456-7890-abcd-ef1234567890',
            'services.indexnow.host' => 'bilskyen.dk',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_indexnow_queues_published_not_draft(): void
    {
        $service = app(IndexNowService::class);
        $observer = new VehicleIndexNowObserver($service);

        $draft = new Vehicle([
            'slug' => 'draft-car',
            'list_status_id' => VehicleListStatus::DRAFT,
            'title' => 'Draft',
            'price' => 100000,
        ]);
        $draft->wasRecentlyCreated = true;
        $observer->saved($draft);
        $this->assertSame([], $service->queuedUrls());

        $published = new Vehicle([
            'slug' => 'vw-polo',
            'list_status_id' => VehicleListStatus::PUBLISHED,
            'title' => 'VW Polo',
            'price' => 149900,
        ]);
        $published->wasRecentlyCreated = true;
        $observer->saved($published);

        $queued = $service->queuedUrls();
        $this->assertCount(1, $queued);
        $this->assertStringContainsString('/biler/vw-polo', $queued[0]);
        $this->assertStringStartsWith('https://', $queued[0]);
    }

    public function test_indexnow_queues_sold_and_skips_when_disabled(): void
    {
        $service = app(IndexNowService::class);
        $observer = new VehicleIndexNowObserver($service);

        $vehicle = new Vehicle([
            'slug' => 'sold-car',
            'title' => 'Sold',
            'price' => 1,
        ]);
        $vehicle->exists = true;
        $vehicle->setRawAttributes([
            'slug' => 'sold-car',
            'title' => 'Sold',
            'price' => 1,
            'list_status_id' => VehicleListStatus::PUBLISHED,
        ], true);
        $vehicle->list_status_id = VehicleListStatus::SOLD;
        $vehicle->syncChanges();
        $observer->saved($vehicle);
        $this->assertNotEmpty($service->queuedUrls());

        Cache::flush();
        config(['services.indexnow.key' => '']);
        $disabled = app(IndexNowService::class);
        $disabled->queue('https://bilskyen.dk/biler/anything');
        $this->assertSame([], $disabled->queuedUrls());
    }

    public function test_indexnow_flush_posts_https_payload(): void
    {
        Http::fake([
            IndexNowService::ENDPOINT => Http::response('ok', 200),
        ]);

        $service = app(IndexNowService::class);
        $service->queue('https://bilskyen.dk/biler/vw-polo');
        $sent = $service->flush();

        $this->assertSame(1, $sent);
        $this->assertSame([], $service->queuedUrls());
        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === IndexNowService::ENDPOINT
                && $data['host'] === 'bilskyen.dk'
                && $data['key'] === 'abcdef12-3456-7890-abcd-ef1234567890'
                && str_starts_with($data['keyLocation'], 'https://')
                && $data['urlList'][0] === 'https://bilskyen.dk/biler/vw-polo';
        });
    }

    public function test_indexnow_flush_does_not_http_without_key(): void
    {
        Http::fake();
        config(['services.indexnow.key' => '']);
        $service = new IndexNowService;
        $service->queue('https://bilskyen.dk/biler/vw-polo');
        $this->assertSame(0, $service->flush());
        Http::assertNothingSent();
    }

    public function test_indexnow_key_route_and_garbage_404(): void
    {
        $key = 'abcdef12-3456-7890-abcd-ef1234567890';
        $controller = app(\App\Http\Controllers\SeoController::class);

        $ok = $controller->indexNowKey($key);
        $this->assertSame(200, $ok->getStatusCode());
        $this->assertSame($key, $ok->getContent());

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $controller->indexNowKey('ffffffff');
    }

    public function test_indexnow_key_route_is_registered(): void
    {
        $uris = collect(\Illuminate\Support\Facades\Route::getRoutes())->map(fn ($route) => $route->uri());
        $this->assertTrue($uris->contains('llms.txt'));
        $this->assertTrue($uris->contains('{indexNowKey}.txt'));
        $this->assertTrue($uris->contains('robots.txt'));
        $llmsPos = array_search('llms.txt', $uris->values()->all(), true);
        $keyPos = array_search('{indexNowKey}.txt', $uris->values()->all(), true);
        $this->assertNotFalse($llmsPos);
        $this->assertNotFalse($keyPos);
        $this->assertLessThan($keyPos, $llmsPos);
    }

    public function test_city_gate_warns_and_hard_stops(): void
    {
        $ok = Mockery::mock(CityIndexService::class);
        $ok->shouldReceive('indexableCarsCount')->andReturn(29);
        $this->app->instance(CityIndexService::class, $ok);
        $this->artisan('seo:city-gate')->assertExitCode(0);

        $stop = Mockery::mock(CityIndexService::class);
        $stop->shouldReceive('indexableCarsCount')->andReturn(50);
        $this->app->instance(CityIndexService::class, $stop);
        $this->artisan('seo:city-gate')->assertExitCode(1);
    }

    public function test_city_hard_stop_does_not_insert_new_row(): void
    {
        Schema::dropIfExists('marketplace_cities');
        Schema::create('marketplace_cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('region', 100)->nullable();
            $table->json('aliases')->nullable();
            $table->unsignedInteger('published_vehicle_count')->default(0);
            $table->unsignedInteger('dealer_count')->default(0);
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();
            $table->json('top_brands')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_computed_at')->nullable();
            $table->timestamps();
        });

        for ($i = 1; $i <= MarketplaceCity::INDEXABLE_CARS_HARD_STOP; $i++) {
            MarketplaceCity::query()->create([
                'name' => 'Gate City '.$i,
                'slug' => 'gate-city-'.$i,
                'is_active' => true,
                'published_vehicle_count' => MarketplaceCity::MIN_VEHICLES_FOR_INDEX,
                'dealer_count' => 0,
            ]);
        }

        $service = app(CityIndexService::class);
        $this->assertSame(50, $service->indexableCarsCount());
        $this->assertNull($service->ensureCityFromName('Brandnewville'));
        $this->assertSame(50, MarketplaceCity::query()->count());

        $existing = $service->ensureCityFromName('Gate City 1');
        $this->assertNotNull($existing);
        $this->assertSame('gate-city-1', $existing->slug);
    }

    public function test_llms_txt_has_https_marketplace_urls(): void
    {
        $txt = app(SeoService::class)->getLlmsTxt();
        $this->assertStringContainsString(CompanyProfile::cvr(), $txt);
        $this->assertStringContainsString('https://', $txt);
        $this->assertStringContainsString('/biler', $txt);
        $this->assertStringContainsString('/markedsdata', $txt);
        $this->assertStringContainsString('/saelg-din-bil', $txt);
        $this->assertStringNotContainsString('Revolutionerer forhandlerstyring', $txt);

        $response = app(\App\Http\Controllers\SeoController::class)->llmsTxt();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('CVR', $response->getContent());
    }

    public function test_gsc_hubs_include_listing_and_city_and_flag_http_sitemaps(): void
    {
        $items = SeoGscInspectCommand::hubInspectionItems('https://bilskyen.dk', [
            'indexable_city' => 'horsens',
            'thin_city' => 'koege',
            'dealers' => ['carhouse', 'dealer'],
        ]);
        $urls = array_column($items, 'url');
        $this->assertContains('https://bilskyen.dk/biler', $urls);
        $this->assertContains('https://bilskyen.dk/markedsdata', $urls);
        $this->assertContains('https://bilskyen.dk/biler-i/horsens', $urls);
        $this->assertContains('https://bilskyen.dk/biler-i/koege', $urls);
        $this->assertContains('https://bilskyen.dk/dealer-carhouse', $urls);
        $this->assertNotContains('https://bilskyen.dk/dealer-dealer', $urls);

        $this->assertTrue(SeoGscInspectCommand::sitemapPathIsHttp('http://bilskyen.dk/sitemap.xml'));
        $this->assertFalse(SeoGscInspectCommand::sitemapPathIsHttp('https://bilskyen.dk/sitemap.xml'));

        $source = file_get_contents(app_path('Console/Commands/SeoGscInspectCommand.php'));
        $this->assertStringContainsString('{--hubs', $source);
        $this->assertStringContainsString('biler-i/', $source);
    }
}
