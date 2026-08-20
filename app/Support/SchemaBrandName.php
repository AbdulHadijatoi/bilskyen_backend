<?php

namespace App\Support;

class SchemaBrandName
{
    /**
     * @var array<string, string>
     */
    private const ALIASES = [
        'VW' => 'Volkswagen',
        'MB' => 'Mercedes-Benz',
        'MERC' => 'Mercedes-Benz',
        'GM' => 'General Motors',
    ];

    public static function normalize(?string $brand): ?string
    {
        $brand = trim((string) $brand);
        if ($brand === '') {
            return null;
        }

        $key = strtoupper($brand);
        if (isset(self::ALIASES[$key])) {
            return self::ALIASES[$key];
        }

        if ($brand === $key && strlen($brand) > 3 && preg_match('/^[A-Z0-9 \-]+$/', $brand)) {
            return mb_convert_case(mb_strtolower($brand, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return $brand;
    }
}
