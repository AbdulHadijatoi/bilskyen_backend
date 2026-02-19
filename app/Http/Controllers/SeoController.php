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
        $xml = Cache::remember('sitemap_xml', 86400, fn () => $this->seoService->getSitemapXml());

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Serve dynamic robots.txt (cached 24h).
     */
    public function robots(): Response
    {
        $txt = Cache::remember('robots_txt', 86400, fn () => $this->seoService->getRobotsTxt());

        return response($txt, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
