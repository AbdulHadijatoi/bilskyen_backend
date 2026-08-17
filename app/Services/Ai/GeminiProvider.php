<?php

namespace App\Services\Ai;

use App\Data\AiCompletionResult;
use Illuminate\Support\Facades\Http;

class GeminiProvider extends AbstractAiProvider
{
    public function getName(): string
    {
        return 'gemini';
    }

    protected function settingPrefix(): string
    {
        return 'gemini';
    }

    protected function defaultModel(): string
    {
        return config('ai.providers.gemini.default_model', 'gemini-1.5-flash');
    }

    protected function callApi(string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens, float $temperature): AiCompletionResult
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            urlencode($model)
        );

        $response = Http::timeout($this->requestTimeout())
            ->connectTimeout(3)
            ->withQueryParameters(['key' => $apiKey])
            ->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $userPrompt]],
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $maxTokens,
                    'temperature' => $temperature,
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException($this->extractError($response->json(), $response->body()));
        }

        $data = $response->json();
        $text = '';
        foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
            $text .= (string) ($part['text'] ?? '');
        }

        $usage = $data['usageMetadata'] ?? [];

        return new AiCompletionResult(
            text: trim($text),
            provider: $this->getName(),
            model: $model,
            promptTokens: (int) ($usage['promptTokenCount'] ?? 0),
            completionTokens: (int) ($usage['candidatesTokenCount'] ?? 0),
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

        return $fallback !== '' ? $fallback : 'Gemini request failed';
    }
}
