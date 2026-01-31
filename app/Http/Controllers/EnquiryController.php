<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Vehicle;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Services\AuthService;
use App\Constants\LeadStage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

        // Find or create "Website" source
        $source = Source::firstOrCreate(['name' => 'Website']);

        // Get lead category from request (default to 'Enquire' if not specified)
        $categoryName = $request->input('category', 'Enquire');
        $leadCategory = LeadCategory::where('name', $categoryName)->first();
        
        // If category doesn't exist, default to 'Enquire'
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        // Create lead record
        $lead = Lead::create([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
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
}
