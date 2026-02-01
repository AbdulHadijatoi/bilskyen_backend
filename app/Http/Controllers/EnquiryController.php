<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Vehicle;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\Enquiry;
use App\Services\AuthService;
use App\Constants\LeadStage;
use App\Constants\LeadIntent;
use App\Constants\Enquiries;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Enquiry Controller for Web
 * Handles vehicle enquiry/lead creation
 */
class EnquiryController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

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
     * Create a lead/enquiry for a vehicle
     * Requires authentication (handled by middleware)
     */
    public function enquire(Request $request, int $id): JsonResponse
    {
        // Get authenticated user (middleware ensures user is authenticated)
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Find vehicle
        $vehicle = Vehicle::with(['details', 'dealer.users', 'user'])->find($id);
        
        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vehicle not found',
            ], 404);
        }

        // Get dealer_id (can be null for private listings)
        $dealerId = $vehicle->dealer_id;

        // Get phone number with fallback logic
        $phoneNumber = null;
        
        // First try: vehicle details seller_phone
        if ($vehicle->details && !empty($vehicle->details->seller_phone)) {
            $phoneNumber = $vehicle->details->seller_phone;
        }
        // If empty and vehicle has dealer: Get phone from dealer's first user
        elseif (empty($phoneNumber) && $vehicle->dealer) {
            $dealerUser = $vehicle->dealer->users()->first();
            if ($dealerUser && !empty($dealerUser->phone)) {
                $phoneNumber = $dealerUser->phone;
            }
        }
        // If empty and vehicle has user (seller/private listing): Get phone from vehicle user
        elseif (empty($phoneNumber) && $vehicle->user) {
            if (!empty($vehicle->user->phone)) {
                $phoneNumber = $vehicle->user->phone;
            }
        }

        // Find or create source based on request type
        $sourceName = $this->getSourceName($request);
        $source = Source::firstOrCreate(['name' => $sourceName]);

        // Get lead category from request (default to 'Enquire' if not specified)
        $categoryName = $request->input('category', 'Enquire');
        $leadCategory = LeadCategory::where('name', $categoryName)->first();
        
        // If category doesn't exist, default to 'Enquire'
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        // Get lead intent based on category
        $leadIntentId = $this->getLeadIntentId($categoryName);

        // Create lead record
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Return response with lead data and phone number
        return response()->json([
            'status' => 'success',
            'message' => 'Lead created successfully',
            'data' => [
                'lead_id' => $lead->id,
                'phone_number' => $phoneNumber,
            ],
        ], 201);
    }

    /**
     * Show enquiry form page for a vehicle
     */
    public function showEnquiryForm(int $id): View
    {
        $vehicle = Vehicle::with(['details', 'dealer.users', 'user', 'images', 'brand', 'model'])->findOrFail($id);
        
        return view('vehicle-enquiry-form', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Submit enquiry form and create lead
     */
    public function submitEnquiryForm(Request $request, int $id): JsonResponse
    {
        // Get authenticated user (middleware ensures user is authenticated)
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'message' => 'required|string|max:5000',
        ]);

        // Find vehicle
        $vehicle = Vehicle::with(['details', 'dealer.users', 'user'])->find($id);
        
        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vehicle not found',
            ], 404);
        }

        // Get dealer_id (can be null for private listings)
        $dealerId = $vehicle->dealer_id;

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

        // Update user profile if name differs
        if ($validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        // Create lead record
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record with the message details
        $enquirySubject = 'Enquiry about ' . ($vehicle->title ?? 'Vehicle #' . $vehicle->id);
        $enquiry = Enquiry::create([
            'subject' => $enquirySubject,
            'message' => $validated['message'],
            'type' => Enquiries::TYPES[0], // 'General' as default
            'status' => Enquiries::STATUSES[0], // 'New' as default
            'source' => $sourceName, // Use dynamic source (Website or Mobile App)
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Your enquiry has been submitted successfully. We will get back to you soon.',
            'data' => [
                'lead_id' => $lead->id,
                'enquiry_id' => $enquiry->id,
            ],
        ], 201);
    }

    /**
     * Show test drive request form
     */
    public function showTestDriveForm(int $id): View
    {
        $vehicle = Vehicle::with(['details', 'dealer.users', 'user', 'images', 'brand', 'model'])->findOrFail($id);
        $user = $this->authService->getAuthenticatedUser(request());
        return view('vehicle-test-drive-form', [
            'vehicle' => $vehicle,
            'user' => $user, // Pass authenticated user for pre-filling form
        ]);
    }

    /**
     * Submit test drive request form and create lead + enquiry
     */
    public function submitTestDriveForm(Request $request, int $id): JsonResponse
    {
        // Get authenticated user (middleware ensures user is authenticated)
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'message' => 'required|string|max:5000',
        ]);

        // Find vehicle
        $vehicle = Vehicle::with(['details', 'dealer.users', 'user'])->find($id);
        
        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vehicle not found',
            ], 404);
        }

        // Get dealer_id (can be null for private listings)
        $dealerId = $vehicle->dealer_id;

        // Find or create source based on request type
        $sourceName = $this->getSourceName($request);
        $source = Source::firstOrCreate(['name' => $sourceName]);

        // Get "Request Test Drive" category
        $leadCategory = LeadCategory::where('name', 'Request Test Drive')->first();
        
        // If category doesn't exist, default to 'Enquire'
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        // Get lead intent based on category
        $leadIntentId = $this->getLeadIntentId('Request Test Drive');

        // Update user profile if name differs
        if ($validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        // Create lead record
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record with type "Test Drive"
        $enquirySubject = 'Test Drive Request for ' . ($vehicle->title ?? 'Vehicle #' . $vehicle->id);
        $enquiry = Enquiry::create([
            'subject' => $enquirySubject,
            'message' => $validated['message'],
            'type' => 'Test Drive', // Use Test Drive type
            'status' => Enquiries::STATUSES[0], // 'New' as default
            'source' => $sourceName, // Use dynamic source (Website or Mobile App)
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Your test drive request has been submitted successfully. We will get back to you soon to schedule your test drive.',
            'data' => [
                'lead_id' => $lead->id,
                'enquiry_id' => $enquiry->id,
            ],
        ], 201);
    }

    /**
     * Show price negotiation form
     */
    public function showPriceNegotiationForm(int $id): View
    {
        $vehicle = Vehicle::with(['details', 'dealer.users', 'user', 'images', 'brand', 'model'])->findOrFail($id);
        $user = $this->authService->getAuthenticatedUser(request());
        return view('vehicle-price-negotiation-form', [
            'vehicle' => $vehicle,
            'user' => $user, // Pass authenticated user for pre-filling form
        ]);
    }

    /**
     * Submit price negotiation form and create lead + enquiry
     */
    public function submitPriceNegotiationForm(Request $request, int $id): JsonResponse
    {
        // Get authenticated user (middleware ensures user is authenticated)
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'message' => 'required|string|max:5000',
        ]);

        // Find vehicle
        $vehicle = Vehicle::with(['details', 'dealer.users', 'user'])->find($id);
        
        if (!$vehicle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vehicle not found',
            ], 404);
        }

        // Get dealer_id (can be null for private listings)
        $dealerId = $vehicle->dealer_id;

        // Find or create source based on request type
        $sourceName = $this->getSourceName($request);
        $source = Source::firstOrCreate(['name' => $sourceName]);

        // Get "Price Negotiation Request" category
        $leadCategory = LeadCategory::where('name', 'Price Negotiation Request')->first();
        
        // If category doesn't exist, default to 'Enquire'
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        // Get lead intent based on category
        $leadIntentId = $this->getLeadIntentId('Price Negotiation Request');

        // Update user profile if name differs
        if ($validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        // Create lead record
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record with type "Price Enquiry"
        $enquirySubject = 'Price Negotiation for ' . ($vehicle->title ?? 'Vehicle #' . $vehicle->id);
        $enquiry = Enquiry::create([
            'subject' => $enquirySubject,
            'message' => $validated['message'],
            'type' => 'Price Enquiry', // Use Price Enquiry type
            'status' => Enquiries::STATUSES[0], // 'New' as default
            'source' => $sourceName, // Use dynamic source (Website or Mobile App)
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
        ]);

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Your price negotiation has been submitted successfully. We will get back to you soon.',
            'data' => [
                'lead_id' => $lead->id,
                'enquiry_id' => $enquiry->id,
            ],
        ], 201);
    }
}
