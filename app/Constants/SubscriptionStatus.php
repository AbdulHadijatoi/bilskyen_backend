<?php

namespace App\Constants;

/**
 * Subscription status constants for API validation
 */
class SubscriptionStatus
{
    public const TRIAL = 1;
    public const ACTIVE = 2;
    public const EXPIRED = 3;
    public const CANCELED = 4;
    public const SCHEDULED = 5;

    /**
     * Get all valid status IDs
     */
    public static function values(): array
    {
        return [
            self::TRIAL,
            self::ACTIVE,
            self::EXPIRED,
            self::CANCELED,
            self::SCHEDULED,
        ];
    }

    /**
     * Check if status ID is valid
     */
    public static function isValid(int $statusId): bool
    {
        return in_array($statusId, self::values(), true);
    }
}

