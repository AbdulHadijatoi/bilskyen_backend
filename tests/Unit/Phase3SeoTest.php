<?php

namespace Tests\Unit;

use App\Constants\VehicleListStatus;
use App\Models\CmsPost;
use App\Models\LandingPage;
use App\Models\User;
use App\Services\InventoryHubService;
use App\Services\MarketSnapshotService;
use App\Services\SeoService;
use App\Support\CompanyProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class Phase3SeoTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_guide_slugs_get_unique_metas_not_saas_fallback(): void
    {
        app()->setLocale('da');

        $saas = 'Revolutionerer forhandlerstyring med kvalitetskøretøjer og exceptionel kundeservice.';
        $this->assertNotSame($saas, __('messages.layouts.meta_description'));
        $this->assertStringNotContainsString('Revolutionerer forhandlerstyring', __('messages.layouts.meta_description'));

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->andReturn(null);

        $ev = $seo->resolveForLandingPage(new LandingPage([
            'slug' => 'brugte-elbiler',
            'title' => 'Brugte elbiler',
            'meta_description' => '',
        ]));
        $budget = $seo->resolveForLandingPage(new LandingPage([
            'slug' => 'brugte-biler-under-100000-kr',
            'title' => 'Brugte biler under 100.000 kr.',
            'meta_description' => '',
        ]));

        $this->assertNotSame($ev['meta_description'], $budget['meta_description']);
        $this->assertNotSame($saas, $ev['meta_description']);
        $this->assertNotSame($saas, $budget['meta_description']);
        $this->assertStringContainsString('elbiler', (string) $ev['meta_description']);
        $this->assertStringContainsString('100.000', (string) $budget['meta_description']);
    }

    public function test_article_schema_has_person_author_and_date_modified(): void
    {
        $post = new CmsPost([
            'slug' => 'used-car-checks',
            'title' => 'Five checks before buying',
            'excerpt' => 'Check history, rust, and a test drive.',
            'author_user_id' => 1,
            'published_at' => now(),
        ]);
        $post->updated_at = now()->subHour();
        $post->setRelation('featuredMedia', null);
        $post->setRelation('author', new User(['name' => 'Mette Hansen']));

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->once()->with('blog', 'used-car-checks')->andReturn(null);

        $resolved = $seo->resolveForCmsPost($post);
        $schema = $resolved['schema_json'];

        $this->assertSame('Person', $schema['author']['@type']);
        $this->assertSame('Mette Hansen', $schema['author']['name']);
        $this->assertNotEmpty($schema['dateModified']);
        $this->assertArrayNotHasKey('potentialAction', $schema);
        $this->assertStringNotContainsString('SearchAction', (string) json_encode($schema));
    }

    public function test_article_schema_falls_back_to_organization_author(): void
    {
        $post = new CmsPost([
            'slug' => 'used-car-checks',
            'title' => 'Five checks before buying',
            'excerpt' => 'Check history.',
            'author_user_id' => null,
            'published_at' => now(),
        ]);
        $post->updated_at = now();
        $post->setRelation('featuredMedia', null);

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->once()->andReturn(null);

        $schema = $seo->resolveForCmsPost($post)['schema_json'];

        $this->assertSame('Organization', $schema['author']['@type']);
        $this->assertSame(CompanyProfile::name(), $schema['author']['name']);
        $this->assertNotEmpty($schema['dateModified']);
    }

    public function test_homepage_stat_1_uses_live_published_count(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/HomeController.php'));
        $view = file_get_contents(resource_path('views/home.blade.php'));

        $this->assertStringContainsString("'publishedVehicleCount' => \$publishedVehicleCount", $controller);
        $this->assertStringContainsString('$vehicleCountFormatted', $view);
        $this->assertStringNotContainsString("homePageContent['stat_1_value']", $view);
        $this->assertSame('Biler til salg', trans('messages.pages.home.stat_1_title', [], 'da'));
    }

    public function test_market_snapshot_includes_generated_at_and_methodology(): void
    {
        $this->assertNotSame('messages.pages.market.methodology', __('messages.pages.market.methodology'));
        $this->assertNotSame('messages.pages.market.generated_at', __('messages.pages.market.generated_at'));

        $service = new MarketSnapshotService;
        $snapshot = $service->assemble([]);

        $this->assertArrayHasKey('generated_at', $snapshot);
        $this->assertNotEmpty($snapshot['generated_at']);
        $this->assertSame(0, $snapshot['listing_count']);

        $seo = app(SeoService::class)->resolveForMarketSnapshot($snapshot);
        $this->assertSame('WebPage', $seo['schema_json']['@type']);
        $this->assertStringNotContainsString('FAQPage', (string) json_encode($seo['schema_json']));
        $this->assertSame(route('market-snapshot'), $seo['canonical_url']);
    }

    public function test_market_snapshot_excludes_unpublished_vehicles(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('dmr_drive_energies');

        Schema::create('dmr_drive_energies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_status_id');
            $table->decimal('price')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('fuel_type_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $fuelId = DB::table('dmr_drive_energies')->insertGetId(['name' => 'El']);
        DB::table('vehicles')->insert([
            [
                'list_status_id' => VehicleListStatus::PUBLISHED,
                'price' => 100000,
                'published_at' => now()->subDays(4),
                'fuel_type_id' => $fuelId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'list_status_id' => VehicleListStatus::DRAFT,
                'price' => 999999,
                'published_at' => now()->subDays(1),
                'fuel_type_id' => $fuelId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = new MarketSnapshotService;
        $rows = $service->publishedRows();
        $this->assertCount(1, $rows);
        $this->assertSame(100000.0, (float) $rows->first()->price);

        $snapshot = $service->assemble($rows);
        $this->assertSame(1, $snapshot['listing_count']);
        $this->assertSame(100000.0, $snapshot['median_price']);
        $this->assertSame(1, $snapshot['by_fuel']['el']['count']);
    }

    public function test_hub_below_threshold_is_noindex_follow(): void
    {
        $this->assertSame('noindex, follow', InventoryHubService::robotsForCount(2));
        $this->assertSame('index, follow', InventoryHubService::robotsForCount(3));

        $seo = app(SeoService::class)->resolveForHub([
            'heading' => __('messages.pages.hubs.el_heading'),
            'intro' => __('messages.pages.hubs.el_intro', ['count' => 1]),
            'count' => 1,
            'indexable' => false,
            'canonical' => route('hubs.electric'),
            'listing_urls' => [],
        ]);

        $this->assertSame('noindex, follow', $seo['robots']);
        $this->assertSame(route('hubs.electric'), $seo['canonical_url']);
        $this->assertNotSame(route('vehicles'), $seo['canonical_url']);
        $this->assertStringNotContainsString('FAQPage', (string) json_encode($seo['schema_json']));
    }

    public function test_hub_routes_are_registered_before_vehicle_detail(): void
    {
        $order = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (in_array($uri, ['biler/el', 'biler/maerke/{brand}', 'biler', 'biler/{vehicle}'], true)
                && ! in_array($uri, $order, true)) {
                $order[] = $uri;
            }
        }

        $this->assertSame(['biler/el', 'biler/maerke/{brand}', 'biler', 'biler/{vehicle}'], $order);
        $this->assertSame('biler', Route::getRoutes()->getByName('vehicles')->uri());
        $this->assertSame('biler/el', Route::getRoutes()->getByName('hubs.electric')->uri());
        $this->assertSame('InventoryHubController', class_basename(Route::getRoutes()->getByName('hubs.electric')->getController()));
        $this->assertSame('HomeController', class_basename(Route::getRoutes()->getByName('vehicle.detail')->getController()));
    }

    public function test_sitemap_includes_market_snapshot_and_conditional_hubs(): void
    {
        $source = file_get_contents(app_path('Services/SeoService.php'));
        $this->assertStringContainsString("route('market-snapshot')", $source);
        $this->assertStringContainsString('isElectricIndexable', $source);
        $this->assertStringContainsString('isBrandIndexable', $source);
        $this->assertStringContainsString("'changefreq' => 'weekly'", $source);
        $this->assertStringContainsString('isSitemapEligible', $source);
        $this->assertStringContainsString("whereHas('vehicles'", $source);
    }
}
