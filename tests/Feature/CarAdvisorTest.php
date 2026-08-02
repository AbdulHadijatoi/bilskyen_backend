<?php

namespace Tests\Feature;

use App\Http\Controllers\CarAdvisorController;
use App\Services\AiService;
use App\Services\CarAdvisorService;
use App\Services\SearchQueryLogService;
use App\Services\SuggestionService;
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

        $log = Mockery::mock(SearchQueryLogService::class);
        $log->shouldReceive('log')->once();
        $this->app->instance(SearchQueryLogService::class, $log);

        $response = $this->postJson('/api/v1/ai/car-advisor', [
            'message' => 'elbil under 200000',
            'locale' => 'da',
            'website' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.summary', 'Elbil under budget');
        $response->assertJsonPath('data.recommendations.0.match_percent', 88);
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

        $advisor = Mockery::mock(CarAdvisorService::class);
        $suggestions = Mockery::mock(SuggestionService::class);
        $suggestions->shouldReceive('examplePrompts')
            ->once()
            ->with('da')
            ->andReturn(['Example one']);
        $log = Mockery::mock(SearchQueryLogService::class);

        $controller = new CarAdvisorController($ai, $advisor, $suggestions, $log);
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

        $suggestions = Mockery::mock(SuggestionService::class);
        $suggestions->shouldReceive('examplePrompts')->andReturn(['Demo prompt']);
        $this->app->instance(SuggestionService::class, $suggestions);

        $controller = new \App\Http\Controllers\HomeController(
            Mockery::mock(\App\Services\AuthService::class),
            Mockery::mock(\App\Services\VehicleService::class),
            Mockery::mock(\App\Services\AuditLogService::class),
            Mockery::mock(\App\Services\PageContentService::class),
            Mockery::mock(\App\Services\LookupService::class),
            Mockery::mock(\App\Services\SeoService::class),
            Mockery::mock(\App\Services\VehicleDetailPresentationService::class),
            Mockery::mock(\App\Services\VehicleViewService::class),
            Mockery::mock(\App\Services\MailService::class),
            Mockery::mock(\App\Services\Finance\FinanceCalculatorService::class),
            Mockery::mock(\App\Services\VehicleTrustReportService::class),
            Mockery::mock(\App\Services\MarketPricingService::class),
            Mockery::mock(\App\Services\VehicleListingPresentationService::class),
            Mockery::mock(\App\Services\Marketing\MetaConversionsApiService::class),
            Mockery::mock(\App\Services\FaqContentService::class),
            Mockery::mock(\App\Services\PlatformSettingService::class),
            $ai,
        );

        $view = $controller->showFindPerfectCar();
        $this->assertSame('find-perfect-car', $view->name());
        $this->assertTrue($view->getData()['publicAiEnabled']);
        $this->assertSame(['Demo prompt'], $view->getData()['advisorExamples']);
    }
}
