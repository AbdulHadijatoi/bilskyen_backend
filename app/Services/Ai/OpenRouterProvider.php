<?php

namespace App\Services\Ai;

use App\Data\AiCompletionResult;

class OpenRouterProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'openrouter';
    }

    protected function settingPrefix(): string
    {
        return 'openrouter';
    }

    protected function defaultModel(): string
    {
        return config('ai.providers.openrouter.default_model', 'openrouter/free');
    }

    protected function callApi(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens, float $temperature): AiCompletionResult
    {
        $response = $this->timedRequest($apiKey)
            ->withHeaders([
                'HTTP-Referer' => (string) config('app.url'),
                'X-OpenRouter-Title' => (string) config('app.name'),
            ])
            ->post('https://openrouter.ai/api/v1/chat/completions', [
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

        return $fallback !== '' ? $fallback : 'OpenRouter request failed';
    }
}
