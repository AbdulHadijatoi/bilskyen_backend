<?php

namespace Tests\Feature;

use App\Services\AiSearchParseService;
use App\Services\AiService;
use Mockery;
use Tests\TestCase;

class AiSearchParseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_parse_returns_filters_from_service(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(true);
        $this->app->instance(AiService::class, $ai);

        $service = Mockery::mock(AiSearchParseService::class);
        $service->shouldReceive('parse')
            ->once()
            ->with('elbil under 200000', 'da')
            ->andReturn([
                'filters' => ['price_to' => 200000, 'search' => 'Electric'],
                'labels' => [
                    ['key' => 'price_to', 'label' => 'Max 200.000 kr'],
                ],
                'query' => 'elbil under 200000',
                'expanded_query' => 'Electric under 200000',
                'provider' => 'openai',
                'cached' => false,
                'fallback' => false,
            ]);
        $this->app->instance(AiSearchParseService::class, $service);

        $log = Mockery::mock(\App\Services\SearchQueryLogService::class);
        $log->shouldReceive('log')->once();
        $this->app->instance(\App\Services\SearchQueryLogService::class, $log);

        $response = $this->postJson('/api/v1/ai/search-parse', [
            'query' => 'elbil under 200000',
            'locale' => 'da',
            'website' => '',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.filters.price_to', 200000);
        $response->assertJsonPath('data.ai_search', 1);
    }

    public function test_search_parse_rejects_when_ai_not_configured(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(false);
        $this->app->instance(AiService::class, $ai);

        $this->postJson('/api/v1/ai/search-parse', [
            'query' => 'elbil under 200000',
            'website' => '',
        ])->assertStatus(422);
    }

    public function test_search_parse_validates_query(): void
    {
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('isGloballyEnabled')->andReturn(true);
        $this->app->instance(AiService::class, $ai);

        $this->postJson('/api/v1/ai/search-parse', [
            'website' => '',
        ])->assertStatus(422);
    }

    public function test_suggest_endpoint_returns_structure(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SeoRedirectMiddleware::class);

        $service = Mockery::mock(AiSearchParseService::class);
        $service->shouldReceive('suggest')
            ->once()
            ->andReturn([
                'brands' => [['id' => 1, 'name' => 'Volkswagen']],
                'models' => [],
                'examples' => ['Elbil under 200.000'],
            ]);
        $this->app->instance(AiSearchParseService::class, $service);

        $this->getJson('/api/v1/search/suggest?q=volk&locale=da')
            ->assertOk()
            ->assertJsonPath('data.brands.0.name', 'Volkswagen');
    }

    public function test_examples_endpoint(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\SeoRedirectMiddleware::class);

        $this->getJson('/api/v1/search/examples?locale=da')
            ->assertOk()
            ->assertJsonStructure(['data' => ['examples']]);
    }

    public function test_saved_search_requires_auth(): void
    {
        $this->postJson('/api/v1/saved-searches', [
            'name' => 'My search',
            'filters' => ['price_to' => 100000],
        ])->assertUnauthorized();
    }
}
