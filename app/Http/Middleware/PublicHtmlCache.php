<?php

namespace App\Http\Middleware;

use App\Support\CrawlerRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicHtmlCache
{
    /**
     * Short public cache for anonymous HTML. Authenticated and auth/API routes stay private.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldCache($request, $response)) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');

        if (CrawlerRequest::isCrawler($request)) {
            $this->stripSetCookie($response);
        }

        return $response;
    }

    private function stripSetCookie(Response $response): void
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

    private function shouldCache(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        if ($request->is('auth/*', 'api/*', 'api/v1/*', 'up')) {
            return false;
        }

        if ($request->cookie('access_token') || $request->cookie('bilskyen_auth')) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return false;
        }

        return true;
    }
}
