<?php

namespace App\Services\Ai;

use App\Data\AiCompletionResult;
use Illuminate\Support\Facades\Http;

class AnthropicProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'anthropic';
    }

    protected function settingPrefix(): string
    {
        return 'anthropic';
    }

    protected function defaultModel(): string
    {
        return config('ai.providers.anthropic.default_model', 'claude-3-5-haiku-latest');
    }

    protected function callApi(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens, float $temperature): AiCompletionResult
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->extractError($response->json(), $response->body()));
        }

        $data = $response->json();
        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        return new AiCompletionResult(
            text: trim($text),
            provider: $this->getName(),
            model: (string) ($data['model'] ?? $model),
            promptTokens: (int) ($data['usage']['input_tokens'] ?? 0),
            completionTokens: (int) ($data['usage']['output_tokens'] ?? 0),
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

        return $fallback !== '' ? $fallback : 'Anthropic request failed';
    }
}
