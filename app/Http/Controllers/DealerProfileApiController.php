<?php

namespace App\Http\Controllers;

use App\Models\Dealer;
use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\Enquiry;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use App\Constants\VehicleListStatus;
use App\Constants\LeadStage;
use App\Constants\LeadIntent;
use App\Constants\Enquiries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Dealer Profile API Controller
 * Handles dealer profile operations for mobile app
 * All routes require authentication (auth:api middleware)
 */
class DealerProfileApiController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService
    ) {}

    /**
     * Get dealer profile
     * GET /api/v1/dealer/profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $user->dealer;

        if (!$dealer) {
            return $this->error(__('messages.errors.dealer_not_found'), null, 404);
        }

        // Load dealer relationships
        $dealer->load('owner');

        return $this->success([
            'id' => $dealer->id,
            'slug' => $dealer->slug,
            'cvr' => $dealer->cvr,
            'address' => $dealer->address,
            'city' => $dealer->city,
            'postcode' => $dealer->postcode,
            'country_code' => $dealer->country_code,
            'logo_url' => $dealer->logo_url,
            'owner' => $dealer->owner ? [
                'id' => $dealer->owner->id,
                'name' => $dealer->owner->name,
                'email' => $dealer->owner->email,
                'phone' => $dealer->owner->phone,
                'whatsapp_number' => $dealer->owner->whatsapp_number,
            ] : null,
        ]);
    }

    /**
     * Get dealer vehicles list
     * GET /api/v1/dealer/vehicles
     */
    public function getVehicles(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $user->dealer;

        if (!$dealer) {
            return $this->error(__('messages.errors.dealer_not_found'), null, 404);
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
            
            return [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'registration' => $vehicle->registration,
                'vin' => $vehicle->dmrFactVehicle?->stel_nummer,
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
                'image_url' => $firstImage?->image_url ?? null,
                'thumbnail_url' => $firstImage?->thumbnail_url ?? null,
            ];
        });

        return $this->paginated(
            new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedVehicles,
                $vehicles->total(),
                $vehicles->perPage(),
                $vehicles->currentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            )
        );
    }

    /**
     * Send general enquiry to dealer
     * POST /api/v1/dealer/enquiry
     */
    public function sendEnquiry(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $user->dealer;

        if (!$dealer) {
            return $this->error(__('messages.errors.dealer_not_found'), null, 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Find or create source
        $sourceName = Source::MOBILE_APP;
        $source = Source::firstOrCreate(['name' => $sourceName]);

        // Get "Enquiry Form Submission" category
        $leadCategory = LeadCategory::where('name', 'Enquiry Form Submission')->first();
        
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        // Get lead intent
        $leadIntentId = $this->getLeadIntentId('Enquiry Form Submission');

        // Create lead record (vehicle_id is null for general dealer enquiry)
        $lead = Lead::create([
            'vehicle_id' => null,
            'buyer_user_id' => $user->id,
            'dealer_id' => $dealer->id,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record
        $dealerName = $dealer->owner?->name ?? 'Dealer';
        $enquirySubject = 'General enquiry about ' . $dealerName;
        $enquiry = Enquiry::create([
            'subject' => $enquirySubject,
            'message' => $request->input('message'),
            'type' => Enquiries::TYPES[0], // 'General'
            'status' => Enquiries::STATUSES[0], // 'New'
            'source' => $sourceName,
            'user_id' => $user->id,
            'vehicle_id' => null,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        // Log audit trail
        try {
            $this->auditLogService->logCreate(
                $user,
                'Enquiry',
                $enquiry->id,
                $enquiry->toArray(),
                $request,
                'Dealer',
                $dealer->id,
                'General enquiry created for dealer',
                ['enquiry', 'dealer']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for dealer enquiry', [
                'enquiry_id' => $enquiry->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success([
            'message' => __('messages.messages.enquiry_submitted_successfully'),
            'enquiry_id' => $enquiry->id,
            'lead_id' => $lead->id,
        ], 201);
    }

    /**
     * Get dealer statistics
     * GET /api/v1/dealer/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $user->dealer;

        if (!$dealer) {
            return $this->error(__('messages.errors.dealer_not_found'), null, 404);
        }

        $dealerId = $dealer->id;

        // Vehicle Statistics
        $totalVehicles = Vehicle::where('dealer_id', $dealerId)->count();
        $publishedVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)->count();
        $draftVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::DRAFT)->count();
        $soldVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::SOLD)->count();
        $archivedVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::ARCHIVED)->count();

        // Get vehicle IDs for this dealer
        $vehicleIds = Vehicle::where('dealer_id', $dealerId)->pluck('id');

        // Enquiry Statistics (vehicle-specific)
        $totalInquiries = Enquiry::whereIn('vehicle_id', $vehicleIds)->count();

        // Lead Statistics (includes both vehicle-specific and general enquiries)
        $totalLeads = Lead::where('dealer_id', $dealerId)->count();
        $generalLeads = Lead::where('dealer_id', $dealerId)
            ->whereNull('vehicle_id')
            ->count();

        return $this->success([
            'vehicles' => [
                'total' => $totalVehicles,
                'published' => $publishedVehicles,
                'draft' => $draftVehicles,
                'sold' => $soldVehicles,
                'archived' => $archivedVehicles,
            ],
            'inquiries' => [
                'total' => $totalInquiries,
                'vehicle_specific' => $totalInquiries,
            ],
            'leads' => [
                'total' => $totalLeads,
                'vehicle_specific' => $totalLeads - $generalLeads,
                'general' => $generalLeads,
            ],
        ]);
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
