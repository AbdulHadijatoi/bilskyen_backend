<?php

namespace App\Exceptions;

use App\Constants\ApiStatusCode;
use RuntimeException;

class AiGenerationException extends RuntimeException
{
    public function __construct(
        string $message,
        private int $statusCode = ApiStatusCode::UNPROCESSABLE_ENTITY,
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
