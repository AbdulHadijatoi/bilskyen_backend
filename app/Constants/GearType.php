<?php

namespace App\Constants;

/**
 * Transmission constants for API validation
 */
class GearType
{
    public const MANUAL = 1;
    public const AUTOMATIC = 2;

    /**
     * Get all valid gear type IDs
     */
    public static function values(): array
    {
        return [
            self::MANUAL,
            self::AUTOMATIC,
        ];
    }

    /**
     * Get all valid transmission names (for string-based validation)
     */
    public static function names(): array
    {
        return [
            'manual',
            'automatic',
        ];
    }

    /**
     * Map transmission name to ID
     */
    public static function nameToId(string $name): ?int
    {
        $mapping = [
            'manual' => self::MANUAL,
            'automatic' => self::AUTOMATIC,
        ];

        return $mapping[strtolower($name)] ?? null;
    }

    /**
     * Check if transmission ID is valid
     */
    public static function isValid(int $transmissionId): bool
    {
        return in_array($transmissionId, self::values(), true);
    }

    /**
     * Check if transmission name is valid
     */
    public static function isValidName(string $name): bool
    {
        return in_array(strtolower($name), self::names(), true);
    }
}
