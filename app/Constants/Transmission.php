<?php

namespace App\Constants;

/**
 * Transmission constants for API validation
 */
class Transmission
{
    public const MANUAL = 1;
    public const AUTOMATIC = 2;
    public const CVT = 3;
    public const SEMI_AUTOMATIC = 4;

    /**
     * Get all valid transmission IDs
     */
    public static function values(): array
    {
        return [
            self::MANUAL,
            self::AUTOMATIC,
            self::CVT,
            self::SEMI_AUTOMATIC,
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
            'cvt',
            'semi-automatic',
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
            'cvt' => self::CVT,
            'semi-automatic' => self::SEMI_AUTOMATIC,
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
