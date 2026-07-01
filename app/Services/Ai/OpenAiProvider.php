<?php

namespace App\Services\Ai;

use App\Data\AiCompletionResult;
use Illuminate\Support\Facades\Http;

class OpenAiProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'openai';
    }

    protected function settingPrefix(): string
    {
        return 'openai';
    }

    protected function defaultModel(): string
    {
        return config('ai.providers.openai.default_model', 'gpt-4o-mini');
    }

    protected function callApi(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens, float $temperature): AiCompletionResult
    {
        $response = Http::timeout(60)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
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

        return $fallback !== '' ? $fallback : 'OpenAI request failed';
    }
}
