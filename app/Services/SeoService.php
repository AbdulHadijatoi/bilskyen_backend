<?php

namespace App\Services;

use App\Models\SeoPage;
use App\Models\SeoSitemap;
use App\Models\Vehicle;
use App\Models\Dealer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class SeoService
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get SEO data for a page from cache or DB.
     */
    public function getForPage(string $pageType, string $pageKey): ?array
    {
        $cacheKey = SeoPage::getCacheKey($pageType, $pageKey);
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $page = SeoPage::where('page_type', $pageType)
            ->where('page_key', $pageKey)
            ->first();

        if (!$page) {
            Cache::put($cacheKey, false, self::CACHE_TTL); // cache "missing" briefly to avoid repeated DB hits
            return null;
        }

        $data = $page->only([
            'title', 'meta_title', 'meta_description', 'meta_keywords',
            'canonical_url', 'robots',
            'og_title', 'og_description', 'og_image',
            'twitter_title', 'twitter_description', 'twitter_image',
            'schema_type', 'schema_json', 'content_html', 'faq_json', 'breadcrumbs_json',
            'updated_at',
        ]);
        Cache::put($cacheKey, $data, self::CACHE_TTL);
        return $data;
    }

    /**
     * Get all SEO pages for admin list/filters.
     */
    public function getAllForAdmin(): Collection
    {
        return SeoPage::orderBy('page_type')->orderBy('page_key')->get();
    }

    /**
     * Update or create an SEO page.
     */
    public function updateOrCreate(array $attributes): SeoPage
    {
        $pageType = $attributes['page_type'] ?? null;
        $pageKey = $attributes['page_key'] ?? null;

        $page = SeoPage::updateOrCreate(
            [
                'page_type' => $pageType,
                'page_key' => $pageKey,
            ],
            collect($attributes)->only([
                'title', 'meta_title', 'meta_description', 'meta_keywords',
                'canonical_url', 'robots',
                'og_title', 'og_description', 'og_image',
                'twitter_title', 'twitter_description', 'twitter_image',
                'schema_type', 'schema_json', 'content_html', 'faq_json', 'breadcrumbs_json',
            ])->toArray()
        );

        SeoPage::clearPageCache($page->page_type, $page->page_key);
        SeoPage::clearSitemapAndRobotsCache();

        return $page;
    }

    /**
     * Delete an SEO page and clear caches.
     */
    public function delete(SeoPage $seoPage): void
    {
        $pageType = $seoPage->page_type;
        $pageKey = $seoPage->page_key;
        $seoPage->delete();
        SeoPage::clearPageCache($pageType, $pageKey);
        SeoPage::clearSitemapAndRobotsCache();
    }

    /**
     * Build sitemap XML (merge seo_sitemaps, seo_pages static, vehicles, dealers).
     */
    public function getSitemapXml(): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $entries = [];

        // Custom entries from seo_sitemaps table
        foreach (SeoSitemap::all() as $row) {
            $loc = str_starts_with($row->url, 'http') ? $row->url : $baseUrl . '/' . ltrim($row->url, '/');
            $entries[] = [
                'loc' => $loc,
                'lastmod' => $row->lastmod?->format('Y-m-d'),
                'changefreq' => $row->changefreq ?? 'weekly',
                'priority' => $row->priority ?? '0.5',
            ];
        }

        // Static pages from seo_pages (home, listing, static)
        $staticPageKeys = [
            'home' => ['home', 'home', 1.0, 'daily'],
            'listing' => ['listing', 'vehicles', 0.9, 'daily'],
            'static' => ['static', null, 0.7, 'monthly'],
        ];

        $staticPageKeyToRoute = [
            'home' => 'home',
            'vehicles' => 'vehicles',
            'privacy-policy' => 'privacy-policy',
            'terms-of-service' => 'terms-of-service',
            'about' => 'about',
            'contact' => 'contact',
        ];

        $addedUrls = array_column($entries, 'loc');

        foreach (SeoPage::whereIn('page_type', ['home', 'listing', 'static'])->get() as $seo) {
            $routeName = $staticPageKeyToRoute[$seo->page_key] ?? null;
            if ($routeName) {
                $loc = $baseUrl . '/' . ltrim(route($routeName, [], false), '/');
                if (!in_array($loc, $addedUrls, true)) {
                    $entries[] = [
                        'loc' => $loc,
                        'lastmod' => $seo->updated_at?->format('Y-m-d'),
                        'changefreq' => $seo->page_key === 'home' ? 'daily' : 'weekly',
                        'priority' => $seo->page_key === 'home' ? '1.0' : ($seo->page_key === 'vehicles' ? '0.9' : '0.7'),
                    ];
                    $addedUrls[] = $loc;
                }
            }
        }

        // If no seo_pages for home/vehicles, add default URLs
        if (!in_array($baseUrl . '/', $addedUrls, true) && !in_array($baseUrl, $addedUrls, true)) {
            $entries[] = [
                'loc' => $baseUrl . '/',
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];
        }
        $vehiclesUrl = $baseUrl . '/vehicles';
        if (!in_array($vehiclesUrl, $addedUrls, true)) {
            $entries[] = [
                'loc' => $vehiclesUrl,
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
        }

        // Vehicle detail URLs (published only)
        $vehicleSlugs = Vehicle::whereNotNull('published_at')->pluck('slug');
        foreach ($vehicleSlugs as $slug) {
            try {
                $loc = $baseUrl . '/vehicles/' . $slug;
                $entries[] = [
                    'loc' => $loc,
                    'lastmod' => now()->format('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        // Dealer URLs
        $dealerSlugs = Dealer::pluck('slug');
        foreach ($dealerSlugs as $slug) {
            try {
                $loc = $baseUrl . '/dealer-' . $slug;
                $entries[] = [
                    'loc' => $loc,
                    'lastmod' => now()->format('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as $e) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($e['loc']) . '</loc>' . "\n";
            if (!empty($e['lastmod'])) {
                $xml .= '    <lastmod>' . $e['lastmod'] . '</lastmod>' . "\n";
            }
            if (!empty($e['changefreq'])) {
                $xml .= '    <changefreq>' . $e['changefreq'] . '</changefreq>' . "\n";
            }
            if (isset($e['priority'])) {
                $xml .= '    <priority>' . $e['priority'] . '</priority>' . "\n";
            }
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Build robots.txt content.
     */
    public function getRobotsTxt(): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /vehicles',
            'Allow: /vehicles/',
            'Disallow: /vehicles?',  // filter params
            'Disallow: /admin',
            'Disallow: /api',
            'Disallow: /auth',
            'Disallow: /seller-dashboard',
            'Disallow: /test/',
            '',
            'Sitemap: ' . $baseUrl . '/sitemap.xml',
        ];
        return implode("\n", $lines);
    }
}
