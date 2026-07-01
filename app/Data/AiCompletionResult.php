<?php

namespace App\Data;

class AiCompletionResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }
}
