<?php

namespace App\Constants;

/**
 * Page status constants for API validation
 */
class PageStatus
{
    public const DRAFT = 1;
    public const PUBLISHED = 2;

    /**
     * Get all valid status IDs
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::PUBLISHED,
        ];
    }

    /**
     * Get all valid status names (for string-based validation)
     */
    public static function names(): array
    {
        return [
            'draft',
            'published',
        ];
    }

    /**
     * Check if status ID is valid
     */
    public static function isValid(int $statusId): bool
    {
        return in_array($statusId, self::values(), true);
    }

    /**
     * Check if status name is valid
     */
    public static function isValidName(string $name): bool
    {
        return in_array(strtolower($name), self::names(), true);
    }
}

