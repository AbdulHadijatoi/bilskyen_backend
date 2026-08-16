<?php

namespace Tests\Unit;

use App\Services\Ai\OpenRouterProvider;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OpenRouterProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_openrouter_config_defaults(): void
    {
        $this->assertSame('openrouter/free', config('ai.providers.openrouter.default_model'));
    }

    public function test_not_configured_when_disabled(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')
            ->with('ai', 'openrouter_enabled', false)
            ->andReturn('false');

        $provider = new OpenRouterProvider($settings);

        $this->assertFalse($provider->isEnabled());
        $this->assertFalse($provider->isConfigured());
        $this->assertSame('openrouter', $provider->getName());
    }

    public function test_complete_posts_to_openrouter_chat_completions(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'id' => 'gen-1',
                'model' => 'openrouter/free',
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Hello from OpenRouter']],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 4],
            ], 200),
        ]);

        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')
            ->with('ai', 'openrouter_enabled', false)
            ->andReturn('true');
        $settings->shouldReceive('get')
            ->with('ai', 'openrouter_api_key')
            ->andReturn('sk-or-test');
        $settings->shouldReceive('get')
            ->with('ai', 'openrouter_model')
            ->andReturn('openrouter/free');
        $settings->shouldReceive('get')
            ->with('ai', 'max_tokens', Mockery::any())
            ->andReturn(1200);
        $settings->shouldReceive('get')
            ->with('ai', 'temperature', Mockery::any())
            ->andReturn(0.7);

        $provider = new OpenRouterProvider($settings);
        $result = $provider->complete('You are a helpful assistant.', 'Say hello');

        $this->assertSame('Hello from OpenRouter', $result->text);
        $this->assertSame('openrouter', $result->provider);
        $this->assertSame('openrouter/free', $result->model);
        $this->assertSame(14, $result->totalTokens());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer sk-or-test')
                && $request['model'] === 'openrouter/free'
                && $request['messages'][0]['role'] === 'system'
                && $request['messages'][1]['content'] === 'Say hello';
        });
    }
}
