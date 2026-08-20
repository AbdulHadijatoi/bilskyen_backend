<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalUrlMiddleware
{
    /**
     * Single-hop 301: HTTPS, apex host, no trailing slash (except /).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        if ($request->is('up') || $request->is('api/*') || $request->is('api/v1/*')) {
            return $next($request);
        }

        $path = $request->getPathInfo() ?: '/';
        $basename = basename($path);
        if ($path !== '/' && str_contains($basename, '.')) {
            return $next($request);
        }

        $appUrl = (string) config('app.url');
        $canonicalHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        $canonicalScheme = strtolower((string) (parse_url($appUrl, PHP_URL_SCHEME) ?: 'https'));
        $apex = $canonicalHost !== '' ? (string) preg_replace('/^www\./', '', $canonicalHost) : '';

        $requestHost = strtolower((string) $request->getHost());
        $requestScheme = $request->isSecure() ? 'https' : 'http';
        $forwarded = strtolower((string) $request->header('X-Forwarded-Proto', ''));
        if (in_array($forwarded, ['http', 'https'], true)) {
            $requestScheme = $forwarded;
        }

        $normalizedPath = $path === '/' ? '/' : rtrim($path, '/');
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        $isCanonicalHost = $apex !== '' && in_array($requestHost, [$apex, 'www.'.$apex], true);
        $desiredScheme = $isCanonicalHost ? $canonicalScheme : $requestScheme;
        $desiredHost = $isCanonicalHost ? $apex : $requestHost;

        if ($desiredScheme === $requestScheme && $desiredHost === $requestHost && $normalizedPath === $path) {
            return $next($request);
        }

        $query = $request->getQueryString();
        $location = $desiredScheme.'://'.$desiredHost.$normalizedPath.($query ? '?'.$query : '');

        return redirect($location, 301);
    }
}
