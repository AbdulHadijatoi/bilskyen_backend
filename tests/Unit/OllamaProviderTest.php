<?php

namespace Tests\Unit;

use App\Services\Ai\OllamaProvider;
use App\Services\PlatformSettingService;
use Mockery;
use Tests\TestCase;

class OllamaProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_configured_when_enabled_without_api_key(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')
            ->with('ai', 'ollama_enabled', false)
            ->andReturn('true');
        $settings->shouldReceive('get')
            ->with('ai', 'ollama_api_key')
            ->andReturn(null);

        $provider = new OllamaProvider($settings);

        $this->assertTrue($provider->isEnabled());
        $this->assertTrue($provider->isConfigured());
        $this->assertSame('ollama', $provider->getName());
    }

    public function test_not_configured_when_disabled(): void
    {
        $settings = Mockery::mock(PlatformSettingService::class);
        $settings->shouldReceive('get')
            ->with('ai', 'ollama_enabled', false)
            ->andReturn('false');

        $provider = new OllamaProvider($settings);

        $this->assertFalse($provider->isEnabled());
        $this->assertFalse($provider->isConfigured());
    }

    public function test_ollama_config_defaults(): void
    {
        $this->assertSame('llama3.2', config('ai.providers.ollama.default_model'));
        $this->assertSame('http://127.0.0.1:11434', config('ai.providers.ollama.base_url'));
    }
}
