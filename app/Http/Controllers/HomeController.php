<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Category;
use App\Models\ListingType;
use App\Models\PriceType;
use App\Models\BodyType;
use App\Models\GearType;
use App\Models\DmrDriveEnergy;
use App\Models\DmrBrand;
use App\Models\DmrModel;
use App\Models\DmrVariant;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Condition;
use App\Models\SalesType;
use App\Models\Type;
use App\Models\FeaturedListing;
use App\Models\ListingViewsLog;
use App\Services\AuthService;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use App\Services\LookupService;
use App\Services\PageContentService;
use App\Services\SeoService;
use App\Services\VehicleDetailPresentationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService,
        private PageContentService $pageContentService,
        private LookupService $lookupService,
        private SeoService $seoService,
        private VehicleDetailPresentationService $vehicleDetailPresentationService
    ) {}

    /**
     * Show the home page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Fetch filter options for the view
        $currentYear = (int) date('Y');
        $modelYears = collect(range($currentYear, 1975))->map(fn (int $y) => (object) [
            'id' => $y,
            'name' => (string) $y,
        ]);

        $filterOptions = [
            'categories' => Category::orderBy('name')->get(),
            'fuelTypes' => DmrDriveEnergy::orderBy('name')->get(),
            'modelYears' => $modelYears,
        ];

        // Fetch featured vehicles
        $featuredVehicles = FeaturedListing::with([
            'vehicle.images',
            'vehicle.variant',
            'vehicle.dmrFactVehicle.variant',
        ])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($featuredListing) {
                $vehicle = $featuredListing->vehicle;
                if (!$vehicle) {
                    return null;
                }

                // Get first image
                $firstImage = $vehicle->images->first();
                $imageUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';

                // Build title
                $title = $vehicle->title ?? trim(($vehicle->brand_name ?? '') . ' ' . ($vehicle->model_name ?? ''));
                
                return [
                    'id' => $vehicle->id,
                    'slug' => $vehicle->slug,
                    'title' => $title,
                    'variant_name' => $vehicle->variant_name,
                    'price' => $vehicle->price ?? 0,
                    'image' => $imageUrl,
                    'km_driven' => $vehicle->km_driven ?? 0,
                    'engine_power_hp' => $vehicle->engine_power_hp,
                    'model_year_name' => $vehicle->model_year_name,
                    'fuel_type_name' => $vehicle->fuel_type_name,
                    'gear_type_name' => $vehicle->gear_type_name,
                    'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                    'dealer_id' => $vehicle->dealer_id,
                    'seller_address' => $vehicle->seller_address,
                    'seller_postcode' => $vehicle->seller_postcode,
                    'sales_type_name' => null,
                ];
            })
            ->filter() // Remove null entries
            ->values(); // Re-index array

        // Get home page content from cache
        $homePageContent = $this->pageContentService->getHomePageContent('home');
        $seo = $this->seoService->getForPage('home', 'home');

        return view('home', [
            'filterOptions' => $filterOptions,
            'featuredVehicles' => $featuredVehicles,
            'homePageContent' => $homePageContent,
            'seo' => $seo,
        ]);
    }

    /**
     * Show the profile page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showProfile(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        return view('profile', [
            'user' => $user,
        ]);
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        // Capture before state for audit log
        $beforeData = $user->only(['name', 'email', 'phone', 'address']);

        // Update user profile
        $user->name = $validated['name'];
        $user->email = strtolower($validated['email']);
        $user->phone = $validated['phone'] ?? null;
        $user->address = $validated['address'] ?? null;
        $user->save();

        // Log audit trail
        try {
            $afterData = $user->only(['name', 'email', 'phone', 'address']);
            $this->auditLogService->logUpdate(
                $user,
                'User',
                $user->id,
                $beforeData,
                $afterData,
                $request,
                null,
                null,
                'User profile updated via web',
                ['user', 'profile', 'web']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for user profile update', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect('/profile')->with('status', __('messages.messages.profile_updated_successfully'));
    }

    /**
     * Show the about page
     *
     * @return \Illuminate\View\View
     */
    public function showAbout()
    {
        // Get about page content from cache
        $aboutPageContent = $this->pageContentService->getHomePageContent('about');
        
        // Get about page images from cache
        $aboutPageImages = $this->pageContentService->getPageImages('about');
        
        $seo = $this->seoService->getForPage('static', 'about');

        return view('about', [
            'aboutPageContent' => $aboutPageContent,
            'aboutPageImages' => $aboutPageImages,
            'seo' => $seo,
        ]);
    }

    /**
     * Show the contact page
     *
     * @return \Illuminate\View\View
     */
    public function showContact()
    {
        // Get contact page content from cache
        $contactPageContent = $this->pageContentService->getHomePageContent('contact');
        
        // Get contact page images from cache
        $contactPageImages = $this->pageContentService->getPageImages('contact');
        
        $seo = $this->seoService->getForPage('static', 'contact');

        return view('contact', [
            'contactPageContent' => $contactPageContent,
            'contactPageImages' => $contactPageImages,
            'seo' => $seo,
        ]);
    }

    /**
     * Show the privacy policy page
     *
     * @return \Illuminate\View\View
     */
    public function showPrivacyPolicy()
    {
        // Get privacy policy page content from cache
        $privacyPageContent = $this->pageContentService->getHomePageContent('privacy');
        
        // Get privacy policy page images from cache
        $privacyPageImages = $this->pageContentService->getPageImages('privacy');
        
        $seo = $this->seoService->getForPage('static', 'privacy-policy');

        return view('privacy-policy', [
            'privacyPageContent' => $privacyPageContent,
            'privacyPageImages' => $privacyPageImages,
            'seo' => $seo,
        ]);
    }

    /**
     * Show the terms of service page
     *
     * @return \Illuminate\View\View
     */
    public function showTermsOfService()
    {
        // Get terms of service page content from cache
        $termsPageContent = $this->pageContentService->getHomePageContent('terms');
        
        // Get terms of service page images from cache
        $termsPageImages = $this->pageContentService->getPageImages('terms');
        
        $seo = $this->seoService->getForPage('static', 'terms-of-service');

        return view('terms-of-service', [
            'termsPageContent' => $termsPageContent,
            'termsPageImages' => $termsPageImages,
            'seo' => $seo,
        ]);
    }

    /** Keys that can come from GET and populate the vehicles sidebar (from vehicle_listing_filters.txt) */
    private const VEHICLE_FILTER_KEYS = [
        'brand_id', 'model_id', 'variant_id', 'model_year', 'fuel_type_id', 'category_id', 'listing_type_id',
        'gear_type_id', 'body_type_id', 'color_id', 'type_id', 'condition_id',
        'sales_type_id', 'price_type_id', 'euronom_id', 'euronorm', 'use_id', 'transmission_id',
        'equipment_id', 'equipment_ids',
        'km_driven_from', 'km_driven_to', 'price_from', 'price_to', 'battery_capacity_from', 'battery_capacity_to',
        'range_km_from', 'range_km_to', 'engine_power_from', 'engine_power_to', 'engine_power_kw_from', 'engine_power_kw_to',
        'towing_weight',
        'ownership_tax_from', 'ownership_tax_to', 'first_registration_year_from', 'first_registration_year_to',
        'fuel_efficiency_from', 'fuel_efficiency_to', 'model_year_from', 'model_year_to',
        'year_from', 'year_to',
        'electrical_consumption_from', 'electrical_consumption_to', 'km_per_liter_from', 'km_per_liter_to',
        'max_speed_from', 'max_speed_to', 'maximum_weight_kg_from', 'maximum_weight_kg_to',
        'top_speed_from', 'top_speed_to', 'weight_from', 'weight_to', 'engine_displacement_from', 'engine_displacement_to',
        'engine_cylinders', 'doors', 'door_count', 'seats_min', 'seats_max', 'wheels', 'axles', 'axle_count',
        'drive_axle_count', 'specifications_airbags', 'charging_type', 'emission_norm_id',
        'ncap_five', 'ncap_test', 'is_import', 'is_factory_new', 'search', 'sort',
    ];

    /**
     * Build currentFilters from request for the vehicles view (sidebar pre-fill). Normalizes arrays.
     */
    private function buildCurrentFilters(Request $request): array
    {
        $currentFilters = [];
        $arrayKeys = ['listing_type_id', 'equipment_ids', 'body_type_id', 'fuel_type_id', 'gear_type_id', 'price_type_id', 'sales_type_id', 'drive_axles', 'drive_axle_count', 'seller_type', 'brand_id', 'model_id', 'variant_id'];
        foreach (self::VEHICLE_FILTER_KEYS as $key) {
            $value = $request->input($key);
            if ($value === null || $value === '') {
                continue;
            }
            if ($key === 'variant_id' && is_string($value) && str_contains($value, ',')) {
                $currentFilters[$key] = array_values(array_filter(array_map('intval', explode(',', $value))));

                continue;
            }
            if (in_array($key, $arrayKeys, true)) {
                $currentFilters[$key] = is_array($value) ? $value : [$value];
            } else {
                $currentFilters[$key] = $value;
            }
        }
        return $currentFilters;
    }

    /**
     * Show the vehicles listing page. GET params populate sidebar (currentFilters); initial list uses same filters.
     */
    public function showVehicles(Request $request)
    {
        $currentFilters = $this->buildCurrentFilters($request);

        // Comma-separated `model_year` (multiple years from home): hydrate range fields only when a single year.
        if (! empty($currentFilters['model_year']) && is_string($currentFilters['model_year'])) {
            $parts = array_values(array_filter(array_map('intval', explode(',', $currentFilters['model_year']))));
            $maxY = (int) date('Y');
            $parts = array_values(array_filter($parts, fn ($y) => $y >= 1975 && $y <= $maxY));
            if (count($parts) === 1
                && empty($currentFilters['model_year_from'])
                && empty($currentFilters['model_year_to'])) {
                $y = (string) $parts[0];
                $currentFilters['model_year_from'] = $y;
                $currentFilters['model_year_to'] = $y;
                unset($currentFilters['model_year']);
            }
        }

        $limit = (int) $request->input('limit', 15);
        $page = (int) $request->input('page', 1);

        $input = $currentFilters;
        if (isset($input['km_driven_from'])) {
            $input['mileage_from'] = $input['km_driven_from'];
        }
        if (isset($input['km_driven_to'])) {
            $input['mileage_to'] = $input['km_driven_to'];
        }
        // Accept both euronorm (name) and euronom_id; normalize to euronom_id for backend filter
        if (! empty($input['euronorm']) && empty($input['euronom_id'])) {
            $euronom = \App\Models\Euronom::where('name', trim((string) $input['euronorm']))->first();
            if ($euronom) {
                $input['euronom_id'] = $euronom->id;
            }
            unset($input['euronorm']);
        }
        if (isset($input['year_from']) && ! isset($input['model_year_from'])) {
            $input['model_year_from'] = $input['year_from'];
        }
        if (isset($input['year_to']) && ! isset($input['model_year_to'])) {
            $input['model_year_to'] = $input['year_to'];
        }
        unset($input['year_from'], $input['year_to']);

        $vehicles = $this->vehicleService->getPublicVehiclesWithAdvancedFilters([], $input, $limit, $page);

        $showNoResultsMessage = $vehicles->total() === 0;
        $fallbackVehicles = null;
        if ($showNoResultsMessage) {
            $fallbackVehicles = $this->vehicleService->getPublicVehicles([], $limit, 1);
        }

        $vehicles = $vehicles->through(fn (Vehicle $v) => $this->vehicleListingItemRow($v));
        if ($fallbackVehicles !== null) {
            $fallbackVehicles = $fallbackVehicles->through(fn (Vehicle $v) => $this->vehicleListingItemRow($v));
        }

        // Because `/api/v1/constants` no longer includes brands/models/types (to reduce load),
        // we only fetch the currently-selected values for initial dropdown rendering.
        $selectedBrandIds = array_map(fn ($v) => (int) $v, $currentFilters['brand_id'] ?? []);
        $selectedModelIds = array_map(fn ($v) => (int) $v, $currentFilters['model_id'] ?? []);
        $selectedVariantIds = array_map(fn ($v) => (int) $v, $currentFilters['variant_id'] ?? []);
        $selectedTypeId = isset($currentFilters['type_id']) && $currentFilters['type_id'] !== '' ? (int) $currentFilters['type_id'] : null;

        $selectedBrands = !empty($selectedBrandIds)
            ? DmrBrand::whereIn('id', $selectedBrandIds)->orderBy('name')->get(['id', 'name'])
            : collect();
        $selectedModels = !empty($selectedModelIds)
            ? DmrModel::whereIn('id', $selectedModelIds)->orderBy('name')->get(['id', 'name', 'brand_id'])
            : collect();
        $selectedVariants = !empty($selectedVariantIds)
            ? DmrVariant::whereIn('id', $selectedVariantIds)->orderBy('name')->get(['id', 'name', 'model_id'])
            : collect();
        $selectedType = $selectedTypeId ? Type::select(['id', 'name'])->find($selectedTypeId) : null;

        $constants = $this->lookupService->getPublicConstants();
        $seo = $this->seoService->getForPage('listing', 'vehicles');

        $vehicleSortLabels = $this->buildVehicleListingSortLabels();
        $rawSortQuery = $request->query('sort');
        $currentListingSort = VehicleService::normalizePublicListingSort(
            is_string($rawSortQuery) ? $rawSortQuery : null
        );

        return view('vehicles', compact(
            'vehicles',
            'constants',
            'currentFilters',
            'seo',
            'showNoResultsMessage',
            'fallbackVehicles',
            'selectedBrands',
            'selectedModels',
            'selectedVariants',
            'selectedType',
            'vehicleSortLabels',
            'currentListingSort'
        ));
    }

    /**
     * Presentation fields for the vehicle listing card component (initial SSR).
     *
     * @return array{vehicle: Vehicle, imgUrl: string, imgAlt: string, salesTypeName: string|null}
     */
    private function vehicleListingItemRow(Vehicle $vehicle): array
    {
        $imgUrl = $vehicle->images->first()?->thumbnail_url ?? '/placeholder-vehicle.jpg';
        $imgAlt = trim(($vehicle->brand_name ?? '').' '.($vehicle->model_name ?? ''));

        $salesTypeName = null;
        if ($vehicle->relationLoaded('salesType') && $vehicle->salesType) {
            $salesTypeName = $vehicle->salesType->name;
        } elseif ($vehicle->sales_type_id) {
            $salesTypeName = $vehicle->salesType()->value('name');
        }

        return [
            'vehicle' => $vehicle,
            'imgUrl' => $imgUrl,
            'imgAlt' => $imgAlt,
            'salesTypeName' => $salesTypeName,
        ];
    }

    /**
     * Human-readable labels for each public listing sort key (column + direction).
     *
     * @return array<string, string>
     */
    private function buildVehicleListingSortLabels(): array
    {
        $labels = [];
        foreach (VehicleService::publicListingSortOptionKeys() as $sortKey) {
            if (preg_match('/^(.+)_(asc|desc)$/', $sortKey, $m)) {
                $colKey = 'messages.pages.vehicles.sort.columns.'.$m[1];
                $colLabel = Lang::has($colKey)
                    ? __($colKey)
                    : Str::headline(str_replace('_', ' ', $m[1]));
                $labels[$sortKey] = $colLabel
                    . ' — '
                    . __('messages.pages.vehicles.sort.dir_'.$m[2]);
            }
        }

        return $labels;
    }

    /**
     * Show the vehicle detail page
     *
     * @param Request $request
     * @param \App\Models\Vehicle $vehicle
     * @return \Illuminate\View\View
     */
    public function showVehicleDetail(Request $request, Vehicle $vehicle)
    {
        $vehicle->load(array_merge($this->vehicleDetailPresentationService->detailEagerLoads(), [
            'images' => function ($q) {
                $q->orderBy('sort_order');
            },
            'user',
            'dealer.owner',
        ]));

        // Get authenticated user (if any)
        $user = $this->authService->getAuthenticatedUser($request);
        
        // Get IP address and user agent
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        DB::transaction(function () use ($vehicle, $user, $ipAddress, $userAgent) {
            ListingViewsLog::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $user?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'viewed_at' => now(),
            ]);
        });
        
        $seo = $this->seoService->getForPage('vehicle', $vehicle->slug);

        return view('vehicle-detail', [
            'vehicle' => $vehicle,
            'vehicleDetail' => $this->vehicleDetailPresentationService->buildDetailPayload($vehicle),
            'seo' => $seo,
        ]);
    }

    /**
     * Show the favorites page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showFavorites(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect()->route('login')->with('return_url', '/favorites');
        }

        // Get user's favorite vehicles
        $favorites = \App\Models\Favorite::where('user_id', $user->id)
            ->with([
                'vehicle.images',
                'vehicle.dmrFactVehicle.variant.model.brand',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('favorites', [
            'favorites' => $favorites,
        ]);
    }
}

