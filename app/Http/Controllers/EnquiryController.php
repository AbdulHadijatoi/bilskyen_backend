<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Vehicle;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\Enquiry;
use App\Services\AuthService;
use App\Services\AuditLogService;
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
        private AuthService $authService,
        private AuditLogService $auditLogService
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
     * Allows both authenticated and guest users
     */
    public function enquire(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Get authenticated user (can be null for guest users)
        $user = $this->authService->getAuthenticatedUser($request);

        $vehicle->load(['details', 'dealer.owner', 'user']);

        // Get dealer_id (can be null for private listings)
        $dealerId = $vehicle->dealer_id;

        // Get phone number with fallback logic
        $phoneNumber = null;
        
        // First try: vehicle details seller_phone
        if ($vehicle->details && !empty($vehicle->details->seller_phone)) {
            $phoneNumber = $vehicle->details->seller_phone;
        }
        // If empty and vehicle has dealer: Get phone from dealer owner
        elseif (empty($phoneNumber) && $vehicle->dealer) {
            // Load owner relationship
            $vehicle->dealer->load('owner');
            if ($vehicle->dealer->owner && !empty($vehicle->dealer->owner->phone)) {
                $phoneNumber = $vehicle->dealer->owner->phone;
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

        // Create lead record (buyer_user_id can be null for guest users)
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Log audit trail (handles both authenticated and guest users)
        try {
            $this->auditLogService->logCreateForGuest(
                $user,
                'Lead',
                $lead->id,
                $lead->toArray(),
                $request,
                'Vehicle',
                $vehicle->id,
                'Lead created for vehicle',
                ['lead', 'enquiry']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for lead creation', [
                'lead_id' => $lead->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

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
    public function showEnquiryForm(Vehicle $vehicle): View
    {
        $vehicle->load(['details', 'dealer.owner', 'user', 'images', 'brand', 'model']);

        return view('vehicle-enquiry-form', [
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Submit enquiry form and create lead
     * Allows both authenticated and guest users
     */
    public function submitEnquiryForm(Request $request, Vehicle $vehicle): JsonResponse
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

        $vehicle->load(['details', 'dealer.owner', 'user']);

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

        // Update user profile if authenticated and name differs
        if ($user && $validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        // Create lead record (buyer_user_id can be null for guest users)
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record with the message details (user_id can be null for guest users)
        $enquirySubject = 'Enquiry about ' . ($vehicle->title ?? 'Vehicle #' . $vehicle->id);
        $enquiry = Enquiry::create([
            'lead_id' => $lead->id,
            'subject' => $enquirySubject,
            'message' => $validated['message'],
            'type' => Enquiries::TYPES[0], // 'General' as default
            'status' => Enquiries::STATUSES[0], // 'New' as default
            'source' => $sourceName, // Use dynamic source (Website or Mobile App)
            'user_id' => $user?->id,
            'vehicle_id' => $vehicle->id,
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
                'Vehicle',
                $vehicle->id,
                'Lead created for vehicle',
                ['lead', 'enquiry']
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
                'Vehicle',
                $vehicle->id,
                'Enquiry submitted for vehicle',
                ['enquiry', 'form']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for enquiry creation', [
                'enquiry_id' => $enquiry->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

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
    public function showTestDriveForm(Vehicle $vehicle): View
    {
        $vehicle->load(['details', 'dealer.owner', 'user', 'images', 'brand', 'model']);
        $user = $this->authService->getAuthenticatedUser(request());
        return view('vehicle-test-drive-form', [
            'vehicle' => $vehicle,
            'user' => $user, // Pass authenticated user for pre-filling form
        ]);
    }

    /**
     * Submit test drive request form and create lead + enquiry
     * Allows both authenticated and guest users
     */
    public function submitTestDriveForm(Request $request, Vehicle $vehicle): JsonResponse
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

        $vehicle->load(['details', 'dealer.owner', 'user']);

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

        // Update user profile if authenticated and name differs
        if ($user && $validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        // Create lead record (buyer_user_id can be null for guest users)
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record with type "Test Drive" (user_id can be null for guest users)
        $enquirySubject = 'Test Drive Request for ' . ($vehicle->title ?? 'Vehicle #' . $vehicle->id);
        $enquiry = Enquiry::create([
            'lead_id' => $lead->id,
            'subject' => $enquirySubject,
            'message' => $validated['message'],
            'type' => 'Test Drive', // Use Test Drive type
            'status' => Enquiries::STATUSES[0], // 'New' as default
            'source' => $sourceName, // Use dynamic source (Website or Mobile App)
            'user_id' => $user?->id,
            'vehicle_id' => $vehicle->id,
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
                'Vehicle',
                $vehicle->id,
                'Lead created for vehicle',
                ['lead', 'enquiry']
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
                'Vehicle',
                $vehicle->id,
                'Test drive request submitted for vehicle',
                ['enquiry', 'test-drive']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for enquiry creation', [
                'enquiry_id' => $enquiry->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

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
    public function showPriceNegotiationForm(Vehicle $vehicle): View
    {
        $vehicle->load(['details', 'dealer.owner', 'user', 'images', 'brand', 'model']);
        $user = $this->authService->getAuthenticatedUser(request());
        return view('vehicle-price-negotiation-form', [
            'vehicle' => $vehicle,
            'user' => $user, // Pass authenticated user for pre-filling form
        ]);
    }

    /**
     * Submit price negotiation form and create lead + enquiry
     * Allows both authenticated and guest users
     */
    public function submitPriceNegotiationForm(Request $request, Vehicle $vehicle): JsonResponse
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

        $vehicle->load(['details', 'dealer.owner', 'user']);

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

        // Update user profile if authenticated and name differs
        if ($user && $validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        // Create lead record (buyer_user_id can be null for guest users)
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Create enquiry record with type "Price Enquiry" (user_id can be null for guest users)
        $enquirySubject = 'Price Negotiation for ' . ($vehicle->title ?? 'Vehicle #' . $vehicle->id);
        $enquiry = Enquiry::create([
            'lead_id' => $lead->id,
            'subject' => $enquirySubject,
            'message' => $validated['message'],
            'type' => 'Price Enquiry', // Use Price Enquiry type
            'status' => Enquiries::STATUSES[0], // 'New' as default
            'source' => $sourceName, // Use dynamic source (Website or Mobile App)
            'user_id' => $user?->id,
            'vehicle_id' => $vehicle->id,
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
                'Vehicle',
                $vehicle->id,
                'Lead created for vehicle',
                ['lead', 'enquiry']
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
                'Vehicle',
                $vehicle->id,
                'Price negotiation request submitted for vehicle',
                ['enquiry', 'price-negotiation']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for enquiry creation', [
                'enquiry_id' => $enquiry->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

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
