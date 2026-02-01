<?php

namespace App\Constants;

/**
 * Lead intent constants for API validation
 */
class LeadIntent
{
    public const LOW = 1;
    public const MEDIUM = 2;
    public const HIGH = 3;
    public const VERY_HIGH = 4;

    /**
     * Get all valid intent IDs
     */
    public static function values(): array
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::VERY_HIGH,
        ];
    }

    /**
     * Check if intent ID is valid
     */
    public static function isValid(int $intentId): bool
    {
        return in_array($intentId, self::values(), true);
    }
}
