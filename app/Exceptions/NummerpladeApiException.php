<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for Nummerplade API failures
 * Standardized error structure for external API failures
 */
class NummerpladeApiException extends Exception
{
    public const CODE_TIMEOUT = 'TIMEOUT';
    public const CODE_RATE_LIMIT = 'RATE_LIMIT';
    public const CODE_INVALID_INPUT = 'INVALID_INPUT';
    public const CODE_SERVICE_DOWN = 'SERVICE_DOWN';
    public const CODE_UNKNOWN = 'UNKNOWN';

    protected string $errorCode;
    protected bool $retryable;

    public function __construct(
        string $message = 'External vehicle data unavailable',
        string $errorCode = self::CODE_UNKNOWN,
        bool $retryable = false,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->retryable = $retryable;
    }

    /**
     * Get standardized error structure
     */
    public function toArray(): array
    {
        return [
            'status' => 'error',
            'message' => $this->getMessage(),
            'source' => 'nummerplade',
            'retryable' => $this->retryable,
            'code' => $this->errorCode,
        ];
    }

    /**
     * Get error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Check if error is retryable
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * Create timeout exception
     */
    public static function timeout(string $message = 'Request to Nummerplade API timed out'): self
    {
        return new self($message, self::CODE_TIMEOUT, true);
    }

    /**
     * Create rate limit exception
     */
    public static function rateLimit(string $message = 'Nummerplade API rate limit exceeded'): self
    {
        return new self($message, self::CODE_RATE_LIMIT, true);
    }

    /**
     * Create invalid input exception
     */
    public static function invalidInput(string $message = 'Invalid registration or VIN provided'): self
    {
        return new self($message, self::CODE_INVALID_INPUT, false);
    }

    /**
     * Create service down exception
     */
    public static function serviceDown(string $message = 'Nummerplade API service is unavailable'): self
    {
        return new self($message, self::CODE_SERVICE_DOWN, true);
    }

    /**
     * Create unknown exception
     */
    public static function unknown(string $message = 'Unknown error occurred with Nummerplade API'): self
    {
        return new self($message, self::CODE_UNKNOWN, false);
    }
}

