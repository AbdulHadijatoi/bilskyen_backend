<?php

namespace Tests\Unit;

use App\Http\Controllers\CitySeoController;
use App\Http\Middleware\PublicHtmlCache;
use App\Models\Dealer;
use App\Models\MarketplaceCity;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SeoService;
use App\Support\CrawlerRequest;
use App\Support\HomeHeroCopy;
use App\Support\TestimonialAttribution;
use Illuminate\Http\Request;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class SeoHealthPassTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_homepage_h1_is_transactional_not_dream_car(): void
    {
        app()->setLocale('da');
        $title = __('messages.pages.home.title');

        $this->assertSame('Brugte biler til salg i Danmark', $title);
        $this->assertStringNotContainsString('perfekte køretøj', mb_strtolower($title));
        $this->assertStringNotContainsString('Find dit perfekte', $title);

        app()->setLocale('en');
        $this->assertSame('Used cars for sale in Denmark', __('messages.pages.home.title'));
        $this->assertStringNotContainsString('Perfect Vehicle', __('messages.pages.home.title'));
    }

    public function test_home_hero_copy_replaces_emotional_cms_title(): void
    {
        app()->setLocale('da');
        $this->assertSame(
            'Brugte biler til salg i Danmark',
            HomeHeroCopy::title('Find din drømmebil på Bilskyen.dk')
        );
        $this->assertSame(
            'Brugte biler til salg i Danmark',
            HomeHeroCopy::title('Find dit perfekte køretøj')
        );
        $this->assertSame(
            'Brugte biler til salg i Danmark',
            HomeHeroCopy::title('  ')
        );
        $this->assertSame(
            'Brugte biler til salg i Danmark',
            HomeHeroCopy::title(null)
        );
        $this->assertSame(
            'Biler til salg i Jylland',
            HomeHeroCopy::title('Biler til salg i Jylland')
        );

        app()->setLocale('en');
        $this->assertSame(
            'Used cars for sale in Denmark',
            HomeHeroCopy::title('Find your dream car at Bilskyen')
        );
        $this->assertSame(
            'Used cars for sale in Denmark',
            HomeHeroCopy::title('Find Your Perfect Vehicle at Bilskyen')
        );
        $this->assertSame(
            'Used cars for sale in Jutland',
            HomeHeroCopy::title('Used cars for sale in Jutland')
        );
    }

    public function test_homepage_view_uses_hero_copy_sanitizer(): void
    {
        $source = file_get_contents(resource_path('views/home.blade.php'));
        $this->assertStringContainsString('HomeHeroCopy::title', $source);
        $this->assertStringContainsString('HomeHeroCopy::description', $source);
        $this->assertStringNotContainsString('perfekte køretøj|perfect vehicle', $source);
    }

    public function test_home_hero_copy_replaces_emotional_cms_description(): void
    {
        app()->setLocale('da');
        $this->assertSame(
            __('messages.pages.home.description'),
            HomeHeroCopy::description('Søg efter det perfekte match.')
        );
        $this->assertSame(
            __('messages.pages.home.description'),
            HomeHeroCopy::description('Find din drømmebil i dag.')
        );
        $this->assertSame(
            'Søg blandt brugte biler i Danmark.',
            HomeHeroCopy::description('Søg blandt brugte biler i Danmark.')
        );

        app()->setLocale('en');
        $this->assertSame(
            __('messages.pages.home.description'),
            HomeHeroCopy::description('Search our inventory to find the perfect match for your needs.')
        );
        $this->assertSame(
            __('messages.pages.home.description'),
            HomeHeroCopy::description('Find your dream car today.')
        );
    }

    public function test_testimonial_fallbacks_are_unattributed(): void
    {
        foreach (['da', 'en'] as $locale) {
            app()->setLocale($locale);
            $blob = implode(' ', [
                __('messages.pages.home.testimonial_1_name'),
                __('messages.pages.home.testimonial_1_location'),
                __('messages.pages.home.testimonial_2_name'),
                __('messages.pages.home.testimonial_2_location'),
                __('messages.pages.home.testimonial_3_name'),
                __('messages.pages.home.testimonial_3_location'),
                __('messages.pages.home.testimonial_buyer'),
                __('messages.pages.home.testimonial_region'),
            ]);

            $this->assertStringNotContainsString('Copenhagen', $blob);
            $this->assertStringNotContainsString('John Davis', $blob);
            $this->assertStringNotContainsString('Priya Sharma', $blob);
            $this->assertStringNotContainsString('Ahmed Khan', $blob);
        }

        app()->setLocale('da');
        $this->assertSame('Køber', TestimonialAttribution::name('John Davis'));
        $this->assertSame('Danmark', TestimonialAttribution::location('Copenhagen, Denmark'));
        $this->assertSame('Køber', TestimonialAttribution::name('Køber'));
    }

    public function test_blog_description_is_thicker_than_placeholder(): void
    {
        app()->setLocale('da');
        $this->assertGreaterThan(80, mb_strlen(__('messages.cms.blog_description')));
        app()->setLocale('en');
        $this->assertGreaterThan(80, mb_strlen(__('messages.cms.blog_description')));
    }

    public function test_city_cars_schema_has_no_faqpage_but_faq_copy_exists(): void
    {
        app()->setLocale('da');
        $this->assertNotSame('messages.pages.cities.faq_cars_q1', __('messages.pages.cities.faq_cars_q1', ['city' => 'Aarhus']));
        $this->assertStringContainsString('Aarhus', __('messages.pages.cities.faq_cars_q1', ['city' => 'Aarhus']));

        $city = new MarketplaceCity([
            'name' => 'Aarhus',
            'slug' => 'aarhus',
            'published_vehicle_count' => 12,
            'dealer_count' => 3,
        ]);

        $method = new ReflectionMethod(CitySeoController::class, 'buildCarsSchemas');
        $method->setAccessible(true);
        $schema = $method->invoke(app(CitySeoController::class), $city, collect(), [
            'breadcrumbs_json' => [],
        ]);

        $encoded = (string) json_encode($schema);
        $this->assertStringNotContainsString('FAQPage', $encoded);
        $this->assertStringContainsString('ItemList', $encoded);
    }

    public function test_vehicle_schema_omits_placeholder_image(): void
    {
        $owner = new User(['name' => 'Au2Vest']);
        $dealer = new Dealer(['city' => 'Hvidovre', 'slug' => 'au2vest']);
        $dealer->setRelation('owner', $owner);

        $vehicle = new Vehicle([
            'slug' => 'vw-id7-style-tourer-5d',
            'title' => 'VW ID.7',
            'price' => 439799,
            'km_driven' => 100,
            'model_year' => 2026,
        ]);
        $vehicle->setRelation('dealer', $dealer);
        $vehicle->setRelation('images', collect([(object) [
            'image_url' => 'https://cdn.example/placeholder-vehicle.jpg',
            'thumbnail_url' => '/placeholder-vehicle.jpg',
        ]]));
        $brand = Mockery::mock();
        $brand->name = 'VW';
        $model = Mockery::mock();
        $model->name = 'ID.7';
        $variant = Mockery::mock();
        $variant->name = '';
        $vehicle->setRelation('brand', $brand);
        $vehicle->setRelation('model', $model);
        $vehicle->setRelation('variant', $variant);

        $seo = Mockery::mock(SeoService::class)->makePartial();
        $seo->shouldReceive('getForPage')->andReturn(null);

        $resolved = $seo->resolveForVehicle($vehicle);

        $this->assertArrayNotHasKey('image', $resolved['schema_json']);
        $this->assertNull($resolved['og_image']);
        $this->assertNull($resolved['twitter_image']);
    }

    public function test_vehicles_listing_h1_is_present_but_visually_hidden(): void
    {
        $source = file_get_contents(resource_path('views/vehicles.blade.php'));

        $this->assertMatchesRegularExpression('/<h1\s+class="sr-only">/', $source);
        $this->assertStringContainsString("messages.pages.vehicles.listing_h1", $source);
        $this->assertStringContainsString("\$seo['meta_title']", $source);
    }

    public function test_listing_component_lazy_loads_images(): void
    {
        $source = file_get_contents(resource_path('views/components/vehicle-listing-item.blade.php'));

        $this->assertStringContainsString('loading="lazy"', $source);
        $this->assertStringContainsString('decoding="async"', $source);
        $this->assertStringContainsString('width="800"', $source);
        $this->assertStringContainsString('height="600"', $source);
    }

    public function test_layouts_do_not_load_google_fonts(): void
    {
        foreach ([
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/layouts/auth.blade.php'),
        ] as $path) {
            $html = file_get_contents($path);
            $this->assertStringNotContainsString('fonts.googleapis.com', $html);
            $this->assertStringNotContainsString('fonts.gstatic.com', $html);
        }
    }

    public function test_public_html_cache_strips_cookies_for_googlebot_only(): void
    {
        $middleware = new PublicHtmlCache;

        $bot = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);
        $this->assertTrue(CrawlerRequest::isCrawler($bot));

        $botResponse = $middleware->handle($bot, function () {
            return response('<html></html>', 200, ['Content-Type' => 'text/html'])
                ->cookie('laravel_session', 'bot-session', 120);
        });
        $this->assertStringContainsString('public', (string) $botResponse->headers->get('Cache-Control'));
        $this->assertSame([], $botResponse->headers->getCookies());
        $this->assertNull($botResponse->headers->get('Set-Cookie'));

        $guest = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        ]);
        $this->assertFalse(CrawlerRequest::isCrawler($guest));

        $guestResponse = $middleware->handle($guest, function () {
            return response('<html></html>', 200, ['Content-Type' => 'text/html'])
                ->cookie('laravel_session', 'human-session', 120);
        });
        $this->assertStringContainsString('public', (string) $guestResponse->headers->get('Cache-Control'));
        $this->assertNotEmpty($guestResponse->headers->getCookies());
    }

    public function test_googlebot_sitemap_response_strips_cookies(): void
    {
        $middleware = new PublicHtmlCache;
        $bot = Request::create('/sitemap.xml', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);

        $response = $middleware->handle($bot, function () {
            return response('<urlset></urlset>', 200, ['Content-Type' => 'application/xml'])
                ->cookie('laravel_session', 'bot-session', 120);
        });

        $this->assertSame([], $response->headers->getCookies());
        $this->assertNull($response->headers->get('Set-Cookie'));
    }

    public function test_dealer_page_lazy_loads_listing_images(): void
    {
        $source = file_get_contents(resource_path('views/dealer-page.blade.php'));
        $this->assertStringContainsString('loading="lazy"', $source);
        $this->assertStringContainsString('decoding="async"', $source);
        $this->assertStringContainsString('width="800"', $source);
        $this->assertStringContainsString('dealer_page.meta_title_cars', $source);
        $controller = file_get_contents(app_path('Http/Controllers/DealerController.php'));
        $this->assertStringContainsString('dealer_page.meta_title', $controller);
    }

    public function test_vehicle_detail_does_not_emit_placeholder_image(): void
    {
        $source = file_get_contents(resource_path('views/vehicle-detail.blade.php'));
        $this->assertStringNotContainsString('src="/placeholder-vehicle.jpg"', $source);
        $this->assertStringContainsString('$publicImages', $source);
    }

    public function test_dealer_meta_title_uses_dealer_name(): void
    {
        app()->setLocale('da');
        $this->assertSame(
            'Carhouse – brugte biler | Bilskyen',
            __('messages.pages.dealer_page.meta_title', ['name' => 'Carhouse'])
        );
        app()->setLocale('en');
        $this->assertSame(
            'Carhouse – used cars | Bilskyen',
            __('messages.pages.dealer_page.meta_title', ['name' => 'Carhouse'])
        );
    }

    public function test_homepage_document_title_matches_h1(): void
    {
        app()->setLocale('da');
        $h1 = HomeHeroCopy::title('Find din drømmebil på Bilskyen.dk');
        $this->assertSame('Brugte biler til salg i Danmark', $h1);

        $source = file_get_contents(app_path('Http/Controllers/HomeController.php'));
        $this->assertStringContainsString('HomeHeroCopy::title', $source);
        $this->assertStringContainsString("\$homeTitle.' | Bilskyen'", $source);
    }
}
