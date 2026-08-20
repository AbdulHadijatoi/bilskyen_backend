<?php

namespace App\Console\Commands;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\MarketplaceCity;
use App\Models\SeoPage;
use App\Models\Vehicle;
use App\Services\Seo\GoogleSearchConsoleService;
use Illuminate\Console\Command;
use Throwable;

class SeoGscInspectCommand extends Command
{
    protected $signature = 'seo:gsc-inspect
        {--dealers-only : Only inspect dealer pages that have seo_pages rows}
        {--vehicles-only : Only inspect published vehicle detail URLs}
        {--hubs : Inspect /biler, public dealers, /markedsdata, and sample city pages}
        {--limit=100 : Max vehicle URLs to inspect (ignored for dealers)}
        {--sleep=1 : Seconds to wait between Inspection API calls}
        {--submit-sitemap : Submit /sitemap.xml to Search Console before inspecting}
        {--list-sitemaps : List sitemaps registered on the property and exit}
        {--base-url= : Override APP_URL for inspected URLs (e.g. https://bilskyen.dk)}';

    protected $description = 'Inspect dealer/vehicle URL indexing status via Google Search Console URL Inspection API';

    public function handle(GoogleSearchConsoleService $gsc): int
    {
        if (! $gsc->isConfigured()) {
            $this->error('Google Search Console is not configured. Set GOOGLE_SEARCH_CONSOLE_PROPERTY and GOOGLE_SERVICE_ACCOUNT_JSON.');
            $this->line('See backend/docs/google-search-console.md');

            return self::FAILURE;
        }

        try {
            $this->info('Property: '.$gsc->property());
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('list-sitemaps')) {
            return $this->listSitemaps($gsc);
        }

        if ($this->option('submit-sitemap')) {
            $sitemapUrl = rtrim($this->publicBaseUrl(), '/').'/sitemap.xml';
            try {
                $gsc->submitSitemap($sitemapUrl);
                $this->info('Submitted sitemap: '.$sitemapUrl);
            } catch (Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        $dealersOnly = (bool) $this->option('dealers-only');
        $vehiclesOnly = (bool) $this->option('vehicles-only');
        $hubs = (bool) $this->option('hubs');
        if ($dealersOnly && $vehiclesOnly) {
            $this->error('Use only one of --dealers-only / --vehicles-only.');

            return self::FAILURE;
        }
        if ($hubs && ($dealersOnly || $vehiclesOnly)) {
            $this->error('Do not combine --hubs with --dealers-only / --vehicles-only.');

            return self::FAILURE;
        }

        $urls = [];
        if ($hubs) {
            $urls = $this->hubUrls();
        } else {
            if (! $vehiclesOnly) {
                foreach ($this->dealerSeoUrls() as $url) {
                    $urls[] = ['type' => 'dealer', 'url' => $url];
                }
            }
            if (! $dealersOnly) {
                $limit = max(1, (int) $this->option('limit'));
                foreach ($this->vehicleUrls($limit) as $url) {
                    $urls[] = ['type' => 'vehicle', 'url' => $url];
                }
            }
        }

        if ($urls === []) {
            $this->warn('No URLs to inspect.');

            return self::SUCCESS;
        }

        $this->info('Inspecting '.count($urls).' URL(s)…');
        $sleep = max(0, (int) $this->option('sleep'));
        $rows = [];
        $notIndexed = [];

        foreach ($urls as $i => $item) {
            try {
                $result = $gsc->inspectUrl($item['url']);
                $coverage = (string) ($result['coverageState'] ?? '');
                $indexing = (string) ($result['indexingState'] ?? '');
                $verdict = (string) ($result['verdict'] ?? '');
                $rows[] = [
                    $item['type'],
                    $this->shortUrl($item['url']),
                    $coverage !== '' ? $coverage : '—',
                    $indexing !== '' ? $indexing : '—',
                    $verdict !== '' ? $verdict : '—',
                    $result['lastCrawlTime'] ?? '—',
                    $result['robotsTxtState'] ?? '—',
                ];

                $indexed = str_contains(strtolower($coverage), 'indexed')
                    || $indexing === 'INDEXING_ALLOWED'
                    || strtoupper($verdict) === 'PASS';
                if (! $indexed) {
                    $notIndexed[] = $item['url'].' → '.($coverage ?: $indexing ?: $verdict ?: 'unknown');
                }
            } catch (Throwable $e) {
                $rows[] = [
                    $item['type'],
                    $this->shortUrl($item['url']),
                    'ERROR',
                    '—',
                    $e->getMessage(),
                    '—',
                    '—',
                ];
                $notIndexed[] = $item['url'].' → ERROR: '.$e->getMessage();
            }

            if ($sleep > 0 && $i < count($urls) - 1) {
                sleep($sleep);
            }
        }

        $this->table(
            ['Type', 'URL', 'Coverage', 'Indexing', 'Verdict', 'Last crawl', 'Robots'],
            $rows
        );

        if ($notIndexed !== []) {
            $this->newLine();
            $this->warn(count($notIndexed).' URL(s) not clearly indexed:');
            foreach ($notIndexed as $line) {
                $this->line('  - '.$line);
            }
        } else {
            $this->info('All inspected URLs look indexed (or indexing allowed).');
        }

        return self::SUCCESS;
    }

    private function listSitemaps(GoogleSearchConsoleService $gsc): int
    {
        try {
            $sitemaps = $gsc->listSitemaps();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($sitemaps === []) {
            $this->warn('No sitemaps registered on this property.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($sitemaps as $sitemap) {
            $rows[] = [
                $sitemap['path'] ?? '—',
                $sitemap['lastSubmitted'] ?? '—',
                $sitemap['lastDownloaded'] ?? '—',
                isset($sitemap['isPending']) && $sitemap['isPending'] ? 'yes' : 'no',
                $sitemap['errors'] ?? 0,
                $sitemap['warnings'] ?? 0,
            ];
        }

        $this->table(
            ['Path', 'Last submitted', 'Last downloaded', 'Pending', 'Errors', 'Warnings'],
            $rows
        );

        foreach ($sitemaps as $sitemap) {
            $path = (string) ($sitemap['path'] ?? '');
            if (self::sitemapPathIsHttp($path)) {
                $this->warn('Sitemap is HTTP (should be HTTPS): '.$path);
            }
        }

        return self::SUCCESS;
    }

    public static function sitemapPathIsHttp(string $path): bool
    {
        return str_starts_with(strtolower(trim($path)), 'http://');
    }

    /**
     * @param  array{indexable_city?: string|null, thin_city?: string|null, dealers?: list<string>}  $extras
     * @return list<array{type: string, url: string}>
     */
    public static function hubInspectionItems(string $base, array $extras = []): array
    {
        $base = rtrim($base, '/');
        $items = [
            ['type' => 'listing', 'url' => $base.'/biler'],
            ['type' => 'market', 'url' => $base.'/markedsdata'],
        ];
        if (! empty($extras['indexable_city'])) {
            $items[] = ['type' => 'city', 'url' => $base.'/biler-i/'.$extras['indexable_city']];
        }
        if (! empty($extras['thin_city'])) {
            $items[] = ['type' => 'city_noindex', 'url' => $base.'/biler-i/'.$extras['thin_city']];
        }
        foreach ($extras['dealers'] ?? [] as $slug) {
            $slug = trim((string) $slug);
            if ($slug === '' || ! Dealer::isPublicProfileSlug($slug)) {
                continue;
            }
            $items[] = ['type' => 'dealer', 'url' => $base.'/dealer-'.$slug];
        }

        return $items;
    }

    /**
     * @return list<array{type: string, url: string}>
     */
    private function hubUrls(): array
    {
        $indexableSlug = MarketplaceCity::query()
            ->where('is_active', true)
            ->where('published_vehicle_count', '>=', MarketplaceCity::MIN_VEHICLES_FOR_INDEX)
            ->orderByDesc('published_vehicle_count')
            ->value('slug');

        $thinSlug = MarketplaceCity::query()
            ->where('is_active', true)
            ->where('published_vehicle_count', '<', MarketplaceCity::MIN_VEHICLES_FOR_INDEX)
            ->orderBy('published_vehicle_count')
            ->value('slug');

        $dealerSlugs = Dealer::query()
            ->whereNotNull('slug')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->limit(20)
            ->pluck('slug')
            ->filter(fn ($slug) => Dealer::isPublicProfileSlug($slug))
            ->take(5)
            ->values()
            ->all();

        return self::hubInspectionItems($this->publicBaseUrl(), [
            'indexable_city' => $indexableSlug,
            'thin_city' => $thinSlug,
            'dealers' => $dealerSlugs,
        ]);
    }

    /**
     * @return list<string>
     */
    private function dealerSeoUrls(): array
    {
        $base = rtrim($this->publicBaseUrl(), '/');

        return SeoPage::query()
            ->where('page_type', 'dealer')
            ->orderBy('page_key')
            ->pluck('page_key')
            ->filter()
            ->map(fn ($slug) => $base.'/dealer-'.$slug)
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function vehicleUrls(int $limit): array
    {
        $base = rtrim($this->publicBaseUrl(), '/');

        return Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNotNull('slug')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->pluck('slug')
            ->map(fn ($slug) => route('vehicle.detail', $slug))
            ->values()
            ->all();
    }

    private function publicBaseUrl(): string
    {
        $override = trim((string) $this->option('base-url'));
        if ($override !== '') {
            return rtrim($override, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    private function shortUrl(string $url): string
    {
        $base = rtrim($this->publicBaseUrl(), '/');
        if (str_starts_with($url, $base)) {
            return substr($url, strlen($base)) ?: '/';
        }

        return $url;
    }
}
