<?php

namespace App\Constants;

/**
 * User status constants for API validation
 */
class UserStatus
{
    public const ACTIVE = 1;
    public const INACTIVE = 2;
    public const SUSPENDED = 3;

    /**
     * Get all valid status IDs
     */
    public static function values(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::SUSPENDED,
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

