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

    public static function stripSetCookie(\Symfony\Component\HttpFoundation\Response $response): void
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie(
                $cookie->getName(),
                $cookie->getPath() ?? '/',
                $cookie->getDomain()
            );
        }

        $response->headers->remove('Set-Cookie');
    }
}
