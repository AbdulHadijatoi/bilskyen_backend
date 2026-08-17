<?php

namespace App\Services\Ai;

use App\Data\AiCompletionResult;

class OpenCodeZenProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'opencodezen';
    }

    protected function settingPrefix(): string
    {
        return 'opencodezen';
    }

    protected function defaultModel(): string
    {
        return config('ai.providers.opencodezen.default_model', 'deepseek-v4-flash-free');
    }

    protected function callApi(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens, float $temperature): AiCompletionResult
    {
        $response = $this->timedRequest($apiKey)
            ->withUserAgent((string) config('ai.providers.opencodezen.user_agent', 'opencode/1.18.16'))
            ->post('https://opencode.ai/zen/v1/chat/completions', [
                'model' => $this->normalizeModel($model),
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
            model: (string) ($data['model'] ?? $this->normalizeModel($model)),
            promptTokens: (int) ($data['usage']['prompt_tokens'] ?? 0),
            completionTokens: (int) ($data['usage']['completion_tokens'] ?? 0),
        );
    }

    /**
     * OpenCode config uses opencode/<id>; the Zen API expects the bare model id.
     */
    private function normalizeModel(string $model): string
    {
        $model = trim($model);

        return str_starts_with($model, 'opencode/') ? substr($model, strlen('opencode/')) : $model;
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractError(?array $json, string $fallback): string
    {
        if (isset($json['error']['message'])) {
            return (string) $json['error']['message'];
        }

        return $fallback !== '' ? $fallback : 'OpenCode Zen request failed';
    }
}
