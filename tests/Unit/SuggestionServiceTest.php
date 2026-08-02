<?php

namespace Tests\Unit;

use App\Services\SuggestionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SuggestionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['da', 'en'] as $locale) {
            foreach ([
                SuggestionService::SURFACE_HOME_CHIPS,
                SuggestionService::SURFACE_SUGGEST_EXAMPLES,
                SuggestionService::SURFACE_ADVISOR_PROMPTS,
                SuggestionService::SURFACE_LIFESTYLE_CHIPS,
            ] as $surface) {
                Cache::forget("suggestions:{$locale}:{$surface}");
            }
        }
    }

    public function test_example_queries_are_locale_aware_with_seed_fallback(): void
    {
        $service = app(SuggestionService::class);
        $da = $service->exampleQueries('da', 'test-seed', 4);
        $en = $service->exampleQueries('en', 'test-seed', 4);

        $this->assertNotEmpty($da);
        $this->assertNotEmpty($en);
        $this->assertTrue(
            collect($da)->contains(fn (string $q) => str_contains(mb_strtolower($q), 'elbil')
                || str_contains(mb_strtolower($q), 'familie')
                || str_contains(mb_strtolower($q), 'golf')
                || str_contains(mb_strtolower($q), 'hybrid'))
        );
        $this->assertTrue(
            collect($en)->contains(fn (string $q) => str_contains(mb_strtolower($q), 'electric')
                || str_contains(mb_strtolower($q), 'family')
                || str_contains(mb_strtolower($q), 'golf')
                || str_contains(mb_strtolower($q), 'hybrid'))
        );
    }

    public function test_sampling_is_deterministic_for_fixed_seed(): void
    {
        $service = app(SuggestionService::class);
        $a = $service->exampleQueries('da', 'same-seed', 3);
        $b = $service->exampleQueries('da', 'same-seed', 3);

        $this->assertSame($a, $b);
    }

    public function test_example_prompts_return_longer_lifestyle_strings(): void
    {
        $service = app(SuggestionService::class);
        $prompts = $service->examplePrompts('en', 'seed', 4);

        $this->assertNotEmpty($prompts);
        $this->assertGreaterThan(40, mb_strlen($prompts[0]));
    }

    public function test_lifestyle_chips_include_href(): void
    {
        $service = app(SuggestionService::class);
        $chips = $service->lifestyleChips('da', 'seed', 2);

        $this->assertNotEmpty($chips);
        $this->assertArrayHasKey('label', $chips[0]);
        $this->assertArrayHasKey('href', $chips[0]);
        $this->assertStringContainsString('find-din-bil', $chips[0]['href']);
    }

    public function test_refresh_populates_cache(): void
    {
        $service = app(SuggestionService::class);
        $count = $service->refresh();

        $this->assertGreaterThan(0, $count);
        $cached = Cache::get($service->cacheKey('da', SuggestionService::SURFACE_HOME_CHIPS));
        $this->assertIsArray($cached);
        $this->assertNotEmpty($cached);
    }
}
