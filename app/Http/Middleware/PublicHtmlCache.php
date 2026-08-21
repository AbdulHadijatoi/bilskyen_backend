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

        if (CrawlerRequest::isCrawler($request) && in_array($request->method(), ['GET', 'HEAD'], true)) {
            CrawlerRequest::stripSetCookie($response);
        }

        if (! $this->shouldCache($request, $response)) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');

        return $response;
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
