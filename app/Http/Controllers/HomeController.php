<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Category;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\ModelYear;
use App\Models\ListingType;
use App\Models\PriceType;
use App\Models\BodyType;
use App\Models\GearType;
use App\Models\FuelType;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Condition;
use App\Models\SalesType;
use App\Models\FeaturedListing;
use App\Models\ListingViewsLog;
use App\Services\AuthService;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use App\Services\LookupService;
use App\Services\PageContentService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService,
        private PageContentService $pageContentService,
        private LookupService $lookupService,
        private SeoService $seoService
    ) {}

    /**
     * Show the home page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Fetch filter options for the view
        $filterOptions = [
            'categories' => Category::orderBy('name')->get(),
            'fuelTypes' => FuelType::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'models' => VehicleModel::orderBy('name')->get(),
            'modelYears' => ModelYear::orderBy('name', 'desc')->get(),
        ];

        // Fetch featured vehicles
        $featuredVehicles = FeaturedListing::with([
            'vehicle.images',
            'vehicle.details',
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

                // Get details
                $details = $vehicle->details;

                // Build title
                $title = $vehicle->title ?? trim(($vehicle->brand_name ?? '') . ' ' . ($vehicle->model_name ?? ''));
                
                return [
                    'id' => $vehicle->id,
                    'slug' => $vehicle->slug,
                    'title' => $title,
                    'version' => $vehicle->version ?? '',
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
                    'sales_type_name' => $details?->sales_type_name ?? $details?->salesType?->name,
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

        return redirect('/profile')->with('status', 'Profile updated successfully!');
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
        'brand_id', 'model_id', 'model_year_id', 'fuel_type_id', 'category_id', 'listing_type_id',
        'gear_type_id', 'body_type_id', 'color_id', 'variant_id', 'type_id', 'condition_id',
        'sales_type_id', 'price_type_id', 'euronom_id', 'euronorm', 'use_id', 'transmission_id',
        'equipment_id', 'equipment_ids',
        'km_driven_from', 'km_driven_to', 'price_from', 'price_to', 'battery_capacity_from', 'battery_capacity_to',
        'range_km_from', 'range_km_to', 'engine_power_from', 'engine_power_to', 'towing_weight',
        'ownership_tax_from', 'ownership_tax_to', 'first_registration_year_from', 'first_registration_year_to',
        'fuel_efficiency_from', 'fuel_efficiency_to', 'year_from', 'year_to',
        'top_speed_from', 'top_speed_to', 'weight_from', 'weight_to', 'engine_displacement_from', 'engine_displacement_to',
        'engine_cylinders', 'doors', 'seats_min', 'seats_max', 'wheels', 'axles', 'drive_axles', 'airbags',
        'charging_type', 'ncap_five', 'is_import', 'is_factory_new', 'search', 'sort',
    ];

    /**
     * Build currentFilters from request for the vehicles view (sidebar pre-fill). Normalizes arrays.
     */
    private function buildCurrentFilters(Request $request): array
    {
        $currentFilters = [];
        $arrayKeys = ['listing_type_id', 'equipment_ids', 'body_type_id', 'fuel_type_id', 'gear_type_id', 'price_type_id', 'sales_type_id', 'drive_axles', 'seller_type', 'brand_id', 'model_id'];
        foreach (self::VEHICLE_FILTER_KEYS as $key) {
            $value = $request->input($key);
            if ($value === null || $value === '') {
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

        $limit = (int) $request->input('limit', 15);
        $page = (int) $request->input('page', 1);

        $advancedKeys = [
            'price_from', 'price_to', 'brand_id', 'model_id', 'model_year_id', 'year_from', 'year_to',
            'mileage_from', 'mileage_to', 'km_driven_from', 'km_driven_to', 'listing_type_id', 'category_id',
            'price_type_id', 'condition_id', 'body_type_id', 'fuel_type_id', 'gear_type_id', 'drive_axles',
            'first_registration_year_from', 'first_registration_year_to', 'sales_type_id',
            'top_speed_from', 'top_speed_to', 'engine_power_from', 'engine_power_to',
            'ownership_tax_from', 'ownership_tax_to', 'battery_capacity_from', 'battery_capacity_to',
            'range_km_from', 'range_km_to', 'charging_type', 'fuel_efficiency_from', 'fuel_efficiency_to',
            'euronorm', 'euronom_id', 'color_id', 'doors', 'seats_min', 'seats_max', 'weight_from', 'weight_to',
            'wheels', 'axles', 'engine_cylinders', 'engine_displacement_from', 'engine_displacement_to',
            'airbags', 'ncap_five', 'equipment_ids', 'equipment_id', 'variant_id', 'type_id', 'use_id', 'transmission_id', 'towing_weight', 'is_import', 'is_factory_new',
        ];
        $basicKeys = ['search', 'category_id', 'brand_id', 'model_id', 'model_year_id', 'fuel_type_id', 'price_from', 'price_to', 'listing_type_id', 'sort'];
        $hasAdvanced = !empty(array_intersect_key($currentFilters, array_flip($advancedKeys)));

        if ($hasAdvanced) {
            $input = $currentFilters;
            if (isset($input['km_driven_from'])) {
                $input['mileage_from'] = $input['km_driven_from'];
            }
            if (isset($input['km_driven_to'])) {
                $input['mileage_to'] = $input['km_driven_to'];
            }
            // Accept both euronorm (name) and euronom_id; normalize to euronom_id for backend filter (DB column is euronom_id)
            if (!empty($input['euronorm']) && empty($input['euronom_id'])) {
                $euronom = \App\Models\Euronom::where('name', trim($input['euronorm']))->first();
                if ($euronom) {
                    $input['euronom_id'] = $euronom->id;
                }
                unset($input['euronorm']);
            }
            $basicFilters = array_intersect_key($input, array_flip($basicKeys));
            $advancedFilters = array_intersect_key($input, array_flip($advancedKeys));
            $vehicles = $this->vehicleService->getPublicVehiclesWithAdvancedFilters($basicFilters, $advancedFilters, $limit, $page);
        } else {
            $filters = array_intersect_key($currentFilters, array_flip(array_merge($basicKeys, ['km_driven_from', 'km_driven_to'])));
            $vehicles = $this->vehicleService->getPublicVehicles($filters, $limit, $page);
        }

        $constants = $this->lookupService->getPublicConstants();
        $seo = $this->seoService->getForPage('listing', 'vehicles');

        return view('vehicles', compact('vehicles', 'constants', 'currentFilters', 'seo'));
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
        $vehicle->load([
            'details',
            'equipment',
            'listingType',
            'images',
            'user',
            'dealer.owner'
        ]);

        // Get authenticated user (if any)
        $user = $this->authService->getAuthenticatedUser($request);
        
        // Get IP address and user agent
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Increment view count and log the view
        DB::transaction(function () use ($vehicle, $user, $ipAddress, $userAgent) {
            // Increment views_count in vehicle_details
            if ($vehicle->details) {
                $vehicle->details->increment('views_count');
            } else {
                // Create vehicle_details if it doesn't exist
                $vehicle->details()->create([
                    'views_count' => 1,
                ]);
            }

            // Create view log entry
            ListingViewsLog::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $user?->id,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'viewed_at' => now(),
            ]);
        });

        // Reload vehicle with updated details
        $vehicle->load('details');
        $seo = $this->seoService->getForPage('vehicle', $vehicle->slug);

        return view('vehicle-detail', [
            'vehicle' => $vehicle,
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
                'vehicle.listingType',
                'vehicle.details'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('favorites', [
            'favorites' => $favorites,
        ]);
    }
}

