<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\Enquiry;
use App\Models\Category;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\DmrFactVehicle;
use App\Models\ListingType;
use App\Models\PriceType;
use App\Models\BodyType;
use App\Models\GearType;
use App\Models\DmrDriveEnergy;
use App\Models\Condition;
use App\Models\SalesType;
use App\Models\EquipmentType;
use App\Services\AuthService;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use App\Services\SeoService;
use App\Constants\LeadStage;
use App\Constants\LeadIntent;
use App\Constants\Enquiries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService,
        private SeoService $seoService
    ) {}

    /**
     * Show the dealer page
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function show(Request $request, string $slug): View
    {
        $dealer = Dealer::where('slug', $slug)->first();
        
        if (!$dealer) {
            abort(404, 'Dealer not found');
        }

        // Load dealer relationships
        $dealer->load(['owner', 'vehicles' => function ($query) {
            $query->where('list_status_id', \App\Constants\VehicleListStatus::PUBLISHED)
                  ->with(['images' => function ($q) {
                      $q->orderBy('sort_order');
                  }]);
        }]);

        // Define basic filter keys
        $basicFilterKeys = [
            'search', 'category_id', 'brand_id', 'model_id', 'model_year_id', 
            'fuel_type_id', 'km_driven', 'price_from', 'price_to', 'listing_type_id', 'sort'
        ];
        
        $filters = $request->only($basicFilterKeys);
        
        // Get dealer vehicles with filters
        $vehicles = $this->vehicleService->getPublicDealerVehicles(
            $dealer->id,
            $filters,
            $request->input('limit', 15),
            $request->input('page', 1)
        );

        // Fetch filter options for the view
        $filterOptions = [
            'categories' => Category::orderBy('name')->get(),
            'listingTypes' => ListingType::orderBy('name')->get(),
            'priceTypes' => PriceType::orderBy('name')->get(),
            'bodyTypes' => BodyType::orderBy('name')->get(),
            'gearTypes' => GearType::orderBy('name')->get(),
            'fuelTypes' => DmrDriveEnergy::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'modelYears' => DmrFactVehicle::distinctModelYearOptions(),
            'conditions' => Condition::orderBy('name')->get(),
            'salesTypes' => SalesType::orderBy('name')->get(),
        ];

        // Popular brands
        $popularBrandNames = ['Volvo', 'BMW', 'Mercedes-Benz', 'Audi', 'VW', 'Toyota', 'Ford', 'Peugeot', 'Opel', 'Skoda', 'Nissan', 'Hyundai', 'Kia', 'Mazda', 'Honda'];
        $filterOptions['popularBrands'] = Brand::whereIn('name', $popularBrandNames)->orderBy('name')->get();

        // Filter models by selected brand if provided
        $selectedBrandId = $request->input('brand_id');
        if ($selectedBrandId) {
            $filterOptions['models'] = VehicleModel::where('brand_id', $selectedBrandId)->orderBy('name')->get();
        } else {
            $filterOptions['models'] = VehicleModel::orderBy('name')->get();
        }

        // Group equipment by equipment type
        $equipmentTypes = EquipmentType::with(['equipments' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();
        
        $filterOptions['equipmentTypes'] = $equipmentTypes;

        $seo = $this->seoService->getForPage('dealer', $dealer->slug);

        return view('dealer-page', [
            'dealer' => $dealer,
            'vehicles' => $vehicles,
            'filterOptions' => $filterOptions,
            'currentFilters' => $request->all(),
            'seo' => $seo,
        ]);
    }

    /**
     * Get dealer vehicles (AJAX endpoint)
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVehicles(Request $request, string $slug): JsonResponse
    {
        $dealer = Dealer::where('slug', $slug)->first();
        
        if (!$dealer) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.dealer_not_found'),
            ], 404);
        }

        // Define basic filter keys
        $basicFilterKeys = [
            'search', 'category_id', 'brand_id', 'model_id', 'model_year_id', 
            'fuel_type_id', 'km_driven', 'price_from', 'price_to', 'listing_type_id', 'sort'
        ];
        
        $filters = $request->only($basicFilterKeys);
        
        // Get dealer vehicles with filters
        $vehicles = $this->vehicleService->getPublicDealerVehicles(
            $dealer->id,
            $filters,
            $request->input('limit', 15),
            $request->input('page', 1)
        );

        // Format vehicles for JSON response
        $formattedVehicles = collect($vehicles->items())->map(function ($vehicle) {
            $firstImage = $vehicle->images->first();
            $imageUrl = $firstImage?->thumbnail_url ?? $firstImage?->url ?? '/placeholder-vehicle.jpg';

            return [
                'id' => $vehicle->id,
                'slug' => $vehicle->slug,
                'title' => $vehicle->title,
                'registration' => $vehicle->registration,
                'price' => $vehicle->price,
                'km_driven' => $vehicle->km_driven,
                'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                'brand_name' => $vehicle->brand_name,
                'model_name' => $vehicle->model_name,
                'fuel_type_name' => $vehicle->fuel_type_name,
                'gear_type_name' => $vehicle->gear_type_name,
                'model_year_name' => $vehicle->model_year_name,
                'vehicle_list_status_name' => $vehicle->vehicle_list_status_name,
                'engine_power_hp' => $vehicle->engine_power_hp,
                'seller_address' => $vehicle->seller_address,
                'seller_postcode' => $vehicle->seller_postcode,
                'dealer_id' => $vehicle->dealer_id,
                'image_url' => $imageUrl,
                'thumbnail_url' => $firstImage?->thumbnail_url ?? null,
                'details' => [],
            ];
        });

        return response()->json([
            'vehicles' => $formattedVehicles,
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
                'from' => $vehicles->firstItem(),
                'to' => $vehicles->lastItem(),
            ],
            'filters' => $request->all(),
        ]);
    }

    /**
     * Submit dealer enquiry form
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitEnquiry(Request $request, string $slug): JsonResponse
    {
        // Get authenticated user (can be null for guest users)
        $user = $this->authService->getAuthenticatedUser($request);

        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
        ]);

        // Find dealer
        $dealer = Dealer::with('owner')->where('slug', $slug)->first();
        
        if (!$dealer) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.dealer_not_found'),
            ], 404);
        }

        // Find or create source based on request type
        $sourceName = $this->getSourceName($request);
        $source = Source::firstOrCreate(['name' => $sourceName]);

        // Get "Enquiry Form Submission" category
        $leadCategory = LeadCategory::where('name', 'Enquiry Form Submission')->first();
        
        // If category doesn't exist, default to 'Enquire'
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        // Get lead intent based on category
        $leadIntentId = $this->getLeadIntentId('Enquiry Form Submission');

        // Update user profile if authenticated and name differs
        if ($user && $validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        // Create lead record (buyer_user_id can be null for guest users, vehicle_id is null for dealer enquiry)
        $lead = Lead::create([
            'vehicle_id' => null, // General dealer enquiry, not vehicle-specific
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealer->id,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record with the message details (user_id can be null for guest users, vehicle_id is null)
        $dealerName = $dealer->owner?->name ?? 'Dealer';
        $enquirySubject = 'General enquiry about ' . $dealerName;
        $enquiry = Enquiry::create([
            'subject' => $enquirySubject,
            'message' => $validated['message'],
            'type' => Enquiries::TYPES[0], // 'General' as default
            'status' => Enquiries::STATUSES[0], // 'New' as default
            'source' => $sourceName, // Use dynamic source (Website or Mobile App)
            'user_id' => $user?->id,
            'vehicle_id' => null, // General dealer enquiry, not vehicle-specific
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Log audit trail for lead (handles both authenticated and guest users)
        try {
            $this->auditLogService->logCreateForGuest(
                $user,
                'Lead',
                $lead->id,
                $lead->toArray(),
                $request,
                'Dealer',
                $dealer->id,
                'Lead created for dealer',
                ['lead', 'enquiry', 'dealer']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for lead creation', [
                'lead_id' => $lead->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Log audit trail for enquiry (handles both authenticated and guest users)
        try {
            $this->auditLogService->logCreateForGuest(
                $user,
                'Enquiry',
                $enquiry->id,
                $enquiry->toArray(),
                $request,
                'Dealer',
                $dealer->id,
                'Enquiry created for dealer',
                ['enquiry', 'dealer']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for enquiry creation', [
                'enquiry_id' => $enquiry->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('messages.errors.enquiry_submitted'),
        ]);
    }

    /**
     * Get source name based on request type (API or Website)
     */
    private function getSourceName(Request $request): string
    {
        // Check if request is from API
        if ($request->expectsJson() || $request->is('api/*')) {
            return Source::MOBILE_APP;
        }
        
        return Source::WEBSITE;
    }

    /**
     * Get lead intent ID based on category name
     */
    private function getLeadIntentId(string $categoryName): ?int
    {
        return match($categoryName) {
            'Enquiry Form Submission' => LeadIntent::HIGH,
            'Phone Number Revealed' => LeadIntent::MEDIUM,
            'WhatsApp Clicked' => LeadIntent::HIGH,
            'Email Clicked' => LeadIntent::MEDIUM,
            'Request Test Drive' => LeadIntent::VERY_HIGH,
            'Price Negotiation Request' => LeadIntent::VERY_HIGH,
            default => null,
        };
    }
}
