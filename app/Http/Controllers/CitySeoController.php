<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceCity;
use App\Services\CityIndexService;
use App\Services\Seo\SchemaBuilderService;
use App\Services\SeoService;
use App\Services\VehicleListingPresentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitySeoController extends Controller
{
    public function __construct(
        private CityIndexService $cityIndexService,
        private SeoService $seoService,
        private SchemaBuilderService $schemaBuilder,
        private VehicleListingPresentationService $listingPresentation,
    ) {}

    public function index(): View
    {
        $cities = MarketplaceCity::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('published_vehicle_count', '>', 0)
                    ->orWhere('dealer_count', '>', 0);
            })
            ->orderByDesc('published_vehicle_count')
            ->orderBy('name')
            ->get();

        $seo = $this->seoService->resolveForCitiesIndex($cities->count());

        return view('cities-index', [
            'cities' => $cities,
            'seo' => $seo,
            'topCities' => $this->cityIndexService->topCities(12),
        ]);
    }

    public function cars(Request $request, string $city): View
    {
        $marketplaceCity = $this->cityIndexService->findBySlug($city);
        if (! $marketplaceCity) {
            abort(404);
        }

        $vehicles = $this->cityIndexService->vehiclesQueryForCity($marketplaceCity)
            ->with([
                'images' => fn ($q) => $q->orderBy('sort_order'),
                'dealer',
                'salesType',
                'brand',
                'model',
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(24)
            ->get();

        $dealers = $this->cityIndexService->dealersQueryForCity($marketplaceCity)
            ->with('owner:id,name')
            ->orderBy('city')
            ->limit(12)
            ->get();

        $relatedCities = MarketplaceCity::query()
            ->where('is_active', true)
            ->where('id', '!=', $marketplaceCity->id)
            ->where('published_vehicle_count', '>=', MarketplaceCity::MIN_VEHICLES_FOR_INDEX)
            ->when($marketplaceCity->region, fn ($q) => $q->where('region', $marketplaceCity->region))
            ->orderByDesc('published_vehicle_count')
            ->limit(8)
            ->get();

        if ($relatedCities->count() < 4) {
            $relatedCities = MarketplaceCity::query()
                ->where('is_active', true)
                ->where('id', '!=', $marketplaceCity->id)
                ->where('published_vehicle_count', '>=', MarketplaceCity::MIN_VEHICLES_FOR_INDEX)
                ->orderByDesc('published_vehicle_count')
                ->limit(8)
                ->get();
        }

        $brandNames = collect($marketplaceCity->top_brands ?? [])
            ->pluck('name')
            ->filter()
            ->take(5)
            ->values()
            ->all();

        $seo = $this->seoService->resolveForCityCars($marketplaceCity, [
            'vehicle_count' => $marketplaceCity->published_vehicle_count,
            'min_price' => $marketplaceCity->min_price,
            'max_price' => $marketplaceCity->max_price,
            'brands' => $brandNames,
        ]);

        $faqs = $this->carsFaqs($marketplaceCity);
        $seo['faq_json'] = $faqs;
        $seo['schema_json'] = $this->buildCarsSchemas($marketplaceCity, $vehicles, $faqs, $seo);

        return view('city-cars', [
            'city' => $marketplaceCity,
            'vehicles' => $vehicles,
            'dealers' => $dealers,
            'relatedCities' => $relatedCities,
            'brandNames' => $brandNames,
            'seo' => $seo,
            'listingPresentation' => $this->listingPresentation,
        ]);
    }

    public function dealers(string $city): View
    {
        $marketplaceCity = $this->cityIndexService->findBySlug($city);
        if (! $marketplaceCity) {
            abort(404);
        }

        $dealers = $this->cityIndexService->dealersQueryForCity($marketplaceCity)
            ->with(['owner:id,name,phone,email', 'vehicles' => function ($q) {
                $q->where('list_status_id', \App\Constants\VehicleListStatus::PUBLISHED);
            }])
            ->orderBy('city')
            ->get();

        $vehiclesPreview = $this->cityIndexService->vehiclesQueryForCity($marketplaceCity)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $relatedCities = MarketplaceCity::query()
            ->where('is_active', true)
            ->where('id', '!=', $marketplaceCity->id)
            ->where('dealer_count', '>=', MarketplaceCity::MIN_DEALERS_FOR_INDEX)
            ->orderByDesc('dealer_count')
            ->limit(8)
            ->get();

        $seo = $this->seoService->resolveForCityDealers($marketplaceCity, [
            'dealer_count' => $marketplaceCity->dealer_count,
        ]);

        $faqs = $this->dealersFaqs($marketplaceCity);
        $seo['faq_json'] = $faqs;
        $seo['schema_json'] = $this->buildDealersSchemas($marketplaceCity, $dealers, $faqs, $seo);

        return view('city-dealers', [
            'city' => $marketplaceCity,
            'dealers' => $dealers,
            'vehiclesPreview' => $vehiclesPreview,
            'relatedCities' => $relatedCities,
            'seo' => $seo,
            'listingPresentation' => $this->listingPresentation,
        ]);
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function carsFaqs(MarketplaceCity $city): array
    {
        return [
            [
                'question' => __('messages.pages.cities.faq_cars_q1', ['city' => $city->name]),
                'answer' => __('messages.pages.cities.faq_cars_a1', [
                    'city' => $city->name,
                    'count' => $city->published_vehicle_count,
                ]),
            ],
            [
                'question' => __('messages.pages.cities.faq_cars_q2', ['city' => $city->name]),
                'answer' => __('messages.pages.cities.faq_cars_a2', [
                    'city' => $city->name,
                    'dealers' => $city->dealer_count,
                ]),
            ],
            [
                'question' => __('messages.pages.cities.faq_cars_q3', ['city' => $city->name]),
                'answer' => __('messages.pages.cities.faq_cars_a3', ['city' => $city->name]),
            ],
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function dealersFaqs(MarketplaceCity $city): array
    {
        return [
            [
                'question' => __('messages.pages.cities.faq_dealers_q1', ['city' => $city->name]),
                'answer' => __('messages.pages.cities.faq_dealers_a1', [
                    'city' => $city->name,
                    'count' => $city->dealer_count,
                ]),
            ],
            [
                'question' => __('messages.pages.cities.faq_dealers_q2', ['city' => $city->name]),
                'answer' => __('messages.pages.cities.faq_dealers_a2', [
                    'city' => $city->name,
                    'vehicles' => $city->published_vehicle_count,
                ]),
            ],
            [
                'question' => __('messages.pages.cities.faq_dealers_q3', ['city' => $city->name]),
                'answer' => __('messages.pages.cities.faq_dealers_a3', ['city' => $city->name]),
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Vehicle>  $vehicles
     * @param  list<array{question: string, answer: string}>  $faqs
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    private function buildCarsSchemas(MarketplaceCity $city, $vehicles, array $faqs, array $seo): array
    {
        $itemListElement = [];
        $position = 1;
        foreach ($vehicles->take(20) as $vehicle) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => route('vehicle.detail', $vehicle),
                'name' => $vehicle->title,
            ];
        }

        $graph = [
            [
                '@type' => 'ItemList',
                'name' => __('messages.pages.cities.cars_heading', ['city' => $city->name]),
                'numberOfItems' => $city->published_vehicle_count,
                'itemListElement' => $itemListElement,
            ],
            $this->schemaBuilder->build('FAQPage', ['faqs' => $faqs]),
            $this->schemaBuilder->build('BreadcrumbList', [
                'items' => $seo['breadcrumbs_json'] ?? [],
            ]),
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_map(function ($node) {
                unset($node['@context']);

                return $node;
            }, $graph),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Dealer>  $dealers
     * @param  list<array{question: string, answer: string}>  $faqs
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    private function buildDealersSchemas(MarketplaceCity $city, $dealers, array $faqs, array $seo): array
    {
        $itemListElement = [];
        $position = 1;
        foreach ($dealers as $dealer) {
            $name = $dealer->owner?->name ?: ($dealer->slug ?? 'Dealer');
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => url('/dealer-'.$dealer->slug),
                'name' => $name,
            ];
        }

        $graph = [
            [
                '@type' => 'ItemList',
                'name' => __('messages.pages.cities.dealers_heading', ['city' => $city->name]),
                'numberOfItems' => $city->dealer_count,
                'itemListElement' => $itemListElement,
            ],
            $this->schemaBuilder->build('FAQPage', ['faqs' => $faqs]),
            $this->schemaBuilder->build('BreadcrumbList', [
                'items' => $seo['breadcrumbs_json'] ?? [],
            ]),
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_map(function ($node) {
                unset($node['@context']);

                return $node;
            }, $graph),
        ];
    }
}
