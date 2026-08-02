<?php

namespace Tests\Feature;

use App\Http\Controllers\CarAdvisorController;
use App\Http\Controllers\HomeController;
use App\Services\AiService;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\CarAdvisorService;
use App\Services\FaqContentService;
use App\Services\Finance\FinanceCalculatorService;
use App\Services\LookupService;
use App\Services\MailService;
use App\Services\MarketPricingService;
use App\Services\Marketing\MetaConversionsApiService;
use App\Services\PageContentService;
use App\Services\PlatformSettingService;
use App\Services\SeoService;
use App\Services\VehicleDetailPresentationService;
use App\Services\VehicleListingPresentationService;
use App\Services\VehicleService;
use App\Services\VehicleTrustReportService;
use App\Services\VehicleViewService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class CarAdvisorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_car_advisor_returns_recommendations(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(true);
        $this->app->instance(AiService::class, $ai);

        $service = Mockery::mock(CarAdvisorService::class);
        $service->shouldReceive('advise')
            ->once()
            ->withArgs(function (string $message, string $locale, array $history) {
                return $message === 'elbil under 200000' && $locale === 'da';
            })
            ->andReturn([
                'summary' => 'Elbil under budget',
                'profile' => [
                    'budget_max' => 200000,
                    'use_case' => 'city',
                    'needs' => ['electric', 'city'],
                    'priorities' => ['budget'],
                    'summary' => 'Elbil under budget',
                ],
                'filters' => ['price_to' => 200000],
                'labels' => [['key' => 'price_to', 'label' => 'Max 200.000 kr']],
                'browse_url' => 'https://example.test/biler?price_to=200000',
                'recommendations' => [
                    [
                        'id' => 1,
                        'slug' => 'test-ev',
                        'title' => 'Test EV',
                        'match_percent' => 88,
                        'explanation' => 'Fits city + budget',
                    ],
                ],
                'candidate_count' => 12,
                'relaxed_filters' => false,
                'provider' => 'openai',
                'fallback_explain' => false,
            ]);
        $this->app->instance(CarAdvisorService::class, $service);

        $response = $this->postJson('/api/v1/ai/car-advisor', [
            'message' => 'elbil under 200000',
            'locale' => 'da',
            'website' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.summary', 'Elbil under budget');
        $response->assertJsonPath('data.recommendations.0.match_percent', 88);
        $response->assertJsonPath('data.filters.price_to', 200000);
    }

    public function test_car_advisor_rejects_when_ai_disabled(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(false);
        $this->app->instance(AiService::class, $ai);

        $this->postJson('/api/v1/ai/car-advisor', [
            'message' => 'familiebil under 200000',
            'website' => '',
        ])->assertStatus(422);
    }

    public function test_car_advisor_validates_message(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(true);
        $this->app->instance(AiService::class, $ai);

        $this->postJson('/api/v1/ai/car-advisor', [
            'website' => '',
        ])->assertStatus(422);
    }

    public function test_car_advisor_examples_via_controller(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(true);

        $service = Mockery::mock(CarAdvisorService::class);
        $service->shouldReceive('examplePrompts')
            ->once()
            ->with('da')
            ->andReturn(['Example one']);

        $controller = new CarAdvisorController($ai, $service);
        $response = $controller->examples(Request::create('/api/v1/ai/car-advisor/examples', 'GET', [
            'locale' => 'da',
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame(['Example one'], $payload['data']['examples']);
        $this->assertTrue($payload['data']['enabled']);
    }

    public function test_find_perfect_car_page_view(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(true);

        $advisor = Mockery::mock(CarAdvisorService::class);
        $advisor->shouldReceive('examplePrompts')->andReturn(['Demo prompt']);
        $this->app->instance(CarAdvisorService::class, $advisor);

        $controller = new HomeController(
            Mockery::mock(AuthService::class),
            Mockery::mock(VehicleService::class),
            Mockery::mock(AuditLogService::class),
            Mockery::mock(PageContentService::class),
            Mockery::mock(LookupService::class),
            Mockery::mock(SeoService::class),
            Mockery::mock(VehicleDetailPresentationService::class),
            Mockery::mock(VehicleViewService::class),
            Mockery::mock(MailService::class),
            Mockery::mock(FinanceCalculatorService::class),
            Mockery::mock(VehicleTrustReportService::class),
            Mockery::mock(MarketPricingService::class),
            Mockery::mock(VehicleListingPresentationService::class),
            Mockery::mock(MetaConversionsApiService::class),
            Mockery::mock(FaqContentService::class),
            Mockery::mock(PlatformSettingService::class),
            $ai,
        );

        $view = $controller->showFindPerfectCar();
        $this->assertSame('find-perfect-car', $view->name());
        $this->assertTrue($view->getData()['publicAiEnabled']);
        $this->assertSame(['Demo prompt'], $view->getData()['advisorExamples']);
    }
}
