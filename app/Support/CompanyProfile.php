<?php

namespace App\Support;

class CompanyProfile
{
    public const PLACEHOLDER_PHONE = '+45 12 34 56 78';

    public const PLACEHOLDER_ADDRESS_NEEDLE = 'Dealership Lane';

    public static function name(): string
    {
        return (string) config('company.name', 'Bilskyen');
    }

    public static function legalName(): string
    {
        return (string) config('company.legal_name', 'Bilskyen ApS');
    }

    public static function cvr(): string
    {
        return (string) config('company.cvr', '45251853');
    }

    public static function email(): string
    {
        return (string) config('company.email', 'info@bilskyen.dk');
    }

    public static function street(): string
    {
        return (string) config('company.street', 'Smedeland 7');
    }

    public static function postalCode(): string
    {
        return (string) config('company.postal_code', '2600');
    }

    public static function city(): string
    {
        return (string) config('company.city', 'Glostrup');
    }

    public static function country(): string
    {
        return (string) config('company.country', 'DK');
    }

    public static function addressLine(): string
    {
        return trim(implode(', ', array_filter([
            self::street(),
            self::postalCode().' '.self::city(),
            self::country() === 'DK' ? 'Denmark' : self::country(),
        ])));
    }

    public static function publicPhone(?string $candidate = null): ?string
    {
        if (self::isPublicPhone($candidate)) {
            return trim((string) $candidate);
        }

        $configured = trim((string) config('company.phone', ''));

        return self::isPublicPhone($configured) ? $configured : null;
    }

    public static function isPublicPhone(?string $phone): bool
    {
        $phone = trim((string) $phone);
        if ($phone === '' || $phone === '#' || strcasecmp($phone, 'tel:') === 0) {
            return false;
        }

        $normalized = preg_replace('/\s+/', ' ', $phone) ?? $phone;
        if (strcasecmp($normalized, self::PLACEHOLDER_PHONE) === 0) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) >= 8;
    }

    public static function publicAddress(?string $candidate = null): string
    {
        $candidate = trim((string) $candidate);
        if ($candidate !== '' && ! str_contains($candidate, self::PLACEHOLDER_ADDRESS_NEEDLE)) {
            return $candidate;
        }

        return self::addressLine();
    }

    /**
     * @return list<string>
     */
    public static function sameAs(): array
    {
        $urls = config('company.same_as', []);
        if (! is_array($urls)) {
            return [];
        }

        return array_values(array_filter($urls, static function ($url): bool {
            return is_string($url) && preg_match('#^https://#i', $url) === 1;
        }));
    }

    public static function logoUrl(): string
    {
        $path = (string) config('company.logo', '/images/og-image.jpg');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }

    public static function mapsSearchUrl(?string $address = null): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($address ?: self::addressLine());
    }
}
