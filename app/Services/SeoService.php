<?php

namespace App\Services;

use App\Constants\CmsPostStatus;
use App\Constants\VehicleListStatus;
use App\Models\CmsPost;
use App\Models\Dealer;
use App\Models\LandingPage;
use App\Models\SeoPage;
use App\Models\SeoSitemap;
use App\Models\Vehicle;
use App\Services\PlatformSettingService;
use App\Services\Seo\SchemaBuilderService;
use App\Support\CompanyProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SeoService
{
    private const CACHE_TTL = 86400; // 24 hours

    public const SITEMAP_CACHE_VERSION = 'v2';

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
     * Build SEO for a public vehicle detail page from brand/model/year/price fields.
     * Optional seo_pages overrides (page_type=vehicle, page_key=slug) win when non-empty.
     */
    public function resolveForVehicle(Vehicle $vehicle): array
    {
        $brand = trim((string) ($vehicle->brand_name ?? ''));
        $model = trim((string) ($vehicle->model_name ?? ''));
        $variant = trim((string) ($vehicle->variant_name ?? ''));
        $year = $this->vehicleYearForSeo($vehicle);
        $priceFormatted = $this->vehiclePriceForSeo($vehicle);
        $mileageFormatted = $this->vehicleMileageForSeo($vehicle);
        $dealerName = $this->vehicleDealerNameForSeo($vehicle);
        $dealerCity = trim((string) ($vehicle->dealer?->city ?? ''));

        $pageTitle = $this->buildVehiclePageTitle($brand, $model, $variant, $year, $priceFormatted);
        $description = $this->buildVehicleMetaDescription(
            $brand,
            $model,
            $variant,
            $dealerName,
            $year,
            $mileageFormatted,
            $priceFormatted
        );
        $keywords = $this->buildVehicleMetaKeywords($brand, $model, $year, $dealerCity);

        // Prefer generated copy; fall back to stored meta / truncated body only as last resort.
        if ($description === '') {
            $description = trim((string) (
                $vehicle->meta_description
                ?: Str::limit(strip_tags((string) ($vehicle->description ?? '')), 160, '')
            ));
        }
        if ($pageTitle === '') {
            $pageTitle = trim((string) (
                $vehicle->meta_title
                ?: $vehicle->title
                ?: __('messages.pages.vehicles.detail.page_title')
            ));
            if ($pageTitle !== '' && ! str_contains($pageTitle, 'Bilskyen')) {
                $pageTitle .= ' | Bilskyen';
            }
        }

        $ogImage = null;
        $firstImage = $vehicle->relationLoaded('images')
            ? $vehicle->images->first()
            : $vehicle->images()->orderBy('sort_order')->first();
        if ($firstImage) {
            $ogImage = $this->publicListingImage($firstImage->image_url ?? $firstImage->thumbnail_url ?? null);
        }

        $canonical = route('vehicle.detail', $vehicle);
        $schemaBrand = \App\Support\SchemaBrandName::normalize($brand !== '' ? $brand : null);
        $availability = ((int) $vehicle->list_status_id === VehicleListStatus::SOLD)
            ? 'https://schema.org/SoldOut'
            : 'https://schema.org/InStock';
        $seller = null;
        $dealer = $vehicle->dealer;
        if ($dealer && \App\Models\Dealer::isPublicProfileSlug($dealer->slug)) {
            $dealerUrl = route('dealer.show', $dealer->slug);
            $seller = array_filter([
                '@type' => 'AutoDealer',
                '@id' => $dealerUrl.'#dealer',
                'name' => $dealerName !== '' ? $dealerName : ($dealer->slug ?? null),
                'url' => $dealerUrl,
            ]);
        }

        $schema = app(SchemaBuilderService::class)->build('Vehicle', [
            'name' => trim(implode(' ', array_filter([$brand, $model, $variant], fn ($p) => $p !== '')))
                ?: ($vehicle->title ?? null),
            'brand' => $schemaBrand,
            'model' => $model !== '' ? $model : null,
            'year' => $year,
            'price' => $vehicle->price !== null && (float) $vehicle->price > 0 ? (float) $vehicle->price : null,
            'mileage' => $vehicle->km_driven !== null && (float) $vehicle->km_driven > 0
                ? (int) $vehicle->km_driven
                : null,
            'fuel' => $vehicle->fuel_type_name,
            'url' => $canonical,
            'image' => $ogImage,
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/UsedCondition',
            'seller' => $seller,
        ]);

        $breadcrumbs = app(SchemaBuilderService::class)->build('BreadcrumbList', [
            'items' => [
                ['name' => __('messages.common.site_name'), 'url' => url('/')],
                ['name' => __('messages.pages.vehicles.listing_h1'), 'url' => route('vehicles')],
                ['name' => $pageTitle !== '' ? $pageTitle : ($vehicle->title ?? __('messages.pages.vehicles.detail.page_title')), 'url' => $canonical],
            ],
        ]);

        $defaults = [
            'title' => $pageTitle,
            'meta_title' => $pageTitle,
            'meta_description' => $description !== '' ? $description : null,
            'meta_keywords' => $keywords !== '' ? $keywords : null,
            'canonical_url' => $canonical,
            'robots' => $vehicle->isSold() ? 'noindex, follow' : 'index, follow',
            'og_title' => $pageTitle,
            'og_description' => $description !== '' ? $description : null,
            'og_image' => $ogImage,
            'twitter_title' => $pageTitle,
            'twitter_description' => $description !== '' ? $description : null,
            'twitter_image' => $ogImage,
            'schema_type' => 'Vehicle',
            'schema_json' => $schema,
            'content_html' => null,
            'faq_json' => null,
            'breadcrumbs_json' => $breadcrumbs,
        ];

        return $this->omitPlaceholderImages($this->applyNonEmptyOverrides(
            $defaults,
            $this->getForPage('vehicle', (string) $vehicle->slug) ?? []
        ));
    }

    /**
     * SEO for a public blog post. CMS fields are defaults; seo_pages (page_type=blog, page_key=slug)
     * win only when non-empty. Schema is a plain Article array (no SchemaBuilderService).
     *
     * @return array<string, mixed>
     */
    public function resolveForCmsPost(CmsPost $post): array
    {
        $title = trim((string) ($post->meta_title ?: $post->title));
        $description = trim((string) ($post->meta_description ?: $post->excerpt));
        $canonical = trim((string) ($post->canonical_url ?: '')) ?: route('blog.show', $post->slug);
        $ogImage = trim((string) ($post->og_image ?: '')) ?: $post->featuredMedia?->url();
        $ogImage = $ogImage !== '' ? $ogImage : null;

        if ($post->exists && $post->author_user_id && ! $post->relationLoaded('author')) {
            $post->load('author');
        }

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $title !== '' ? $title : null,
            'description' => $description !== '' ? $description : null,
            'image' => $ogImage,
            'url' => $canonical,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
        ], fn ($value) => $value !== null && $value !== '');
        $schema['author'] = $this->cmsPostAuthorSchema($post);

        $defaults = [
            'title' => $title !== '' ? $title : null,
            'meta_title' => $title !== '' ? $title : null,
            'meta_description' => $description !== '' ? $description : null,
            'meta_keywords' => null,
            'canonical_url' => $canonical,
            'robots' => trim((string) ($post->robots ?: '')) ?: 'index, follow',
            'og_title' => $title !== '' ? $title : null,
            'og_description' => $description !== '' ? $description : null,
            'og_image' => $ogImage,
            'twitter_title' => $title !== '' ? $title : null,
            'twitter_description' => $description !== '' ? $description : null,
            'twitter_image' => $ogImage,
            'schema_type' => 'Article',
            'schema_json' => $schema,
            'content_html' => null,
            'faq_json' => null,
            'breadcrumbs_json' => null,
        ];

        return $this->applyNonEmptyOverrides(
            $defaults,
            $this->getForPage('blog', (string) $post->slug) ?? []
        );
    }

    /**
     * SEO for a public landing page. CMS fields are defaults; seo_pages (page_type=landing, page_key=slug)
     * win only when non-empty. Schema is a plain WebPage array (no SchemaBuilderService).
     *
     * @return array<string, mixed>
     */
    public function resolveForLandingPage(LandingPage $page): array
    {
        $title = trim((string) ($page->meta_title ?: $page->title));
        $description = trim((string) ($page->meta_description ?: ''));
        if ($description === '') {
            $description = $this->landingMetaFallback((string) $page->slug);
        }
        $canonical = trim((string) ($page->canonical_url ?: '')) ?: route('landing.show', $page->slug);
        $ogTitle = trim((string) ($page->og_title ?: '')) ?: $title;
        $ogDescription = trim((string) ($page->og_description ?: '')) ?: $description;
        $ogImage = trim((string) ($page->og_image ?: ''));
        $ogImage = $ogImage !== '' ? $ogImage : null;

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title !== '' ? $title : null,
            'description' => $description !== '' ? $description : null,
            'url' => $canonical,
            'image' => $ogImage,
        ], fn ($value) => $value !== null && $value !== '');

        $defaults = [
            'title' => $title !== '' ? $title : null,
            'meta_title' => $title !== '' ? $title : null,
            'meta_description' => $description !== '' ? $description : null,
            'meta_keywords' => null,
            'canonical_url' => $canonical,
            'robots' => trim((string) ($page->robots ?: '')) ?: 'index, follow',
            'og_title' => $ogTitle !== '' ? $ogTitle : null,
            'og_description' => $ogDescription !== '' ? $ogDescription : null,
            'og_image' => $ogImage,
            'twitter_title' => $ogTitle !== '' ? $ogTitle : null,
            'twitter_description' => $ogDescription !== '' ? $ogDescription : null,
            'twitter_image' => $ogImage,
            'schema_type' => 'WebPage',
            'schema_json' => $schema,
            'content_html' => null,
            'faq_json' => null,
            'breadcrumbs_json' => null,
        ];

        return $this->applyNonEmptyOverrides(
            $defaults,
            $this->getForPage('landing', (string) $page->slug) ?? []
        );
    }

    /**
     * @return array<string, string>
     */
    private function cmsPostAuthorSchema(CmsPost $post): array
    {
        $name = trim((string) ($post->author?->name ?? ''));
        if ($name !== '') {
            return [
                '@type' => 'Person',
                'name' => $name,
            ];
        }

        return [
            '@type' => 'Organization',
            'name' => CompanyProfile::name(),
        ];
    }

    private function landingMetaFallback(string $slug): string
    {
        $key = 'messages.cms.landing_meta.'.$slug;
        $translated = __($key);
        if (! is_string($translated) || $translated === $key) {
            return '';
        }

        return trim($translated);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function resolveForMarketSnapshot(array $snapshot): array
    {
        $generatedAt = (string) ($snapshot['generated_at'] ?? now()->toAtomString());
        $title = __('messages.pages.market.meta_title');
        $description = __('messages.pages.market.meta_description');
        $canonical = route('market-snapshot');

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'dateModified' => $generatedAt,
        ], fn ($value) => $value !== null && $value !== '');

        return [
            'title' => $title,
            'meta_title' => $title,
            'meta_description' => $description,
            'meta_keywords' => null,
            'canonical_url' => $canonical,
            'robots' => 'index, follow',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => null,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => null,
            'schema_type' => 'WebPage',
            'schema_json' => $schema,
            'content_html' => null,
            'faq_json' => null,
            'breadcrumbs_json' => [
                ['name' => __('messages.common.site_name'), 'url' => url('/')],
                ['name' => __('messages.pages.market.heading'), 'url' => $canonical],
            ],
        ];
    }

    /**
     * @param  array{heading?: string, intro?: string, count?: int, indexable?: bool, canonical?: string, listing_urls?: list<array{url: string, name: string}>}  $hub
     * @return array<string, mixed>
     */
    public function resolveForHub(array $hub): array
    {
        $title = (string) ($hub['heading'] ?? '');
        $description = (string) ($hub['intro'] ?? '');
        $canonical = (string) ($hub['canonical'] ?? '');
        $indexable = (bool) ($hub['indexable'] ?? false);
        $count = (int) ($hub['count'] ?? 0);

        $itemListElement = [];
        $position = 1;
        foreach ($hub['listing_urls'] ?? [] as $item) {
            if (! is_array($item) || empty($item['url'])) {
                continue;
            }
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => $item['url'],
                'name' => $item['name'] ?? $item['url'],
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $canonical,
                ],
                [
                    '@type' => 'ItemList',
                    'name' => $title,
                    'numberOfItems' => $count,
                    'itemListElement' => $itemListElement,
                ],
            ],
        ];

        return [
            'title' => $title,
            'meta_title' => $title,
            'meta_description' => $description,
            'meta_keywords' => null,
            'canonical_url' => $canonical,
            'robots' => $indexable ? 'index, follow' : 'noindex, follow',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => null,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => null,
            'schema_type' => 'ItemList',
            'schema_json' => $schema,
            'content_html' => null,
            'faq_json' => null,
            'breadcrumbs_json' => [
                ['name' => __('messages.common.site_name'), 'url' => url('/')],
                ['name' => __('messages.pages.footer.browse_vehicles'), 'url' => route('vehicles')],
                ['name' => $title, 'url' => $canonical],
            ],
        ];
    }


    /**
     * Copy override fields onto defaults only when the override value is non-empty.
     *
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function applyNonEmptyOverrides(array $defaults, array $override): array
    {
        foreach ($override as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $defaults[$key] = $value;
        }

        return $defaults;
    }

    /**
     * Build truncated vehicle page title per SEO spec (~60 chars).
     * Drop price first, then variant, when too long.
     */
    public function buildVehiclePageTitle(
        string $brand,
        string $model,
        string $variant,
        ?string $year,
        ?string $priceFormatted
    ): string {
        $build = function (?string $includeVariant, ?string $includePrice) use ($brand, $model, $year): string {
            $nameParts = array_values(array_filter(
                [$brand, $model, $includeVariant],
                static fn (?string $p): bool => $p !== null && trim($p) !== ''
            ));
            $name = trim(implode(' ', $nameParts));
            if ($name === '') {
                return '';
            }

            $segments = [$name];
            if ($year !== null && $year !== '') {
                $segments[0] = $name . ' – ' . $year;
            }
            if ($includePrice !== null && $includePrice !== '') {
                $segments[] = $includePrice . ' kr';
            }
            $segments[] = 'Bilskyen';

            return implode(' | ', $segments);
        };

        $candidates = [
            $build($variant !== '' ? $variant : null, $priceFormatted),
            $build($variant !== '' ? $variant : null, null),
            $build(null, $priceFormatted),
            $build(null, null),
        ];

        $best = '';
        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $best = $candidate;
            if (mb_strlen($candidate) <= 60) {
                return $candidate;
            }
        }

        if ($best === '') {
            return '';
        }

        return Str::limit($best, 60, '');
    }

    /**
     * Build vehicle meta description (~155–160 chars).
     */
    public function buildVehicleMetaDescription(
        string $brand,
        string $model,
        string $variant,
        string $dealerName,
        ?string $year,
        ?string $mileageFormatted,
        ?string $priceFormatted
    ): string {
        $name = trim(implode(' ', array_filter([$brand, $model, $variant], fn ($p) => $p !== '')));
        if ($name === '') {
            return '';
        }

        $dealerPart = $dealerName !== '' ? $dealerName : 'Bilskyen';
        $facts = array_values(array_filter([
            $year,
            $mileageFormatted !== null ? $mileageFormatted . ' km' : null,
            $priceFormatted !== null ? $priceFormatted . ' kr' : null,
        ], static fn (?string $p): bool => $p !== null && $p !== ''));

        $factsStr = $facts !== [] ? implode(', ', $facts) . '. ' : '';
        $description = "{$name} til salg hos {$dealerPart}. {$factsStr}Se billeder, udstyr og kontakt forhandleren direkte på Bilskyen.";

        return Str::limit(trim(preg_replace('/\s+/', ' ', $description) ?? $description), 160, '');
    }

    /**
     * @return string comma-separated keywords
     */
    public function buildVehicleMetaKeywords(
        string $brand,
        string $model,
        ?string $year,
        string $dealerCity
    ): string {
        if ($brand === '' && $model === '') {
            return '';
        }

        $parts = [];
        $brandModel = trim($brand . ' ' . $model);
        if ($brandModel !== '') {
            $parts[] = $brandModel;
        }
        if ($brand !== '') {
            $parts[] = 'brugt ' . $brand;
        }
        if ($brandModel !== '' && $year) {
            $parts[] = $brandModel . ' ' . $year;
        }
        if ($brandModel !== '') {
            $parts[] = $brandModel . ' til salg';
        }
        if ($dealerCity !== '') {
            $parts[] = 'brugt bil ' . $dealerCity;
        }

        return implode(', ', $parts);
    }

    private function vehicleYearForSeo(Vehicle $vehicle): ?string
    {
        $year = $vehicle->model_year_name ?? $vehicle->model_year ?? null;
        if ($year === null || $year === '' || (int) $year <= 0) {
            return null;
        }

        return (string) (int) $year;
    }

    /**
     * Danish thousands separator (439.799). Null when missing / contact-for-price.
     */
    private function vehiclePriceForSeo(Vehicle $vehicle): ?string
    {
        $price = $vehicle->price;
        if ($price === null || (float) $price <= 0) {
            return null;
        }

        return number_format((float) $price, 0, ',', '.');
    }

    private function vehicleMileageForSeo(Vehicle $vehicle): ?string
    {
        $km = $vehicle->km_driven;
        if ($km === null || (float) $km <= 0) {
            return null;
        }

        return number_format((int) round((float) $km), 0, ',', '.');
    }

    private function vehicleDealerNameForSeo(Vehicle $vehicle): string
    {
        $dealer = $vehicle->dealer;
        if (! $dealer) {
            return '';
        }

        return trim((string) ($dealer->owner?->name ?? $dealer->slug ?? ''));
    }

    /**
     * SEO for city cars landing page (/biler-i/{slug}).
     *
     * @param  array{vehicle_count?: int, min_price?: float|null, max_price?: float|null, brands?: list<string>}  $stats
     */
    public function resolveForCityCars(\App\Models\MarketplaceCity $city, array $stats = []): array
    {
        $count = (int) ($stats['vehicle_count'] ?? $city->published_vehicle_count);
        $min = $stats['min_price'] ?? $city->min_price;
        $max = $stats['max_price'] ?? $city->max_price;
        $title = __('messages.pages.cities.cars_meta_title', ['city' => $city->name, 'count' => $count]);
        $description = __('messages.pages.cities.cars_meta_description', [
            'city' => $city->name,
            'count' => $count,
            'min' => $min !== null ? number_format((float) $min, 0, ',', '.') : '—',
            'max' => $max !== null ? number_format((float) $max, 0, ',', '.') : '—',
        ]);

        $indexable = $city->isCarsIndexable();
        $canonical = url('/biler-i/'.$city->slug);

        $defaults = [
            'title' => $title,
            'meta_title' => $title,
            'meta_description' => $description,
            'meta_keywords' => null,
            'canonical_url' => $canonical,
            'robots' => $indexable ? 'index, follow' : 'noindex, follow',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => null,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => null,
            'schema_type' => 'ItemList',
            'schema_json' => null,
            'content_html' => null,
            'faq_json' => null,
            'breadcrumbs_json' => [
                ['name' => __('messages.common.site_name'), 'url' => url('/')],
                ['name' => __('messages.pages.cities.index_heading'), 'url' => url('/byer')],
                ['name' => $city->name, 'url' => $canonical],
            ],
        ];

        $override = $this->getForPage('city_cars', $city->slug) ?? [];
        foreach ($override as $key => $value) {
            if ($value !== null && $value !== '') {
                $defaults[$key] = $value;
            }
        }

        if (! $indexable) {
            $defaults['robots'] = 'noindex, follow';
        }

        return $defaults;
    }

    /**
     * SEO for city dealers landing page (/forhandlere-i/{slug}).
     *
     * @param  array{dealer_count?: int}  $stats
     */
    public function resolveForCityDealers(\App\Models\MarketplaceCity $city, array $stats = []): array
    {
        $count = (int) ($stats['dealer_count'] ?? $city->dealer_count);
        $title = __('messages.pages.cities.dealers_meta_title', ['city' => $city->name, 'count' => $count]);
        $description = __('messages.pages.cities.dealers_meta_description', [
            'city' => $city->name,
            'count' => $count,
        ]);

        $indexable = $city->isDealersIndexable();
        $canonical = url('/forhandlere-i/'.$city->slug);

        $defaults = [
            'title' => $title,
            'meta_title' => $title,
            'meta_description' => $description,
            'meta_keywords' => null,
            'canonical_url' => $canonical,
            'robots' => $indexable ? 'index, follow' : 'noindex, follow',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => null,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => null,
            'schema_type' => 'ItemList',
            'schema_json' => null,
            'content_html' => null,
            'faq_json' => null,
            'breadcrumbs_json' => [
                ['name' => __('messages.common.site_name'), 'url' => url('/')],
                ['name' => __('messages.pages.cities.index_heading'), 'url' => url('/byer')],
                ['name' => $city->name, 'url' => $canonical],
            ],
        ];

        $override = $this->getForPage('city_dealers', $city->slug) ?? [];
        foreach ($override as $key => $value) {
            if ($value !== null && $value !== '') {
                $defaults[$key] = $value;
            }
        }

        if (! $indexable) {
            $defaults['robots'] = 'noindex, follow';
        }

        return $defaults;
    }

    /**
     * SEO for /byer city index.
     */
    public function resolveForCitiesIndex(int $cityCount): array
    {
        $title = __('messages.pages.cities.index_meta_title');
        $description = __('messages.pages.cities.index_meta_description', ['count' => $cityCount]);

        return [
            'title' => $title,
            'meta_title' => $title,
            'meta_description' => $description,
            'canonical_url' => url('/byer'),
            'robots' => 'index, follow',
            'og_title' => $title,
            'og_description' => $description,
            'twitter_title' => $title,
            'twitter_description' => $description,
        ];
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
     * Whether public SEO indexing signals should be emitted.
     * Non-production (staging/local) must not be discoverable by crawlers.
     */
    public function isIndexingEnabled(): bool
    {
        return app()->environment('production');
    }

    public static function sitemapCacheKey(?string $env = null): string
    {
        return 'sitemap_xml_'.($env ?? app()->environment()).'_'.self::SITEMAP_CACHE_VERSION;
    }

    public static function robotsCacheKey(?string $env = null): string
    {
        return 'robots_txt_'.($env ?? app()->environment());
    }

    /**
     * Drop sitemap/robots cache for every env key this app has used.
     */
    public static function forgetPublicCaches(): void
    {
        $envs = array_unique(['local', 'staging', 'production', 'testing', (string) app()->environment()]);
        foreach ($envs as $env) {
            Cache::forget(self::sitemapCacheKey($env));
            Cache::forget('sitemap_xml_'.$env);
            Cache::forget(self::robotsCacheKey($env));
        }
        Cache::forget('sitemap_xml');
        Cache::forget('robots_txt');
    }

    /**
     * Absolute HTTPS URL for sitemap/robots locs (strips default ports and trailing slash except "/").
     */
    public function canonicalPublicUrl(string $urlOrPath): string
    {
        $trimmed = trim($urlOrPath);
        $appUrl = (string) config('app.url');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'bilskyen.dk';

        if ($trimmed === '') {
            $trimmed = '/';
        }

        if (! str_starts_with($trimmed, 'http://') && ! str_starts_with($trimmed, 'https://')) {
            $path = '/'.ltrim($trimmed, '/');
            $trimmed = 'https://'.$host.$path;
        }

        $parts = parse_url($trimmed) ?: [];
        $host = $parts['host'] ?? $host;
        $path = $parts['path'] ?? '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return 'https://'.$host.$path.$query;
    }

    /**
     * @param  array<string, true>  $seen
     * @param  array{lastmod?: string|null, changefreq?: string, priority?: float|string}  $meta
     * @param  list<array{loc: string, lastmod?: string|null, changefreq?: string, priority?: float|string}>  $entries
     */
    private function pushSitemapEntry(array &$entries, array &$seen, string $loc, array $meta): void
    {
        $loc = $this->canonicalPublicUrl($loc);
        if (isset($seen[$loc])) {
            return;
        }
        $seen[$loc] = true;
        $entries[] = array_merge(['loc' => $loc], $meta);
    }

    /**
     * Build sitemap XML (merge seo_sitemaps, seo_pages static, vehicles, dealers).
     */
    public function getSitemapXml(): string
    {
        if (! $this->isIndexingEnabled()) {
            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
                .'</urlset>';
        }

        try {
            return $this->buildProductionSitemapXml();
        } catch (\Throwable $e) {
            report($e);

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
                .'</urlset>';
        }
    }

    private function buildProductionSitemapXml(): string
    {
        $entries = [];
        $seen = [];

        $this->safelyCollectSitemap(function () use (&$entries, &$seen): void {
            foreach (SeoSitemap::all() as $row) {
                $this->pushSitemapEntry($entries, $seen, (string) ($row->url ?? ''), [
                    'lastmod' => $this->sitemapLastmod($row->lastmod),
                    'changefreq' => $row->changefreq ?? 'weekly',
                    'priority' => $row->priority ?? '0.5',
                ]);
            }
        });

        $staticPageKeyToRoute = [
            'home' => 'home',
            'vehicles' => 'vehicles',
            'privacy-policy' => 'privacy-policy',
            'terms-of-service' => 'terms-of-service',
            'account-deletion' => 'account-deletion',
            'about' => 'about',
            'contact' => 'contact',
            'sell-your-car' => 'sell-your-car',
        ];

        $this->safelyCollectSitemap(function () use (&$entries, &$seen, $staticPageKeyToRoute): void {
            foreach (SeoPage::whereIn('page_type', ['home', 'listing', 'static'])->get() as $seo) {
                $routeName = $staticPageKeyToRoute[$seo->page_key] ?? null;
                if ($routeName) {
                    $this->pushNamedSitemapRoute($entries, $seen, $routeName, [], [
                        'lastmod' => $this->sitemapLastmod($seo->updated_at),
                        'changefreq' => $seo->page_key === 'home' ? 'daily' : 'weekly',
                        'priority' => $seo->page_key === 'home' ? '1.0' : ($seo->page_key === 'vehicles' ? '0.9' : '0.7'),
                    ]);
                }
            }
        });

        $this->pushSitemapEntry($entries, $seen, url('/'), [
            'lastmod' => now()->format('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ]);
        $this->pushNamedSitemapRoute($entries, $seen, 'vehicles', [], [
            'lastmod' => now()->format('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ]);
        $this->pushNamedSitemapRoute($entries, $seen, 'blog.index', [], [
            'lastmod' => now()->format('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ]);
        $this->pushNamedSitemapRoute($entries, $seen, 'sell-your-car', [], [
            'lastmod' => now()->format('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ]);

        $this->safelyCollectSitemap(function () use (&$entries, &$seen): void {
            CmsPost::query()
                ->where('status', CmsPostStatus::PUBLISHED)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->select(['id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->chunkById(500, function ($posts) use (&$entries, &$seen) {
                    foreach ($posts as $post) {
                        $this->pushNamedSitemapRoute($entries, $seen, 'blog.show', ['slug' => $post->slug], [
                            'lastmod' => $this->sitemapLastmod($post->updated_at),
                            'changefreq' => 'weekly',
                            'priority' => '0.7',
                        ]);
                    }
                });
        });

        $this->safelyCollectSitemap(function () use (&$entries, &$seen): void {
            LandingPage::query()
                ->where('status', CmsPostStatus::PUBLISHED)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->select(['id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->chunkById(500, function ($pages) use (&$entries, &$seen) {
                    foreach ($pages as $lp) {
                        $this->pushNamedSitemapRoute($entries, $seen, 'landing.show', ['slug' => $lp->slug], [
                            'lastmod' => $this->sitemapLastmod($lp->updated_at),
                            'changefreq' => 'monthly',
                            'priority' => '0.6',
                        ]);
                    }
                });
        });

        $this->safelyCollectSitemap(function () use (&$entries, &$seen): void {
            Vehicle::query()
                ->where('list_status_id', VehicleListStatus::PUBLISHED)
                ->whereNotNull('slug')
                ->select(['id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->chunkById(500, function ($vehicles) use (&$entries, &$seen) {
                    foreach ($vehicles as $vehicle) {
                        $this->pushNamedSitemapRoute($entries, $seen, 'vehicle.detail', ['vehicle' => $vehicle], [
                            'lastmod' => $this->sitemapLastmod($vehicle->updated_at) ?? now()->format('Y-m-d'),
                            'changefreq' => 'weekly',
                            'priority' => '0.8',
                        ]);
                    }
                });
        });

        $this->safelyCollectSitemap(function () use (&$entries, &$seen): void {
            Dealer::query()
                ->whereNotNull('slug')
                ->whereNotNull('user_id')
                ->whereHas('vehicles', function ($query) {
                    $query->where('list_status_id', VehicleListStatus::PUBLISHED);
                })
                ->select(['id', 'slug', 'user_id', 'updated_at'])
                ->orderBy('id')
                ->chunkById(500, function ($dealers) use (&$entries, &$seen) {
                    foreach ($dealers as $dealer) {
                        if (! Dealer::isSitemapEligible($dealer->slug, true)) {
                            continue;
                        }
                        $this->pushNamedSitemapRoute($entries, $seen, 'dealer.show', ['slug' => $dealer->slug], [
                            'lastmod' => $this->sitemapLastmod($dealer->updated_at) ?? now()->format('Y-m-d'),
                            'changefreq' => 'weekly',
                            'priority' => '0.6',
                        ]);
                    }
                });
        });

        $this->pushSitemapEntry($entries, $seen, url('/byer'), [
            'lastmod' => now()->format('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '0.7',
        ]);

        $this->safelyCollectSitemap(function () use (&$entries, &$seen): void {
            foreach (\App\Models\MarketplaceCity::query()->where('is_active', true)->orderBy('name')->get() as $city) {
                if ($city->isCarsIndexable()) {
                    $this->pushSitemapEntry($entries, $seen, url('/biler-i/'.$city->slug), [
                        'lastmod' => $this->sitemapLastmod($city->last_computed_at) ?? now()->format('Y-m-d'),
                        'changefreq' => 'daily',
                        'priority' => '0.7',
                    ]);
                }
                if ($city->isDealersIndexable()) {
                    $this->pushSitemapEntry($entries, $seen, url('/forhandlere-i/'.$city->slug), [
                        'lastmod' => $this->sitemapLastmod($city->last_computed_at) ?? now()->format('Y-m-d'),
                        'changefreq' => 'weekly',
                        'priority' => '0.65',
                    ]);
                }
            }
        });

        $this->pushNamedSitemapRoute($entries, $seen, 'market-snapshot', [], [
            'lastmod' => now()->format('Y-m-d'),
            'changefreq' => 'weekly',
            'priority' => '0.6',
        ]);

        $this->safelyCollectSitemap(function () use (&$entries, &$seen): void {
            $hubs = app(InventoryHubService::class);
            if ($hubs->isElectricIndexable()) {
                $this->pushNamedSitemapRoute($entries, $seen, 'hubs.electric', [], [
                    'lastmod' => now()->format('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ]);
            }
            if ($hubs->isBrandIndexable(InventoryHubService::VOLKSWAGEN_SLUG)) {
                $this->pushNamedSitemapRoute($entries, $seen, 'hubs.brand', ['brand' => InventoryHubService::VOLKSWAGEN_SLUG], [
                    'lastmod' => now()->format('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ]);
            }
        });

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
        if (! $this->isIndexingEnabled()) {
            return "User-agent: *\nDisallow: /";
        }

        $settings = app(PlatformSettingService::class);
        $mode = $settings->get('seo', 'robots_mode', 'default');
        $custom = (string) $settings->get('seo', 'robots_custom_body', '');

        if ($mode === 'custom' && trim($custom) !== '') {
            return $this->ensureHttpsSitemapDirective(trim($custom));
        }

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /biler',
            'Allow: /biler/',
            'Allow: /dealer-',
            'Allow: /byer',
            'Allow: /biler-i/',
            'Allow: /forhandlere-i/',
            'Disallow: /biler?',  // filter params
            'Disallow: /admin',
            'Disallow: /api',
            'Disallow: /auth',
            'Disallow: /seller-dashboard',
            'Disallow: /test/',
            '',
            'Sitemap: '.$this->canonicalPublicUrl('/sitemap.xml'),
        ];
        return implode("\n", $lines);
    }

    /**
     * @param  array<string, true>  $seen
     * @param  array{lastmod?: string|null, changefreq?: string, priority?: float|string}  $meta
     * @param  list<array{loc: string, lastmod?: string|null, changefreq?: string, priority?: float|string}>  $entries
     * @param  array<string, mixed>  $params
     */
    private function pushNamedSitemapRoute(array &$entries, array &$seen, string $name, array $params, array $meta): void
    {
        $this->safelyCollectSitemap(function () use (&$entries, &$seen, $name, $params, $meta): void {
            if (! Route::has($name)) {
                return;
            }
            $this->pushSitemapEntry($entries, $seen, route($name, $params), $meta);
        });
    }

    private function safelyCollectSitemap(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function sitemapLastmod(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_string($value) && trim($value) !== '') {
            return substr(trim($value), 0, 10);
        }

        return null;
    }

    private function ensureHttpsSitemapDirective(string $body): string
    {
        $rewritten = preg_replace('/^(Sitemap:\s*)http:\/\//im', '$1https://', $body);

        return is_string($rewritten) ? $rewritten : $body;
    }

    /**
     * Short llms.txt for non-Google agents. Google ignores this file.
     */
    public function getLlmsTxt(): string
    {
        $company = CompanyProfile::legalName();
        $cvr = CompanyProfile::cvr();
        $lines = [
            '# '.$company,
            '',
            $company.' (CVR '.$cvr.') is a Danish used-car marketplace at Bilskyen. Dealers and private sellers publish listings; buyers browse, compare, and enquire.',
            '',
            '## Site',
            '- Home: '.$this->canonicalPublicUrl('/'),
            '- Used cars: '.$this->canonicalPublicUrl(route('vehicles')),
            '- Market snapshot: '.$this->canonicalPublicUrl(route('market-snapshot')),
            '- Blog: '.$this->canonicalPublicUrl(route('blog.index')),
            '- About: '.$this->canonicalPublicUrl(route('about')),
            '- Contact: '.$this->canonicalPublicUrl(route('contact')),
            '- Sell your car: '.$this->canonicalPublicUrl(route('sell-your-car')),
            '',
            'Inventory is live on /biler. Do not treat unpublished or draft listings as for sale.',
        ];

        return implode("\n", $lines)."\n";
    }

    private function publicListingImage(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || str_contains($url, 'placeholder-vehicle')) {
            return null;
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    private function omitPlaceholderImages(array $seo): array
    {
        $seo['og_image'] = $this->publicListingImage(is_string($seo['og_image'] ?? null) ? $seo['og_image'] : null);
        $seo['twitter_image'] = $this->publicListingImage(is_string($seo['twitter_image'] ?? null) ? $seo['twitter_image'] : null);

        if (isset($seo['schema_json']) && is_array($seo['schema_json'])) {
            $schemaImage = $seo['schema_json']['image'] ?? null;
            $clean = $this->publicListingImage(is_string($schemaImage) ? $schemaImage : null);
            if ($clean === null) {
                unset($seo['schema_json']['image']);
            } else {
                $seo['schema_json']['image'] = $clean;
            }
        }

        return $seo;
    }
}
