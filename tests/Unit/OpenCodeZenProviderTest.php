<?php

namespace Tests\Unit;

use App\Services\Ai\OpenCodeZenProvider;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OpenCodeZenProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_opencodezen_config_defaults(): void
    {
        $this->assertSame('deepseek-v4-flash-free', config('ai.providers.opencodezen.default_model'));
        $this->assertSame('opencode/1.18.16', config('ai.providers.opencodezen.user_agent'));
    }

    public function test_not_configured_when_disabled(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')
            ->with('ai', 'opencodezen_enabled', false)
            ->andReturn('false');

        $provider = new OpenCodeZenProvider($settings);

        $this->assertFalse($provider->isEnabled());
        $this->assertFalse($provider->isConfigured());
        $this->assertSame('opencodezen', $provider->getName());
    }

    public function test_complete_posts_to_zen_chat_completions(): void
    {
        Http::fake([
            'https://opencode.ai/zen/v1/chat/completions' => Http::response([
                'id' => 'gen-1',
                'model' => 'deepseek-v4-flash-free',
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Hello from Zen']],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 4],
            ], 200),
        ]);

        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')
            ->with('ai', 'opencodezen_enabled', false)
            ->andReturn('true');
        $settings->shouldReceive('get')
            ->with('ai', 'opencodezen_api_key')
            ->andReturn('oc-test-key');
        $settings->shouldReceive('get')
            ->with('ai', 'opencodezen_model')
            ->andReturn('opencode/deepseek-v4-flash-free');
        $settings->shouldReceive('get')
            ->with('ai', 'max_tokens', Mockery::any())
            ->andReturn(1200);
        $settings->shouldReceive('get')
            ->with('ai', 'temperature', Mockery::any())
            ->andReturn(0.7);

        $provider = new OpenCodeZenProvider($settings);
        $result = $provider->complete('You are a helpful assistant.', 'Say hello');

        $this->assertSame('Hello from Zen', $result->text);
        $this->assertSame('opencodezen', $result->provider);
        $this->assertSame('deepseek-v4-flash-free', $result->model);
        $this->assertSame(14, $result->totalTokens());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://opencode.ai/zen/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer oc-test-key')
                && $request->hasHeader('User-Agent', 'opencode/1.18.16')
                && $request['model'] === 'deepseek-v4-flash-free'
                && $request['messages'][0]['role'] === 'system'
                && $request['messages'][1]['content'] === 'Say hello';
        });
    }
}
