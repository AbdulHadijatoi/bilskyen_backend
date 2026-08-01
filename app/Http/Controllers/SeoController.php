<?php

namespace App\Http\Controllers;

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
        $env = app()->environment();
        $ttl = $this->seoService->isIndexingEnabled() ? 86400 : 60;
        $xml = Cache::remember("sitemap_xml_{$env}", $ttl, fn () => $this->seoService->getSitemapXml());

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
        $env = app()->environment();
        $ttl = $this->seoService->isIndexingEnabled() ? 86400 : 60;
        $txt = Cache::remember("robots_txt_{$env}", $ttl, fn () => $this->seoService->getRobotsTxt());

        $headers = [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'public, max-age='.$ttl,
        ];

        if (! $this->seoService->isIndexingEnabled()) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }

        return response($txt, 200, $headers);
    }
}
