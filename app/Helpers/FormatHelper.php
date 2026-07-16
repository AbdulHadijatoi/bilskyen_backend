<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Format currency value
     *
     * @param float|null $amount
     * @param string|null $currency Currency code (default: DKK)
     * @return string
     */
    public static function formatCurrency(?float $amount, ?string $currency = 'DKK'): string
    {
        // Handle null amount
        if ($amount === null) {
            return __('messages.common.not_available');
        }

        if ($currency === 'DKK') {
            // Format Danish Krone with comma as thousands separator and dot as decimal separator
            // Show decimals only when they exist and are non-zero
            $hasDecimals = ($amount != floor($amount));
            if ($hasDecimals) {
                // Format with 2 decimals, remove trailing zeros
                $formatted = rtrim(rtrim(number_format($amount, 2, '.', ','), '0'), '.');
            } else {
                // Format without decimals
                $formatted = number_format($amount, 0, '.', ',');
            }
            return $formatted . ' kr.';
        }

        // For other currencies, use standard formatting with comma as thousands separator and dot as decimal separator
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }

    /**
     * Whether a CVR is safe to show on public pages (not placeholder/test data).
     */
    public static function isValidPublicCvr(?string $cvr): bool
    {
        if ($cvr === null) {
            return false;
        }

        $trimmed = trim($cvr);
        if ($trimmed === '' || strcasecmp($trimmed, '#') === 0) {
            return false;
        }

        if (preg_match('/^pending/i', $trimmed)) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '' || preg_match('/^0+$/', $digits)) {
            return false;
        }

        // Obviously fake / repeated sequences used in test data
        if (in_array($digits, ['123123123123', '00000000', '11111111', '1234567890'], true)) {
            return false;
        }

        if (preg_match('/^(123){3,}$/', $digits) || preg_match('/^(0123)+$/', $digits)) {
            return false;
        }

        // Danish CVR is typically 8 digits; allow 8–10 after stripping
        if (strlen($digits) < 8 || strlen($digits) > 10) {
            return false;
        }

        return true;
    }

    /**
     * Format phone number
     * Currently returns as-is, can be enhanced for specific formats
     *
     * @param string $phone
     * @return string
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove any non-digit characters except + at the start
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // If it starts with +, keep it
        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        return $cleaned;
    }

    /**
     * Format date consistently
     *
     * @param Carbon|string $date
     * @param string|null $format Date format (default: 'Y-m-d')
     * @return string
     */
    public static function formatDate(Carbon|string $date, ?string $format = 'Y-m-d'): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format($format);
    }

    /**
     * Whether an equipment/feature label is safe to show (skip bare numeric IDs).
     */
    public static function isDisplayableFeatureLabel(?string $label): bool
    {
        $label = trim((string) $label);
        if ($label === '') {
            return false;
        }

        // Skip keys that are only digits / dots / dashes (equipment ID leaks)
        if (preg_match('/^[0-9.\-]+$/', $label)) {
            return false;
        }

        return true;
    }

    /**
     * Format a vehicle listing title with consistent capitalization.
     */
    public static function formatListingTitle(?string $title): string
    {
        $title = trim((string) $title);
        if ($title === '') {
            return '';
        }

        // Prefer mb_convert_case for UTF-8 brand/model names
        if (function_exists('mb_convert_case')) {
            return mb_convert_case(mb_strtolower($title, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($title));
    }

    /**
     * Build a listing title from brand and model names.
     */
    public static function generateListingTitleFromBrandAndModel(?string $brandName, ?string $modelName): string
    {
        $parts = array_values(array_filter([
            trim((string) $brandName),
            trim((string) $modelName),
        ], static fn (string $part): bool => $part !== ''));

        return self::formatListingTitle(implode(' ', $parts));
    }

    /**
     * Format a short location line for vehicle cards (postcode + city/address).
     */
    public static function formatListingLocation(?string $address = null, ?string $postcode = null, ?string $city = null): string
    {
        $parts = [];
        $postcode = trim((string) $postcode);
        $city = trim((string) $city);
        $address = trim((string) $address);

        if ($postcode !== '') {
            $parts[] = $postcode;
        }
        if ($city !== '') {
            $parts[] = $city;
        } elseif ($address !== '') {
            // Prefer city when available; otherwise show address without duplicating postcode
            $parts[] = $address;
        }

        return implode(' ', $parts);
    }

    /**
     * Format date for display (human-readable format, locale-aware when possible).
     *
     * @param Carbon|string $date
     * @return string
     */
    public static function formatDateDisplay(Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $locale = app()->getLocale();
        try {
            return $date->locale($locale)->isoFormat('D MMM YYYY');
        } catch (\Throwable) {
            return $date->format('d M Y');
        }
    }

    /**
     * Format month/year for listing badges (e.g. first registration).
     */
    public static function formatMonthYear(Carbon|string $date): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $locale = app()->getLocale();
        try {
            return $date->locale($locale)->isoFormat('MMM YYYY');
        } catch (\Throwable) {
            return $date->format('M Y');
        }
    }

    /**
     * Format model/spec definition values with inferred units when the source value is unitless.
     */
    public static function formatVehicleSpecValue(?string $name, mixed $value): string
    {
        $label = trim((string) $name);
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        // Preserve values that already carry explicit units/text.
        if (preg_match('/[a-zA-Z%°\/]/u', $raw)) {
            return $raw;
        }

        if (! is_numeric($raw)) {
            return $raw;
        }

        $numeric = (float) $raw;
        $formatted = fmod($numeric, 1.0) === 0.0
            ? number_format((int) round($numeric), 0, '.', ',')
            : rtrim(rtrim(number_format($numeric, 2, '.', ','), '0'), '.');

        $normalizedLabel = mb_strtolower($label, 'UTF-8');

        $unitMatchers = [
            'km/h' => ['top speed', 'max speed', 'maks hastighed', 'maksimum hastighed', 'hastighed'],
            'cm' => ['length', 'width', 'height', 'wheelbase', 'track width', 'bredde', 'længde', 'hojde', 'højde', 'akselafstand'],
            'mm' => ['ground clearance', 'frigang'],
            'kg' => ['weight', 'total weight', 'kerb weight', 'payload', 'vægt', 'totalvægt', 'egenvægt'],
            'L' => ['tank capacity', 'fuel tank', 'boot volume', 'bagagerum', 'tank'],
        ];

        foreach ($unitMatchers as $unit => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($normalizedLabel, $needle)) {
                    return $formatted . ' ' . $unit;
                }
            }
        }

        return $formatted;
    }

    /**
     * Format datetime consistently
     *
     * @param Carbon|string $dateTime
     * @param string|null $format DateTime format (default: 'Y-m-d H:i:s')
     * @return string
     */
    public static function formatDateTime(Carbon|string $dateTime, ?string $format = 'Y-m-d H:i:s'): string
    {
        if (is_string($dateTime)) {
            $dateTime = Carbon::parse($dateTime);
        }

        return $dateTime->format($format);
    }
}

