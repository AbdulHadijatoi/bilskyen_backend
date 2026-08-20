<?php

namespace App\Support;

use Illuminate\Http\Request;

class CrawlerRequest
{
    private const PATTERN = 'googlebot|bingbot|yandex(?:bot)?|gptbot|oai-searchbot|claudebot|applebot|bytespider|duckduckbot';

    public static function isCrawler(?Request $request = null): bool
    {
        $ua = strtolower((string) ($request?->userAgent() ?? ''));
        if ($ua === '') {
            return false;
        }

        return preg_match('/'.self::PATTERN.'/i', $ua) === 1;
    }
}
