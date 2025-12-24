<?php

namespace App\Constants;

use App\Models\VehicleListStatus as VehicleListStatusModel;

/**
 * Vehicle list status constants for API validation
 */
class VehicleListStatus
{
    public const DRAFT = 1;
    public const PUBLISHED = 2;
    public const SOLD = 3;
    public const ARCHIVED = 4;

    /**
     * Get all valid status IDs
     */
    public static function values(): array
    {
        return [
            self::DRAFT,
            self::PUBLISHED,
            self::SOLD,
            self::ARCHIVED,
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
            'sold',
            'archived',
        ];
    }

    /**
     * Map status name to ID
     */
    public static function nameToId(string $name): ?int
    {
        $mapping = [
            'draft' => self::DRAFT,
            'published' => self::PUBLISHED,
            'sold' => self::SOLD,
            'archived' => self::ARCHIVED,
        ];

        return $mapping[strtolower($name)] ?? null;
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

