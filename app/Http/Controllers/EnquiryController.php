<?php

namespace App\Http\Controllers;

use App\Mail\VehicleEnquiryReceivedMail;
use App\Models\Lead;
use App\Models\Vehicle;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\Enquiry;
use App\Services\AuthService;
use App\Services\AuditLogService;
use App\Services\MailService;
use App\Services\MarketplaceNotifier;
use App\Support\EnquiryMailPresenter;
use App\Constants\LeadStage;
use App\Constants\LeadIntent;
use App\Constants\Enquiries;
use App\Services\Marketing\MetaConversionsApiService;
use App\Services\Marketing\ListingFunnelService;
use App\Services\Marketing\TrafficAttributionService;
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
        private AuditLogService $auditLogService,
        private MailService $mailService,
        private MarketplaceNotifier $marketplaceNotifier,
        private MetaConversionsApiService $metaConversionsApiService,
        private TrafficAttributionService $trafficAttributionService,
        private ListingFunnelService $listingFunnelService,
    ) {}

    private function abortUnlessPublishedForEnquiry(Vehicle $vehicle): void
    {
        if (! $vehicle->isPublished()) {
            abort(404);
        }
    }

    private function enquiryBlockedResponse(Vehicle $vehicle): ?JsonResponse
    {
        if ($vehicle->isPublished()) {
            return null;
        }

        if ($vehicle->isSold()) {
            return $this->error(__('messages.pages.vehicles.detail.sold_no_enquire'), [], 422);
        }

        abort(404);
    }

    private function resolveVehicleOwnerEmail(Vehicle $vehicle): ?string
    {
        $dealerOwnerEmail = $vehicle->dealer?->owner?->email;
        if (!empty($dealerOwnerEmail)) {
            return $dealerOwnerEmail;
        }

        $sellerEmail = $vehicle->user?->email;
        if (!empty($sellerEmail)) {
            return $sellerEmail;
        }

        return null;
    }

    private function sendVehicleEnquiryEmail(Vehicle $vehicle, Enquiry $enquiry): void
    {
        $ownerEmail = $this->resolveVehicleOwnerEmail($vehicle);
        if (!$ownerEmail) {
            return;
        }

        $presenter = EnquiryMailPresenter::for($vehicle, $enquiry);
        $vehicleUrl = route('vehicle.detail', $vehicle);

        $this->mailService->sendMailable(
            $ownerEmail,
            new VehicleEnquiryReceivedMail(
                vehicleTitle: $presenter->vehicleTitle(),
                vehicleUrl: $vehicleUrl,
                enquiryType: $presenter->typeLabel(),
                enquirySubject: $presenter->subjectLabel(),
                senderName: (string) $enquiry->name,
                senderEmail: (string) $enquiry->email,
                senderPhone: $enquiry->phone ? (string) $enquiry->phone : null,
                senderMessage: $presenter->messageBody(),
            ),
            [
                'mail_type' => 'vehicle_enquiry_received',
                'vehicle_id' => $vehicle->id,
                'enquiry_id' => $enquiry->id,
            ],
            false
        );
    }

    private function notifyEnquiryRecipients(Vehicle $vehicle, Enquiry $enquiry): void
    {
        $title = __('messages.notifications.new_enquiry_title');
        $message = __('messages.notifications.new_enquiry_message', ['vehicle' => $this->vehicleLabel($vehicle)]);
        $meta = ['vehicle_id' => $vehicle->id, 'enquiry_id' => $enquiry->id];

        if ($vehicle->dealer_id && $vehicle->dealer) {
            $this->marketplaceNotifier->notifyDealerOwner($vehicle->dealer, $title, $message, $meta);
        } elseif ($vehicle->user) {
            $this->marketplaceNotifier->notifyUser($vehicle->user, $title, $message, $meta);
        }
    }

    /**
     * Phone reveal is a CTA only — do not create a lead or email the dealer.
     */
    private function isPhoneRevealCategory(mixed $category): bool
    {
        $value = trim((string) $category);
        if ($value === '') {
            return false;
        }

        $labels = array_unique(array_filter([
            'Phone Number Revealed',
            __('messages.forms.phone_number_revealed'),
            trans('messages.forms.phone_number_revealed', [], 'en'),
            trans('messages.forms.phone_number_revealed', [], 'da'),
        ]));

        return in_array($value, $labels, true);
    }

    private function resolveContactPhone(Vehicle $vehicle): ?string
    {
        $dealerPhone = $vehicle->dealer?->owner?->phone;
        if (! empty($dealerPhone)) {
            return $dealerPhone;
        }

        $sellerPhone = $vehicle->user?->phone;
        if (! empty($sellerPhone)) {
            return $sellerPhone;
        }

        return null;
    }

    private function getLeadIntentId(string $categoryName): ?int
    {
        return match($categoryName) {
            'Enquiry Form Submission' => LeadIntent::HIGH,
            'Phone Number Revealed' => LeadIntent::MEDIUM,
            'WhatsApp Clicked' => LeadIntent::HIGH,
            'Email Clicked' => LeadIntent::MEDIUM,
            'Request Test Drive' => LeadIntent::VERY_HIGH,
            'Price Negotiation Request' => LeadIntent::VERY_HIGH,
            'Exchange Request' => LeadIntent::VERY_HIGH,
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

    private function vehicleLabel(Vehicle $vehicle): string
    {
        return $vehicle->title ?? __('messages.mail.vehicle_fallback', ['id' => $vehicle->id]);
    }

    private function enquirySubject(string $translationKey, Vehicle $vehicle): string
    {
        return __($translationKey, ['vehicle' => $this->vehicleLabel($vehicle)]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withAttribution(array $payload, Request $request): array
    {
        return array_merge($payload, $this->trafficAttributionService->leadAttributes($request));
    }

    private function recordFunnelConvert(Request $request, Vehicle $vehicle, string $kind): void
    {
        $this->listingFunnelService->record(
            $request,
            (int) $vehicle->id,
            ListingFunnelService::CONVERT,
            ['kind' => $kind]
        );
    }

    private function dispatchMetaLead(Request $request, Vehicle $vehicle, ?Enquiry $enquiry = null): ?string
    {
        if (! $this->metaConversionsApiService->isBrowserEnabled()) {
            return null;
        }

        $eventId = $this->metaConversionsApiService->newEventId();
        $this->metaConversionsApiService->trackLead(
            $vehicle,
            $eventId,
            route('vehicle.detail', $vehicle),
            $request->ip(),
            $request->userAgent(),
            $enquiry?->email,
            $enquiry?->phone
        );

        return $eventId;
    }

    /**
     * Create a lead/enquiry for a vehicle
     * Allows both authenticated and guest users
     */
    public function enquire(Request $request, Vehicle $vehicle): JsonResponse
    {
        if ($blocked = $this->enquiryBlockedResponse($vehicle)) {
            return $blocked;
        }

        // Get authenticated user (can be null for guest users)
        $user = $this->authService->getAuthenticatedUser($request);

        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'category' => 'nullable|string|max:100',
        ]);

        $vehicle->load(['dealer.owner', 'user']);

        if ($this->isPhoneRevealCategory($validated['category'] ?? $request->input('category'))) {
            return response()->json([
                'status' => 'success',
                'message' => __('messages.api.phone_shown'),
                'data' => [
                    'lead_id' => null,
                    'phone_number' => $this->resolveContactPhone($vehicle),
                    'meta_lead_event_id' => null,
                ],
            ]);
        }

        // Get dealer_id (can be null for private listings)
        $dealerId = $vehicle->dealer_id;

        $phoneNumber = $this->resolveContactPhone($vehicle);

        // Find or create source based on request type
        $sourceName = $this->getSourceName($request);
        $source = Source::firstOrCreate(['name' => $sourceName]);

        // Get lead category from request (default to 'Enquire' if not specified)
        $categoryName = $validated['category'] ?? $request->input('category', 'Enquire');
        $leadCategory = LeadCategory::where('name', $categoryName)->first();
        
        // If category doesn't exist, default to 'Enquire'
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        // Get lead intent based on category
        $leadIntentId = $this->getLeadIntentId($categoryName);

        // Create lead record (buyer_user_id can be null for guest users)
        $lead = Lead::create($this->withAttribution([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ], $request));
        $this->recordFunnelConvert($request, $vehicle, 'phone');

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
                __('messages.audit.lead_created_for_vehicle'),
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
        $enquiry = Enquiry::create([
            'lead_id' => $lead->id,
            'vehicle_id' => $vehicle->id,
            'user_id' => $user?->id,
            'name' => $validated['name'] ?? $user?->name ?? 'Guest',
            'email' => $validated['email'] ?? $user?->email ?? 'noreply@example.com',
            'phone' => $validated['phone'] ?? $user?->phone,
            'subject' => $this->enquirySubject('messages.api.phone_reveal_subject', $vehicle),
            'message' => __('messages.api.phone_reveal_message'),
            'type' => Enquiries::TYPES[0],
            'status' => Enquiries::STATUSES[0],
            'source' => $this->getSourceName($request),
        ]);

        $this->sendVehicleEnquiryEmail($vehicle, $enquiry);
        $this->notifyEnquiryRecipients($vehicle, $enquiry);

        $metaLeadEventId = $this->dispatchMetaLead($request, $vehicle, $enquiry);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.api.lead_created_successfully'),
            'data' => [
                'lead_id' => $lead->id,
                'phone_number' => $phoneNumber,
                'meta_lead_event_id' => $metaLeadEventId,
            ],
        ], 201);
    }

    /**
     * Show enquiry form page for a vehicle
     */
    public function showEnquiryForm(Vehicle $vehicle): View
    {
        $this->abortUnlessPublishedForEnquiry($vehicle);

        $vehicle->load(['dealer.owner', 'user', 'images', 'dmrFactVehicle.variant.model.brand']);

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
        if ($blocked = $this->enquiryBlockedResponse($vehicle)) {
            return $blocked;
        }

        // Get authenticated user (can be null for guest users)
        $user = $this->authService->getAuthenticatedUser($request);

        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:5000',
        ]);

        if (empty(trim((string) ($validated['message'] ?? '')))) {
            $validated['message'] = __('messages.dialogs.enquiry_default_message');
        }

        $vehicle->load(['dealer.owner', 'user', 'dmrFactVehicle.variant.model.brand']);

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
        $lead = Lead::create($this->withAttribution([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ], $request));
        $this->recordFunnelConvert($request, $vehicle, 'enquiry');

        // Create enquiry record with the message details (user_id can be null for guest users)
        $enquirySubject = $this->enquirySubject('messages.enquiries.subjects.enquiry_about', $vehicle);
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
                __('messages.audit.lead_created_for_vehicle'),
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
                __('messages.audit.enquiry_submitted_for_vehicle'),
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
        $this->sendVehicleEnquiryEmail($vehicle, $enquiry);
        $this->notifyEnquiryRecipients($vehicle, $enquiry);

        $metaLeadEventId = $this->dispatchMetaLead($request, $vehicle, $enquiry);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.messages.enquiry_submitted_successfully'),
            'data' => [
                'lead_id' => $lead->id,
                'enquiry_id' => $enquiry->id,
                'meta_lead_event_id' => $metaLeadEventId,
            ],
        ], 201);
    }

    /**
     * Show test drive request form
     */
    public function showTestDriveForm(Vehicle $vehicle): View
    {
        $this->abortUnlessPublishedForEnquiry($vehicle);

        $vehicle->load(['dealer.owner', 'user', 'images', 'dmrFactVehicle.variant.model.brand']);
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
        if ($blocked = $this->enquiryBlockedResponse($vehicle)) {
            return $blocked;
        }

        // Get authenticated user (can be null for guest users)
        $user = $this->authService->getAuthenticatedUser($request);

        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:5000',
        ]);

        if (empty(trim((string) ($validated['message'] ?? '')))) {
            $validated['message'] = __('messages.dialogs.test_drive_default_message');
        }

        $vehicle->load(['dealer.owner', 'user', 'dmrFactVehicle.variant.model.brand']);

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
        $lead = Lead::create($this->withAttribution([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ], $request));
        $this->recordFunnelConvert($request, $vehicle, 'test-drive');

        // Create enquiry record with type "Test Drive" (user_id can be null for guest users)
        $enquirySubject = $this->enquirySubject('messages.enquiries.subjects.test_drive_for', $vehicle);
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
                __('messages.audit.lead_created_for_vehicle'),
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
                __('messages.audit.test_drive_submitted_for_vehicle'),
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
        $this->sendVehicleEnquiryEmail($vehicle, $enquiry);
        $this->notifyEnquiryRecipients($vehicle, $enquiry);

        $metaLeadEventId = $this->dispatchMetaLead($request, $vehicle, $enquiry);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.api.test_drive_submitted_followup'),
            'data' => [
                'lead_id' => $lead->id,
                'enquiry_id' => $enquiry->id,
                'meta_lead_event_id' => $metaLeadEventId,
            ],
        ], 201);
    }

    /**
     * Show price negotiation form
     */
    public function showPriceNegotiationForm(Vehicle $vehicle): View
    {
        $this->abortUnlessPublishedForEnquiry($vehicle);

        $vehicle->load(['dealer.owner', 'user', 'images', 'dmrFactVehicle.variant.model.brand']);
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
        if ($blocked = $this->enquiryBlockedResponse($vehicle)) {
            return $blocked;
        }

        // Get authenticated user (can be null for guest users)
        $user = $this->authService->getAuthenticatedUser($request);

        // Validate form data
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
        ]);

        $vehicle->load(['dealer.owner', 'user', 'dmrFactVehicle.variant.model.brand']);

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
        $lead = Lead::create($this->withAttribution([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ], $request));
        $this->recordFunnelConvert($request, $vehicle, 'price');

        // Create enquiry record with type "Price Enquiry" (user_id can be null for guest users)
        $enquirySubject = $this->enquirySubject('messages.enquiries.subjects.price_negotiation_for', $vehicle);
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
                __('messages.audit.lead_created_for_vehicle'),
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
                __('messages.audit.price_negotiation_submitted_for_vehicle'),
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
        $this->sendVehicleEnquiryEmail($vehicle, $enquiry);
        $this->notifyEnquiryRecipients($vehicle, $enquiry);

        $metaLeadEventId = $this->dispatchMetaLead($request, $vehicle, $enquiry);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.api.price_negotiation_submitted_followup'),
            'data' => [
                'lead_id' => $lead->id,
                'enquiry_id' => $enquiry->id,
                'meta_lead_event_id' => $metaLeadEventId,
            ],
        ], 201);
    }

    /**
     * Submit exchange request form and create lead + enquiry
     * Allows both authenticated and guest users
     */
    public function submitExchangeForm(Request $request, Vehicle $vehicle): JsonResponse
    {
        if ($blocked = $this->enquiryBlockedResponse($vehicle)) {
            return $blocked;
        }

        $user = $this->authService->getAuthenticatedUser($request);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'licence_plate' => 'required|string|max:20',
            'kilometers' => 'required|numeric|min:0',
            'expected_price' => 'required|string|max:50',
            'message' => 'required|string|max:5000',
        ]);

        $vehicle->load(['dealer.owner', 'user', 'dmrFactVehicle.variant.model.brand']);
        $dealerId = $vehicle->dealer_id;

        $sourceName = $this->getSourceName($request);
        $source = Source::firstOrCreate(['name' => $sourceName]);

        $leadCategory = LeadCategory::where('name', 'Exchange Request')->first();
        if (!$leadCategory) {
            $leadCategory = LeadCategory::where('name', 'Enquire')->first();
        }

        $leadIntentId = $this->getLeadIntentId('Exchange Request');

        if ($user && $validated['name'] !== $user->name) {
            $user->name = $validated['name'];
            $user->save();
        }

        $lead = Lead::create($this->withAttribution([
            'vehicle_id' => $vehicle->id,
            'buyer_user_id' => $user?->id,
            'dealer_id' => $dealerId,
            'lead_stage_id' => LeadStage::NEW,
            'lead_intent_id' => $leadIntentId,
            'source_id' => $source->id,
            'lead_category_id' => $leadCategory?->id,
            'created_at' => now(),
            'last_activity_at' => now(),
        ], $request));
        $this->recordFunnelConvert($request, $vehicle, 'exchange');

        $enquiryMessage = "Licence plate: {$validated['licence_plate']}\n";
        $enquiryMessage .= "Kilometres: {$validated['kilometers']}\n";
        $enquiryMessage .= "Expected price (exchange vehicle): {$validated['expected_price']}\n\n";
        $enquiryMessage .= "Message:\n{$validated['message']}";

        $enquirySubject = $this->enquirySubject('messages.enquiries.subjects.exchange_for', $vehicle);
        $enquiry = Enquiry::create([
            'lead_id' => $lead->id,
            'subject' => $enquirySubject,
            'message' => $enquiryMessage,
            'type' => 'Trade-In',
            'status' => Enquiries::STATUSES[0],
            'source' => $sourceName,
            'user_id' => $user?->id,
            'vehicle_id' => $vehicle->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        try {
            $this->auditLogService->logCreateForGuest(
                $user,
                'Lead',
                $lead->id,
                $lead->toArray(),
                $request,
                'Vehicle',
                $vehicle->id,
                __('messages.audit.lead_created_for_vehicle'),
                ['lead', 'enquiry']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for lead creation', [
                'lead_id' => $lead->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->auditLogService->logCreateForGuest(
                $user,
                'Enquiry',
                $enquiry->id,
                $enquiry->toArray(),
                $request,
                'Vehicle',
                $vehicle->id,
                __('messages.audit.exchange_request_submitted_for_vehicle'),
                ['enquiry', 'exchange']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for enquiry creation', [
                'enquiry_id' => $enquiry->id,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->sendVehicleEnquiryEmail($vehicle, $enquiry);
        $this->notifyEnquiryRecipients($vehicle, $enquiry);

        $metaLeadEventId = $this->dispatchMetaLead($request, $vehicle, $enquiry);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.api.exchange_request_submitted_followup'),
            'data' => [
                'lead_id' => $lead->id,
                'enquiry_id' => $enquiry->id,
                'meta_lead_event_id' => $metaLeadEventId,
            ],
        ], 201);
    }
}
