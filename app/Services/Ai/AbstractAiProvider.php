<?php

namespace App\Services\Ai;

use App\Contracts\AiProviderInterface;
use App\Data\AiCompletionResult;
use App\Services\PlatformSettingService;
use Illuminate\Support\Facades\Http;

abstract class AbstractAiProvider implements AiProviderInterface
{
    public function __construct(
        protected PlatformSettingService $platformSettingService,
    ) {}

    abstract protected function settingPrefix(): string;

    abstract protected function defaultModel(): string;

    abstract protected function callApi(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens, float $temperature): AiCompletionResult;

    public function isEnabled(): bool
    {
        $prefix = $this->settingPrefix();
        $enabled = $this->platformSettingService->get('ai', $prefix.'_enabled', false);

        return $enabled === true || $enabled === 'true' || $enabled === '1';
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled() && $this->apiKey() !== null;
    }

    protected function apiKey(): ?string
    {
        $key = $this->platformSettingService->get('ai', $this->settingPrefix().'_api_key');

        return is_string($key) && $key !== '' && $key !== '********' ? $key : null;
    }

    protected function configuredModel(): string
    {
        $model = $this->platformSettingService->get('ai', $this->settingPrefix().'_model');

        return is_string($model) && $model !== '' ? $model : $this->defaultModel();
    }

    protected function maxTokens(): int
    {
        $value = $this->platformSettingService->get('ai', 'max_tokens', config('ai.default_max_tokens', 1200));

        return max(256, (int) $value);
    }

    protected function temperature(): float
    {
        $value = $this->platformSettingService->get('ai', 'temperature', config('ai.default_temperature', 0.7));

        return min(1.0, max(0.0, (float) $value));
    }

    public function testConnection(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => __('messages.api.ai_provider_disabled', ['provider' => $this->getName()]),
            ];
        }

        $apiKey = $this->apiKey();
        if (! $apiKey) {
            return [
                'success' => false,
                'message' => __('messages.api.ai_provider_not_configured', ['provider' => $this->getName()]),
            ];
        }

        try {
            $result = $this->callApi(
                $apiKey,
                $this->configuredModel(),
                'You are a helpful assistant.',
                'Reply with exactly: OK',
                16,
                0
            );

            if (trim($result->text) === '') {
                return [
                    'success' => false,
                    'message' => __('messages.api.ai_provider_empty_response', ['provider' => $this->getName()]),
                ];
            }

            return [
                'success' => true,
                'message' => __('messages.api.ai_connection_ok', ['provider' => $this->getName()]),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function complete(string $systemPrompt, string $userPrompt, array $options = []): AiCompletionResult
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException(__('messages.api.ai_provider_disabled', ['provider' => $this->getName()]));
        }

        $apiKey = $this->apiKey();
        if (! $apiKey) {
            throw new \RuntimeException(__('messages.api.ai_provider_not_configured', ['provider' => $this->getName()]));
        }

        $result = $this->callApi(
            $apiKey,
            (string) ($options['model'] ?? $this->configuredModel()),
            $systemPrompt,
            $userPrompt,
            (int) ($options['max_tokens'] ?? $this->maxTokens()),
            (float) ($options['temperature'] ?? $this->temperature()),
        );

        $this->assertNonEmptyResult($result);

        return $result;
    }

    protected function assertNonEmptyResult(AiCompletionResult $result): void
    {
        if (trim($result->text) === '') {
            throw new \RuntimeException(__('messages.api.ai_provider_empty_response', ['provider' => $this->getName()]));
        }
    }

    protected function httpClient(string $apiKey): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(60)->withToken($apiKey);
    }
}
