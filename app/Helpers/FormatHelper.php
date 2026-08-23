<?php

namespace App\Helpers;

use App\Models\Location;
use Carbon\Carbon;

class FormatHelper
{
    public const NEW_LISTING_MAX_DAYS = 7;

    /** @var array<string, string|null> */
    private static array $cityByPostcode = [];

    /** @var array<string, array{latitude: float, longitude: float}|null> */
    private static array $coordsByPostcode = [];

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
     *
     * @param  int|float|string|null  $cvr
     */
    public static function isValidPublicCvr(mixed $cvr): bool
    {
        if ($cvr === null || $cvr === '') {
            return false;
        }

        if (is_int($cvr) || is_float($cvr)) {
            $cvr = (string) $cvr;
        }

        if (! is_string($cvr)) {
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
        if (in_array($digits, ['123123123123', '12312312', '12345678', '00000000', '11111111', '1234567890', '87654321'], true)) {
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
        // If CMS stores multiple numbers, use the first one for tel: links.
        $first = preg_split('/[\s,;\/|]+/', trim($phone))[0] ?? $phone;

        // Remove any non-digit characters except + at the start
        $cleaned = preg_replace('/[^\d+]/', '', $first) ?? '';

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
     * Title shown on listing cards: listing title plus variant when it adds trim/package info.
     */
    public static function formatListingCardTitle(?string $title, ?string $variant = null): string
    {
        $formatted = self::formatListingTitle($title);
        $variant = self::formatListingTitle($variant);
        if ($variant === '') {
            return $formatted;
        }

        if ($formatted !== '' && mb_stripos($formatted, $variant) !== false) {
            return $formatted;
        }

        return trim($formatted.' '.$variant);
    }

    /**
     * True when a city label is a real place name, not a form/field placeholder.
     */
    public static function isUsableCityName(mixed $city): bool
    {
        $city = trim((string) $city);
        if ($city === '') {
            return false;
        }

        if (preg_match('/^\d+$/', $city) === 1) {
            return false;
        }

        $placeholders = [
            'city',
            'address',
            'postcode',
            'postal code',
            'n/a',
            'na',
            'test',
            'unknown',
            'null',
            'undefined',
            'street',
        ];

        return ! in_array(mb_strtolower($city), $placeholders, true);
    }

    /**
     * City name for a Danish postcode, cached per request.
     */
    public static function cityForPostcode(?string $postcode): ?string
    {
        $postcode = trim((string) $postcode);
        if ($postcode === '') {
            return null;
        }

        if (array_key_exists($postcode, self::$cityByPostcode)) {
            return self::$cityByPostcode[$postcode];
        }

        try {
            $city = Location::query()->where('postcode', $postcode)->value('city');
            $city = is_string($city) ? trim($city) : '';
            self::$cityByPostcode[$postcode] = self::isUsableCityName($city) ? $city : null;
        } catch (\Throwable) {
            self::$cityByPostcode[$postcode] = null;
        }

        return self::$cityByPostcode[$postcode];
    }

    /**
     * WGS84 point for a Danish postcode centroid, cached per request.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public static function coordsForPostcode(?string $postcode): ?array
    {
        $postcode = trim((string) $postcode);
        if ($postcode === '') {
            return null;
        }
        if (array_key_exists($postcode, self::$coordsByPostcode)) {
            return self::$coordsByPostcode[$postcode];
        }
        try {
            $row = Location::query()->where('postcode', $postcode)->first(['latitude', 'longitude']);
            $lat = $row?->latitude;
            $lng = $row?->longitude;
            self::$coordsByPostcode[$postcode] = (is_numeric($lat) && is_numeric($lng))
                ? ['latitude' => (float) $lat, 'longitude' => (float) $lng]
                : null;
        } catch (\Throwable) {
            self::$coordsByPostcode[$postcode] = null;
        }

        return self::$coordsByPostcode[$postcode];
    }

    /**
     * Best-effort city from a free-text address line (never the street).
     */
    public static function cityFromAddress(?string $address): ?string
    {
        $address = trim((string) $address);
        if ($address === '') {
            return null;
        }

        if (! str_contains($address, ',')) {
            if (preg_match('/\d/', $address) === 1) {
                return null;
            }

            return self::isUsableCityName($address) ? $address : null;
        }

        $parts = preg_split('/,/', $address) ?: [];
        $last = trim((string) array_pop($parts));
        $last = preg_replace('/^\d{4,5}\s+/', '', $last) ?? $last;
        $last = trim($last);

        if (preg_match('/\d/', $last) === 1) {
            return null;
        }

        return self::isUsableCityName($last) ? $last : null;
    }

    /**
     * Location line for vehicle cards: the stored address, unchanged.
     *
     * @param  string|null  $postcode  Unused; kept so existing call sites keep compiling.
     * @param  string|null  $city  Unused; kept so existing call sites keep compiling.
     */
    public static function formatListingLocation(?string $address = null, ?string $postcode = null, ?string $city = null): string
    {
        return trim((string) $address);
    }

    /**
     * Compact fuel label for the listing spec row (avoids wrap on "Hybrid (Diesel + El)").
     */
    public static function formatFuelTypeShort(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $paren = mb_strpos($name, '(');
        if ($paren !== false) {
            $name = trim(mb_substr($name, 0, $paren));
        }

        if (mb_strlen($name) <= 18) {
            return $name;
        }

        $words = preg_split('/\s+/u', $name) ?: [];

        return trim(implode(' ', array_slice($words, 0, 2)));
    }

    /**
     * Calendar days since created_at in the app timezone.
     * Pass $maxDays to cap the window (used by isNewListing); omit it for the always-on age badge.
     */
    public static function newListingAgeDays(mixed $createdAt = null, ?int $maxDays = null): ?int
    {
        if ($createdAt === null || $createdAt === '') {
            return null;
        }

        try {
            $date = $createdAt instanceof Carbon ? $createdAt->copy() : Carbon::parse((string) $createdAt);
        } catch (\Throwable) {
            return null;
        }

        $tz = (string) config('app.timezone', 'Europe/Copenhagen');
        $createdStart = $date->timezone($tz)->startOfDay();
        $nowStart = now($tz)->startOfDay();

        if ($createdStart->greaterThan($nowStart)) {
            return 0;
        }

        $age = (int) $createdStart->diffInDays($nowStart);

        if ($age < 0) {
            return null;
        }

        if ($maxDays !== null && $age > $maxDays) {
            return null;
        }

        return $age;
    }

    /**
     * True when the listing row was created within the last N calendar days.
     */
    public static function isNewListing(mixed $createdAt = null, int $days = self::NEW_LISTING_MAX_DAYS): bool
    {
        return self::newListingAgeDays($createdAt, $days) !== null;
    }

    /**
     * Badge copy: "New today" on the listing date, otherwise "N days ago" for any age.
     */
    public static function newListingBadgeLabel(mixed $createdAt = null): ?string
    {
        $age = self::newListingAgeDays($createdAt);
        if ($age === null) {
            return null;
        }

        if ($age === 0) {
            return __('messages.pages.vehicles.new_listing_today');
        }

        return trans_choice('messages.pages.vehicles.new_listing_days_ago', $age, ['days' => $age]);
    }

    /**
     * Color band for the listing-age badge: today, 1–7 days, or older than a week.
     *
     * @return 'today'|'recent'|'older'|null
     */
    public static function newListingBadgeTone(mixed $createdAt = null): ?string
    {
        $age = self::newListingAgeDays($createdAt);
        if ($age === null) {
            return null;
        }

        if ($age === 0) {
            return 'today';
        }

        if ($age <= self::NEW_LISTING_MAX_DAYS) {
            return 'recent';
        }

        return 'older';
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

