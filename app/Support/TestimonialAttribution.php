<?php

namespace App\Support;

class TestimonialAttribution
{
    /**
     * CMS or lang name/location that must not appear as a verified person.
     *
     * @var list<string>
     */
    private const BLOCKED_NEEDLES = [
        'copenhagen',
        'denmark',
        'john davis',
        'john ',
        'priya',
        'sharma',
        'ahmed khan',
        'ahmed ',
        'mads',
        'jonas',
        'line ',
    ];

    public static function name(?string $candidate): string
    {
        $fallback = __('messages.pages.home.testimonial_buyer');
        $candidate = trim((string) $candidate);
        if ($candidate === '' || self::looksAttributed($candidate)) {
            return $fallback;
        }

        return $candidate;
    }

    public static function location(?string $candidate): string
    {
        $fallback = __('messages.pages.home.testimonial_region');
        $candidate = trim((string) $candidate);
        if ($candidate === '' || self::looksAttributed($candidate)) {
            return $fallback;
        }

        return $candidate;
    }

    public static function looksAttributed(string $value): bool
    {
        $lower = mb_strtolower($value);
        foreach (self::BLOCKED_NEEDLES as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return (bool) preg_match('/^[A-ZÆØÅ][a-zæøå]+ [A-ZÆØÅ]/u', $value);
    }
}
