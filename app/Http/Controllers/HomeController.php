<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Constants\VehicleListStatus;
use App\Constants\VehicleSearchFilters;
use App\Models\Category;
use App\Models\ListingType;
use App\Models\PriceType;
use App\Models\BodyType;
use App\Models\GearType;
use App\Models\DmrDriveEnergy;
use App\Models\DmrBrand;
use App\Models\DmrModel;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Condition;
use App\Models\SalesType;
use App\Models\Type;
use App\Models\FeaturedListing;
use App\Models\ListingViewsLog;
use App\Models\PageContent;
use App\Services\AuthService;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use App\Services\LookupService;
use App\Services\PageContentService;
use App\Services\SeoService;
use App\Services\VehicleDetailPresentationService;
use App\Services\VehicleViewService;
use App\Services\MailService;
use App\Services\Finance\FinanceCalculatorService;
use App\Services\VehicleTrustReportService;
use App\Services\MarketPricingService;
use App\Services\VehicleListingPresentationService;
use App\Services\Marketing\MetaConversionsApiService;
use App\Services\AiService;
use App\Services\FaqContentService;
use App\Services\PlatformSettingService;
use App\Services\SearchQueryLogService;
use App\Services\SuggestionService;
use App\Mail\ContactMessageMail;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
class HomeController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService,
        private PageContentService $pageContentService,
        private LookupService $lookupService,
        private SeoService $seoService,
        private VehicleDetailPresentationService $vehicleDetailPresentationService,
        private VehicleViewService $vehicleViewService,
        private MailService $mailService,
        private FinanceCalculatorService $financeCalculatorService,
        private VehicleTrustReportService $trustReportService,
        private MarketPricingService $marketPricingService,
        private VehicleListingPresentationService $vehicleListingPresentationService,
        private MetaConversionsApiService $metaConversionsApiService,
        private FaqContentService $faqContentService,
        private PlatformSettingService $platformSettingService,
        private AiService $aiService,
    ) {}

    /**
     * Show the home page
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $customDealer = $request->attributes->get('custom_domain_dealer');
        if ($customDealer) {
            return app(DealerController::class)->show($request, $customDealer->slug);
        }

        // Fetch filter options for the view
        $currentYear = (int) date('Y');
        $modelYears = collect(range($currentYear, 1975))->map(fn (int $y) => (object) [
            'id' => $y,
            'name' => (string) $y,
        ]);

        $filterOptions = [
            'fuelTypes' => DmrDriveEnergy::orderBy('name')->get(),
            'modelYears' => $modelYears,
        ];

        $publishedVehicleCount = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)->count();
        $listingTypes = $this->lookupService->getListingTypes();

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
        $locale = app()->getLocale();
        $sessionSeed = null;
        try {
            $sessionSeed = $request->hasSession() ? $request->session()->getId() : null;
        } catch (\Throwable) {
            $sessionSeed = null;
        }

        return view('home', [
            'filterOptions' => $filterOptions,
            'featuredVehicles' => $featuredVehicles,
            'homePageContent' => $homePageContent,
            'seo' => $seo,
            'publishedVehicleCount' => $publishedVehicleCount,
            'listingTypes' => $listingTypes,
            'currentYear' => $currentYear,
            'filterPriceMax' => VehicleSearchFilters::PRICE_MAX,
            'filterKmMax' => VehicleSearchFilters::KM_MAX,
            'lifestyleChips' => app(SuggestionService::class)->lifestyleChips($locale, $sessionSeed, 2),
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

        return redirect()->route('profile')->with('status', __('messages.messages.profile_updated_successfully'));
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
     * Handle contact form submission
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'subject' => 'required|in:vehicle-inquiry,financing,service-appointment,general',
            'message' => 'required|string|max:5000',
        ]);

        $contactPageContent = $this->pageContentService->getHomePageContent('contact');
        $recipientEmail = $contactPageContent['contact_email'] ?? 'info@bilskyen.dk';
        $subjectLabel = $this->getContactSubjectLabel($validated['subject']);

        $sent = $this->mailService->sendMailable(
            $recipientEmail,
            new ContactMessageMail(
                senderName: $validated['name'],
                senderEmail: $validated['email'],
                subjectLabel: $subjectLabel,
                senderMessage: $validated['message'],
            ),
            [
                'mail_type' => 'contact_message_received',
                'sender_email' => $validated['email'],
            ],
            false
        );

        if (!$sent) {
            return back()
                ->withInput()
                ->with('error', __('messages.pages.contact.send_error'));
        }

        return redirect()
            ->route('contact')
            ->with('success', __('messages.pages.contact.send_success'));
    }

    private function getContactSubjectLabel(string $subject): string
    {
        return match ($subject) {
            'vehicle-inquiry' => __('messages.pages.contact.vehicle_inquiry'),
            'financing' => __('messages.pages.contact.financing_question'),
            'service-appointment' => __('messages.pages.contact.service_appointment'),
            'general' => __('messages.pages.contact.general_question'),
            default => $subject,
        };
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

        $privacyLastUpdated = PageContent::where('page_name', 'privacy')
            ->where('section_key', 'privacy_body')
            ->value('updated_at');

        return view('privacy-policy', [
            'privacyPageContent' => $privacyPageContent,
            'privacyPageImages' => $privacyPageImages,
            'privacyLastUpdated' => $privacyLastUpdated,
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

    /**
     * Account deletion instructions (mobile store compliance).
     */
    public function showAccountDeletion()
    {
        $seo = $this->seoService->getForPage('static', 'account-deletion');

        return view('account-deletion', [
            'seo' => $seo,
        ]);
    }

    /**
     * Public FAQ / help page with optional on-page chatbot.
     */
    public function showFaq()
    {
        if (! $this->platformSettingService->isFaqPageEnabled()) {
            abort(404);
        }

        $faq = $this->faqContentService->getPublicContent();
        $seo = $this->seoService->getForPage('static', 'faq');
        $qaPairs = $this->faqContentService->flattenQaPairs($faq['sections']);

        $faqSchema = null;
        if ($qaPairs !== []) {
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static fn (array $pair) => [
                    '@type' => 'Question',
                    'name' => $pair['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $pair['answer'],
                    ],
                ], $qaPairs),
            ];
        }

        return view('faq', [
            'faqHeaderTitle' => $faq['header_title'] !== ''
                ? $faq['header_title']
                : __('messages.pages.faq.header_title'),
            'faqHeaderDescription' => $faq['header_description'] !== ''
                ? $faq['header_description']
                : __('messages.pages.faq.header_description'),
            'faqSections' => $faq['sections'],
            'faqChatbotEnabled' => $this->platformSettingService->isFaqChatbotEnabled()
                && $this->aiService->isGloballyEnabled(),
            'faqSchema' => $faqSchema,
            'seo' => $seo,
        ]);
    }

    /**
     * Find My Perfect Car — lifestyle advisor (grounded on live inventory).
     */
    public function showFindPerfectCar()
    {
        $locale = app()->getLocale();
        $sessionSeed = null;
        try {
            $sessionSeed = request()->hasSession() ? request()->session()->getId() : null;
        } catch (\Throwable) {
            $sessionSeed = null;
        }

        return view('find-perfect-car', [
            'publicAiEnabled' => $this->aiService->isGloballyEnabled(),
            'advisorExamples' => app(SuggestionService::class)->examplePrompts($locale, $sessionSeed, 4),
            'advisorPrefill' => trim((string) request()->query('q', '')),
            'seo' => [
                'meta_title' => __('messages.pages.find_perfect_car.meta_title'),
                'meta_description' => __('messages.pages.find_perfect_car.meta_description'),
                'canonical_url' => url('/find-din-bil'),
                'og_title' => __('messages.pages.find_perfect_car.meta_title'),
                'og_description' => __('messages.pages.find_perfect_car.meta_description'),
            ],
        ]);
    }

    /** Keys that can come from GET and populate the vehicles sidebar (from vehicle_listing_filters.txt) */
    private const VEHICLE_FILTER_KEYS = [
        'brand_id', 'model_id', 'fuel_type_id', 'category_id', 'listing_type_id',
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
        'city_slug', 'city', 'postcode',
    ];

    /**
     * Build currentFilters from request for the vehicles view (sidebar pre-fill). Normalizes arrays.
     */
    private function buildCurrentFilters(Request $request): array
    {
        $currentFilters = [];
        $arrayKeys = ['listing_type_id', 'equipment_ids', 'body_type_id', 'fuel_type_id', 'gear_type_id', 'price_type_id', 'sales_type_id', 'drive_axles', 'drive_axle_count', 'seller_type', 'brand_id', 'model_id'];
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
     * Whether the request has real search filters (not just pagination/sort).
     */
    private function hasActiveVehicleFilters(array $filters): bool
    {
        $ignored = ['limit', 'page', 'sort', 'view'];
        foreach ($filters as $key => $value) {
            if (in_array($key, $ignored, true)) {
                continue;
            }
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (is_array($value) && count(array_filter($value, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Show the vehicles listing page. GET params populate sidebar (currentFilters); initial list uses same filters.
     */
    public function showVehicles(Request $request)
    {
        $currentFilters = $this->buildCurrentFilters($request);

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

        $searchQuery = trim((string) ($request->input('search') ?? $request->input('q') ?? ''));
        if ($searchQuery !== '') {
            app(SearchQueryLogService::class)->log(
                surface: 'vehicles',
                query: $searchQuery,
                locale: app()->getLocale(),
                filters: array_filter($currentFilters, fn ($v) => $v !== null && $v !== '' && $v !== []),
            );
        }

        $hasFilters = $this->hasActiveVehicleFilters($currentFilters);
        $showNoResultsMessage = $vehicles->total() === 0 && $hasFilters;
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
        $selectedTypeId = isset($currentFilters['type_id']) && $currentFilters['type_id'] !== '' ? (int) $currentFilters['type_id'] : null;

        $selectedBrands = !empty($selectedBrandIds)
            ? DmrBrand::whereIn('id', $selectedBrandIds)->orderBy('name')->get(['id', 'name'])
            : collect();
        $selectedModels = !empty($selectedModelIds)
            ? DmrModel::whereIn('id', $selectedModelIds)->orderBy('name')->get(['id', 'name', 'brand_id'])
            : collect();
        $selectedType = $selectedTypeId ? Type::select(['id', 'name'])->find($selectedTypeId) : null;

        $constants = $this->lookupService->getPublicConstants();
        $seo = $this->seoService->getForPage('listing', 'vehicles') ?? [];
        // Always consolidate filter/sort query variants onto the clean listing URL.
        $seo['canonical_url'] = route('vehicles');

        $vehicleSortLabels = $this->buildVehicleListingSortLabels();
        $rawSortQuery = $request->query('sort');
        $rawSortQuery = is_string($rawSortQuery) ? $rawSortQuery : null;
        $currentListingSort = VehicleService::normalizePublicListingSort($rawSortQuery);
        $filterPriceMax = VehicleSearchFilters::PRICE_MAX;
        $filterKmMax = VehicleSearchFilters::KM_MAX;

        return view('vehicles', compact(
            'vehicles',
            'constants',
            'currentFilters',
            'seo',
            'showNoResultsMessage',
            'fallbackVehicles',
            'selectedBrands',
            'selectedModels',
            'selectedType',
            'vehicleSortLabels',
            'currentListingSort',
            'rawSortQuery',
            'filterPriceMax',
            'filterKmMax',
        ));
    }

    /**
     * Presentation fields for the vehicle listing card component (initial SSR).
     *
     * @return array{vehicle: Vehicle, imgUrl: string, imgAlt: string, salesTypeName: string|null, trustBadge: bool, priceDroppedRecently: bool}
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

        $badges = $this->vehicleListingPresentationService->badgeFields($vehicle);

        return [
            'vehicle' => $vehicle,
            'imgUrl' => $imgUrl,
            'imgAlt' => $imgAlt,
            'salesTypeName' => $salesTypeName,
            'trustBadge' => (bool) ($badges['trust_badge'] ?? false),
            'priceDroppedRecently' => (bool) ($badges['price_dropped_recently'] ?? false),
            'premiumDealerBadge' => (bool) ($badges['premium_dealer_badge'] ?? false),
            'isBoosted' => (bool) ($badges['is_boosted'] ?? false),
        ];
    }

    /**
     * Curated Danish labels for the public vehicles listing sort dropdown.
     *
     * @return array<string, string>
     */
    private function buildVehicleListingSortLabels(): array
    {
        return VehicleService::curatedPublicListingSortOptions();
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

        $this->vehicleViewService->recordView(
            $vehicle,
            $user?->id,
            $request->ip(),
            $request->userAgent()
        );
        
        $seo = $this->seoService->resolveForVehicle($vehicle);
        $showFinanceCalculator = $this->financeCalculatorService->shouldShowCalculatorForVehicle($vehicle);
        $financeSettings = $showFinanceCalculator ? $this->financeCalculatorService->settingsForLocale() : [];
        $financeEstimate = $showFinanceCalculator
            ? $this->financeCalculatorService->calculateMonthlyPayment((float) ($vehicle->price ?? 0))
            : null;
        $financePartnerUrl = $showFinanceCalculator
            ? $this->financeCalculatorService->dealerFinanceUrl($vehicle->dealer)
            : null;

        $metaViewContentEventId = null;
        if ($this->metaConversionsApiService->isEnabled()) {
            $metaViewContentEventId = $this->metaConversionsApiService->newEventId();
            $this->metaConversionsApiService->trackViewContent(
                $vehicle,
                $metaViewContentEventId,
                url()->current(),
                $request->ip(),
                $request->userAgent()
            );
        }

        return view('vehicle-detail', [
            'vehicle' => $vehicle,
            'vehicleDetail' => $this->vehicleDetailPresentationService->buildDetailPayload($vehicle),
            'seo' => $seo,
            'showTrustReport' => $this->trustReportService->isPlatformTrustReportEnabled(),
            'showFinanceCalculator' => $showFinanceCalculator,
            'financeSettings' => $financeSettings,
            'financeEstimate' => $financeEstimate,
            'financePartnerUrl' => $financePartnerUrl,
            'metaViewContentEventId' => $metaViewContentEventId,
            'metaPixelEnabled' => $this->metaConversionsApiService->isEnabled(),
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
            return redirect()->route('login')->with('return_url', '/favoritter');
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

