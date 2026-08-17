<?php

namespace App\Services\Ai;

use App\Data\AiCompletionResult;
use Illuminate\Support\Facades\Http;

/**
 * Local Ollama provider (OpenAI-compatible /v1/chat/completions).
 * Intended for local development/testing only — no cloud API key required.
 */
class OllamaProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'ollama';
    }

    protected function settingPrefix(): string
    {
        return 'ollama';
    }

    protected function defaultModel(): string
    {
        return config('ai.providers.ollama.default_model', 'llama3.2');
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled();
    }

    protected function apiKey(): ?string
    {
        $key = $this->platformSettingService->get('ai', 'ollama_api_key');
        if (is_string($key) && $key !== '' && $key !== '********') {
            return $key;
        }

        // Local Ollama does not require a key; return a sentinel so parent complete() passes.
        return $this->isEnabled() ? 'ollama-local' : null;
    }

    protected function baseUrl(): string
    {
        $url = $this->platformSettingService->get(
            'ai',
            'ollama_base_url',
            config('ai.providers.ollama.base_url', 'http://127.0.0.1:11434')
        );

        $url = is_string($url) && trim($url) !== '' ? trim($url) : 'http://127.0.0.1:11434';

        return rtrim($url, '/');
    }

    public function testConnection(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => __('messages.api.ai_provider_disabled', ['provider' => $this->getName()]),
            ];
        }

        try {
            $tags = Http::timeout(5)->get($this->baseUrl().'/api/tags');
            if (! $tags->successful()) {
                return [
                    'success' => false,
                    'message' => 'Ollama is not reachable at '.$this->baseUrl(),
                ];
            }

            $result = $this->callApi(
                $this->apiKey() ?? 'ollama-local',
                $this->configuredModel(),
                'You are a helpful assistant.',
                'Reply with exactly: OK',
                32,
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

    protected function callApi(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens, float $temperature): AiCompletionResult
    {
        $request = $this->timedRequest($apiKey);

        $response = $request->post($this->baseUrl().'/v1/chat/completions', [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->extractError($response->json(), $response->body()));
        }

        $data = $response->json();
        $text = (string) ($data['choices'][0]['message']['content'] ?? '');

        return new AiCompletionResult(
            text: trim($text),
            provider: $this->getName(),
            model: (string) ($data['model'] ?? $model),
            promptTokens: (int) ($data['usage']['prompt_tokens'] ?? 0),
            completionTokens: (int) ($data['usage']['completion_tokens'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractError(?array $json, string $fallback): string
    {
        if (isset($json['error']['message'])) {
            return (string) $json['error']['message'];
        }
        if (isset($json['error']) && is_string($json['error'])) {
            return $json['error'];
        }

        return $fallback !== '' ? $fallback : 'Ollama request failed';
    }
}
