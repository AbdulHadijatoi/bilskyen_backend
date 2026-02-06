<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\Vehicle;
use App\Services\AuditLogService;
use App\Services\DealerContextService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Dealer Enquiry Controller
 * Handles enquiries for dealer's vehicles
 */
class DealerEnquiryController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}
    /**
     * Get all enquiries for dealer's vehicles
     */
    public function index(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        // Check if enquiry_management feature is enabled
        if (!$this->subscriptionFeatureService->hasFeature($dealer, 'enquiry_management')) {
            return $this->error(
                'Enquiry management is not available in your current subscription plan. Please upgrade to access this feature.',
                403
            );
        }

        // Get all vehicle IDs for this dealer
        $vehicleIds = Vehicle::where('dealer_id', $dealer->id)->pluck('id');

        $query = Enquiry::with(['user', 'vehicle', 'contact'])
            ->whereIn('vehicle_id', $vehicleIds);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $enquiries = $query->paginate($request->get('limit', 15));

        return $this->paginated($enquiries);
    }

    /**
     * Get a specific enquiry
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dealer = $request->user()->dealer;
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        // Get all vehicle IDs for this dealer
        $vehicleIds = Vehicle::where('dealer_id', $dealer->id)->pluck('id');

        $enquiry = Enquiry::with(['user', 'vehicle.brand', 'vehicle.model', 'contact'])
            ->whereIn('vehicle_id', $vehicleIds)
            ->findOrFail($id);

        return $this->success($enquiry);
    }

    /**
     * Update enquiry status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:New,In Progress,Awaiting Customer,Responded,Closed,Converted to Sale,Cancelled',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Get all vehicle IDs for this dealer
        $vehicleIds = Vehicle::where('dealer_id', $dealer->id)->pluck('id');

        $enquiry = Enquiry::whereIn('vehicle_id', $vehicleIds)->findOrFail($id);
        $oldStatus = $enquiry->status;
        $enquiry->status = $request->status;
        $enquiry->save();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Enquiry',
                $enquiry->id,
                ['status' => $oldStatus],
                ['status' => $request->status],
                $request,
                'Dealer',
                $dealer->id,
                "Enquiry status changed: {$oldStatus} -> {$request->status}",
                ['dealer', 'enquiry', 'update', 'status']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for enquiry status update', [
                'enquiry_id' => $enquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($enquiry->load(['user', 'vehicle', 'contact']));
    }

    /**
     * Update enquiry type
     */
    public function updateType(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:General,Sales,Vehicle Information,Test Drive,Price Enquiry,Financing,Insurance,Trade-In,Availability,Service,Parts,Complaint,Feedback,Other',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Get all vehicle IDs for this dealer
        $vehicleIds = Vehicle::where('dealer_id', $dealer->id)->pluck('id');

        $enquiry = Enquiry::whereIn('vehicle_id', $vehicleIds)->findOrFail($id);
        $oldType = $enquiry->type;
        $enquiry->type = $request->type;
        $enquiry->save();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Enquiry',
                $enquiry->id,
                ['type' => $oldType],
                ['type' => $request->type],
                $request,
                'Dealer',
                $dealer->id,
                "Enquiry type changed: {$oldType} -> {$request->type}",
                ['dealer', 'enquiry', 'update', 'type']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for enquiry type update', [
                'enquiry_id' => $enquiry->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($enquiry->load(['user', 'vehicle', 'contact']));
    }
}
