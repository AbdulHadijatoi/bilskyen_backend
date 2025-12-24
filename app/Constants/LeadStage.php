<?php

namespace App\Constants;

/**
 * Lead stage constants for API validation
 */
class LeadStage
{
    public const NEW = 1;
    public const CONTACTED = 2;
    public const QUALIFIED = 3;
    public const QUOTED = 4;
    public const NEGOTIATING = 5;
    public const WON = 6;
    public const LOST = 7;

    /**
     * Get all valid stage IDs
     */
    public static function values(): array
    {
        return [
            self::NEW,
            self::CONTACTED,
            self::QUALIFIED,
            self::QUOTED,
            self::NEGOTIATING,
            self::WON,
            self::LOST,
        ];
    }

    /**
     * Check if stage ID is valid
     */
    public static function isValid(int $stageId): bool
    {
        return in_array($stageId, self::values(), true);
    }
}

