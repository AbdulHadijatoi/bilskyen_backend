<?php

namespace App\Support;

use Illuminate\Http\Request;

class InternalRedirect
{
    /**
     * Allowlist a same-origin path (must start with / and not //).
     */
    public static function path(?string $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || strlen($url) > 2048) {
            return null;
        }

        if (str_contains($url, "\0") || preg_match('/[\r\n]/', $url)) {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            $parts = parse_url($url);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
            if ($host === '' || $appHost === '' || $host !== $appHost) {
                return null;
            }

            $path = ($parts['path'] ?? '/')
                .(isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '');

            return self::path($path);
        }

        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        if (str_contains($url, '\\') || str_contains($url, '@')) {
            return null;
        }

        $lower = strtolower($url);
        if (str_contains($lower, 'javascript:') || str_contains($lower, 'data:')) {
            return null;
        }

        return $url;
    }

    /**
     * Safe intended path from the current request (skips /auth/* to avoid loops).
     */
    public static function intendedFromRequest(Request $request): ?string
    {
        $path = self::path($request->getRequestUri());
        if ($path === null || str_starts_with($path, '/auth/')) {
            return null;
        }

        return $path;
    }

    /**
     * Destination after a successful web login.
     */
    public static function afterLogin(Request $request): string
    {
        return self::path($request->input('return_url'))
            ?? self::path($request->query('return_url'))
            ?? self::path($request->session()->pull('url.intended'))
            ?? '/';
    }
}
