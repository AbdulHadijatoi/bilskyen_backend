<?php

namespace App\Support;

class HomeHeroCopy
{
    /**
     * CMS hero H1, or the transactional lang title when CMS copy is emotional/empty.
     */
    public static function title(?string $cms): string
    {
        $fallback = __('messages.pages.home.title');
        $cms = trim((string) $cms);
        if ($cms === '' || self::isEmotionalTitle($cms)) {
            return $fallback;
        }

        return $cms;
    }

    /**
     * CMS hero subcopy, or the transactional lang description when CMS copy is emotional/empty.
     */
    public static function description(?string $cms): string
    {
        $fallback = __('messages.pages.home.description');
        $cms = trim((string) $cms);
        if ($cms === '' || self::isEmotionalDescription($cms)) {
            return $fallback;
        }

        return $cms;
    }

    public static function isEmotionalTitle(string $value): bool
    {
        return (bool) preg_match('/drømmebil|dream car|perfekte køretøj|perfect vehicle/iu', $value);
    }

    public static function isEmotionalDescription(string $value): bool
    {
        return (bool) preg_match('/drømmebil|dream car|perfekte match|perfect match/iu', $value);
    }
}
