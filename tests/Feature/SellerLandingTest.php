<?php

namespace Tests\Feature;

use App\Http\Controllers\SellYourCarController;
use App\Http\Middleware\AuthenticateWeb;
use App\Http\Middleware\SeoRedirectMiddleware;
use App\Services\AiService;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CityIndexService;
use App\Services\DmrLookupAssociationService;
use App\Services\FileService;
use App\Services\PageContentService;
use App\Services\PlatformSettingService;
use App\Services\SellYourCarSubmissionService;
use App\Services\SeoService;
use App\Services\VehicleTrustReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SellerLandingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_guest_get_saelg_din_bil_is_public(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/saelg-din-bil', 'GET'));

        $this->assertSame('sell-your-car', $route->getName());
        $this->assertFalse(in_array('auth.web', $route->gatherMiddleware(), true));

        $post = app('router')->getRoutes()->match(Request::create('/saelg-din-bil', 'POST'));
        $this->assertTrue(in_array('auth.web', $post->gatherMiddleware(), true));
    }

    public function test_guest_http_get_returns_landing_not_login(): void
    {
        $this->withoutMiddleware(SeoRedirectMiddleware::class);

        $this->mock(AuthService::class, function ($mock) {
            $mock->shouldReceive('getAuthenticatedUser')->andReturn(null);
        });
        $this->mock(SeoService::class, function ($mock) {
            $mock->shouldReceive('getForPage')->andReturn(null);
        });
        $this->mock(PlatformSettingService::class, function ($mock) {
            $mock->shouldReceive('get')->andReturn('');
            $mock->shouldReceive('isFaqPageEnabled')->andReturn(false);
            $mock->shouldReceive('isLanguageSwitcherEnabled')->andReturn(false);
        });
        $this->mock(PageContentService::class, function ($mock) {
            $mock->shouldReceive('getHomePageContent')->andReturn([]);
        });
        $this->mock(CityIndexService::class, function ($mock) {
            $mock->shouldReceive('topCities')->andReturn(collect());
        });
        $this->mock(AiService::class, function ($mock) {
            $mock->shouldReceive('isGloballyEnabled')->andReturn(false);
        });

        if (! Schema::hasTable('landing_pages')) {
            Schema::create('landing_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->nullable();
                $table->string('title')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        $response = $this->get('/saelg-din-bil');

        $response->assertOk();
        $response->assertSee(__('messages.pages.sell_your_car.landing_hero_title'), false);
        $response->assertSee('return_url=', false);
        $response->assertDontSee(__('messages.pages.login.title'), false);
    }

    public function test_guest_show_returns_landing_not_login_redirect(): void
    {
        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('getAuthenticatedUser')->andReturn(null);

        $seo = Mockery::mock(SeoService::class);
        $seo->shouldReceive('getForPage')->with('static', 'sell-your-car')->andReturn(null);

        $controller = new SellYourCarController(
            $auth,
            Mockery::mock(AuditLogService::class),
            Mockery::mock(FileService::class),
            Mockery::mock(DmrLookupAssociationService::class),
            Mockery::mock(SellYourCarSubmissionService::class),
            Mockery::mock(VehicleTrustReportService::class),
            $seo,
        );

        $response = $controller->show(Request::create('/saelg-din-bil', 'GET'));

        $this->assertSame('sell-your-car-landing', $response->name());
        $this->assertStringContainsString('return_url=', $response['loginUrl']);
        $this->assertStringContainsString('saelg-din-bil', urldecode($response['loginUrl']));
        $this->assertSame(route('for-dealers.landing'), $response['dealerLandingUrl']);
    }

    public function test_login_view_includes_return_url(): void
    {
        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('getAuthenticatedUser')->andReturn(null);
        $this->app->instance(AuthService::class, $auth);

        $request = Request::create('/auth/login', 'GET', [
            'return_url' => '/saelg-din-bil',
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $controller = $this->app->make(\App\Http\Controllers\AuthPageController::class);
        $view = $controller->showLogin($request);

        $this->assertSame('auth.login', $view->name());
        $this->assertSame('/saelg-din-bil', $view['returnUrl']);
        $this->assertSame('/saelg-din-bil', $request->session()->get('url.intended'));
    }

    public function test_authenticate_web_passes_return_url(): void
    {
        $auth = Mockery::mock(AuthService::class);
        $auth->shouldReceive('getAuthenticatedUser')->andReturn(null);

        $request = Request::create('/favoritter', 'GET');
        $request->setLaravelSession($this->app['session.store']);

        $response = (new AuthenticateWeb($auth))->handle($request, fn () => new Response());

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('/auth/login', $response->headers->get('Location'));
        $this->assertStringContainsString('return_url='.rawurlencode('/favoritter'), $response->headers->get('Location'));
        $this->assertSame('/favoritter', $request->session()->get('url.intended'));
    }
}
