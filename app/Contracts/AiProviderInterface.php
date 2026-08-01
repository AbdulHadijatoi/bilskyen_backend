<?php

namespace App\Contracts;

use App\Data\AiCompletionResult;

interface AiProviderInterface
{
    public function getName(): string;

    public function isEnabled(): bool;

    /**
     * Provider is toggled on and has a usable API key configured.
     */
    public function isConfigured(): bool;

    /**
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array;

    /**
     * @param  array{max_tokens?: int, temperature?: float, model?: string}  $options
     */
    public function complete(string $systemPrompt, string $userPrompt, array $options = []): AiCompletionResult;
}
