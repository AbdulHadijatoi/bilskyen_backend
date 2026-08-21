<?php

namespace App\Http\Controllers;

use App\Services\Seo\IndexNowService;
use App\Services\SeoService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function __construct(
        private SeoService $seoService
    ) {}

    /**
     * Serve dynamic sitemap.xml (cached 24h).
     */
    public function sitemap(): Response
    {
        $ttl = $this->seoService->isIndexingEnabled() ? 86400 : 60;
        $xml = Cache::remember(SeoService::sitemapCacheKey(), $ttl, fn () => $this->seoService->getSitemapXml());

        $headers = [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age='.$ttl,
        ];

        if (! $this->seoService->isIndexingEnabled()) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }

        return response($xml, 200, $headers);
    }

    /**
     * Serve dynamic robots.txt (cached 24h).
     */
    public function robots(): Response
    {
        $ttl = $this->seoService->isIndexingEnabled() ? 86400 : 60;
        $txt = Cache::remember(SeoService::robotsCacheKey(), $ttl, fn () => $this->seoService->getRobotsTxt());

        $headers = [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'public, max-age='.$ttl,
        ];

        if (! $this->seoService->isIndexingEnabled()) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }

        return response($txt, 200, $headers);
    }

    /**
     * Optional llms.txt for non-Google agents (Google ignores this file).
     */
    public function llmsTxt(): Response
    {
        $txt = $this->seoService->getLlmsTxt();
        $headers = [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ];
        if (! $this->seoService->isIndexingEnabled()) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }

        return response($txt, 200, $headers);
    }

    /**
     * IndexNow key file. Must match INDEXNOW_KEY (8–128 hex/uuid chars).
     */
    public function indexNowKey(string $indexNowKey): Response
    {
        $configured = trim((string) config('services.indexnow.key', ''));
        if (! IndexNowService::isValidKey($configured) || $configured !== $indexNowKey) {
            abort(404);
        }

        return response($configured, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
