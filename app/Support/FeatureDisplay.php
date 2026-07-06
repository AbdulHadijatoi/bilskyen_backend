<?php

namespace App\Support;

use App\Models\Feature;

class FeatureDisplay
{
    public static function label(Feature $feature, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $useDa = str_starts_with($locale, 'da');

        $da = trim((string) ($feature->label_da ?? ''));
        $en = trim((string) ($feature->label_en ?? ''));

        if ($useDa && $da !== '') {
            return $da;
        }

        if ($en !== '') {
            return $en;
        }

        return self::formatKey($feature->key ?? '');
    }

    public static function formatKey(string $key): string
    {
        if ($key === '') {
            return '';
        }

        return collect(explode('_', $key))
            ->map(fn (string $word) => ucfirst(strtolower($word)))
            ->implode(' ');
    }

    public static function formatFeatureValue(Feature $feature, mixed $value, ?string $locale = null): ?string
    {
        $typeId = (int) ($feature->feature_value_type_id ?? 0);

        if ($typeId === 1) {
            if (! self::isTruthy($value)) {
                return null;
            }

            return self::label($feature, $locale);
        }

        if ($typeId === 2 || $typeId === 3) {
            if ($value === null || $value === '') {
                return null;
            }

            return self::label($feature, $locale) . ': ' . $value;
        }

        return null;
    }

    public static function isTruthy(mixed $value): bool
    {
        return $value === true
            || $value === 1
            || $value === '1'
            || $value === 'true';
    }
}
