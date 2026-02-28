<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Enquiry;
use App\Models\ListingViewsLog;
use App\Models\VehicleDetail;
use App\Constants\VehicleListStatus;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Seller Profile API Controller
 * Handles seller profile operations for mobile app
 * All routes require authentication (auth:api middleware)
 */
class SellerProfileController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService
    ) {}

    /**
     * Get seller's vehicles list
     * GET /api/v1/seller/vehicles
     */
    public function getVehicles(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = $request->only([
            'vehicle_list_status_id',
            'search',
            'sort',
        ]);

        // Build query for seller's vehicles
        $query = Vehicle::where('user_id', $user->id)
            ->with(['images' => function ($q) {
                $q->orderBy('sort_order');
            }, 'details', 'equipment']);

        // Apply status filter
        if ($request->has('vehicle_list_status_id') && $request->input('vehicle_list_status_id')) {
            $query->where('vehicle_list_status_id', $request->input('vehicle_list_status_id'));
        }

        // Apply search filter
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('registration', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sort = $request->input('sort', 'created_at_desc');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'created_at_desc':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Get vehicles with counts
        $vehicles = $query->withCount(['enquiries as enquiries_count', 'viewLogs as views_count'])
            ->paginate($request->input('limit', 15));

        // Format vehicles for response
        $formattedVehicles = collect($vehicles->items())->map(function ($vehicle) {
            $firstImage = $vehicle->images->first();
            
            return [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'registration' => $vehicle->registration,
                'vin' => $vehicle->vin,
                'price' => $vehicle->price,
                'km_driven' => $vehicle->km_driven,
                'vehicle_list_status_id' => $vehicle->vehicle_list_status_id,
                'vehicle_list_status_name' => $vehicle->vehicle_list_status_name,
                'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                'published_at' => $vehicle->published_at?->format('Y-m-d H:i:s'),
                'created_at' => $vehicle->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $vehicle->updated_at->format('Y-m-d H:i:s'),
                'image_url' => $firstImage?->image_url ?? null,
                'thumbnail_url' => $firstImage?->thumbnail_url ?? null,
                'enquiries_count' => $vehicle->enquiries_count ?? 0,
                'views_count' => $vehicle->views_count ?? 0,
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
     * Get vehicle details
     * GET /api/v1/seller/vehicles/{id}
     */
    public function getVehicle(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $vehicle = Vehicle::with([
            'images' => function ($q) {
                $q->orderBy('sort_order');
            },
            'details',
            'equipment',
            'details.color',
            'details.variant',
            'details.euronom'
        ])->findOrFail($id);

        // Verify ownership
        if ($vehicle->user_id !== $user->id) {
            return $this->error('You do not have permission to view this vehicle', null, 403);
        }

        return $this->success($vehicle);
    }

    /**
     * Update vehicle
     * PUT /api/v1/seller/vehicles/{id}
     */
    public function updateVehicle(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $vehicle = Vehicle::findOrFail($id);

        // Verify ownership
        if ($vehicle->user_id !== $user->id) {
            return $this->error('You do not have permission to update this vehicle', null, 403);
        }

        // Store before state for audit log
        $beforeState = $vehicle->toArray();

        // Use VehicleService to update vehicle
        $data = $request->all();
        $vehicle = $this->vehicleService->updateVehicle($vehicle, $data);
        $vehicle->refresh();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Vehicle',
                $vehicle->id,
                $beforeState,
                $vehicle->toArray(),
                $request,
                'Seller',
                null,
                "Vehicle updated by seller: {$vehicle->title}",
                ['vehicle', 'seller', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle update', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($vehicle->load(['images', 'details', 'equipment']));
    }

    /**
     * Update vehicle status
     * PATCH /api/v1/seller/vehicles/{id}/status
     */
    public function updateVehicleStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'vehicle_list_status_id' => ['required', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $vehicle = Vehicle::findOrFail($id);

        // Verify ownership
        if ($vehicle->user_id !== $user->id) {
            return $this->error('You do not have permission to update this vehicle', null, 403);
        }

        // Store before state
        $beforeState = $vehicle->toArray();

        // Update status
        $vehicle->vehicle_list_status_id = $request->input('vehicle_list_status_id');
        
        // Set published_at if publishing
        if ($request->input('vehicle_list_status_id') == VehicleListStatus::PUBLISHED && !$vehicle->published_at) {
            $vehicle->published_at = now();
        }
        
        $vehicle->save();

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Vehicle',
                $vehicle->id,
                $beforeState,
                $vehicle->toArray(),
                $request,
                'Seller',
                null,
                "Vehicle status updated by seller: {$vehicle->title}",
                ['vehicle', 'seller', 'status']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle status update', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($vehicle->fresh());
    }

    /**
     * Delete vehicle
     * DELETE /api/v1/seller/vehicles/{id}
     */
    public function deleteVehicle(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $vehicle = Vehicle::findOrFail($id);

        // Verify ownership
        if ($vehicle->user_id !== $user->id) {
            return $this->error('You do not have permission to delete this vehicle', null, 403);
        }

        // Store before state for audit log
        $beforeState = $vehicle->toArray();

        // Delete vehicle (soft delete)
        $this->vehicleService->deleteVehicle($vehicle);

        // Audit log
        try {
            $this->auditLogService->logDelete(
                $user,
                'Vehicle',
                $id,
                $beforeState,
                $request,
                'Seller',
                null,
                "Vehicle deleted by seller: " . ($beforeState['title'] ?? 'N/A'),
                ['vehicle', 'seller', 'delete']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle deletion', [
                'vehicle_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(['message' => __('messages.messages.vehicle_deleted_successfully')]);
    }

    /**
     * Get all inquiries for seller's vehicles
     * GET /api/v1/seller/inquiries
     */
    public function getInquiries(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all vehicle IDs for this seller
        $vehicleIds = Vehicle::where('user_id', $user->id)->pluck('id');

        $query = Enquiry::whereIn('vehicle_id', $vehicleIds)
            ->with(['vehicle' => function ($q) {
                $q->select('id', 'title', 'registration', 'price');
            }])
            ->orderBy('created_at', 'desc');

        // Filter by vehicle_id if provided
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }

        $inquiries = $query->paginate($request->input('limit', 15));

        return $this->paginated($inquiries);
    }

    /**
     * Get inquiry details
     * GET /api/v1/seller/inquiries/{id}
     */
    public function getInquiry(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $inquiry = Enquiry::with(['vehicle' => function ($q) {
            $q->select('id', 'title', 'registration', 'price');
        }])->findOrFail($id);

        // Verify the inquiry belongs to one of seller's vehicles
        $vehicle = $inquiry->vehicle;
        if (!$vehicle || $vehicle->user_id !== $user->id) {
            return $this->error('You do not have permission to view this inquiry', null, 403);
        }

        return $this->success($inquiry);
    }

    /**
     * Get seller statistics
     * GET /api/v1/seller/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all vehicle IDs for this seller
        $vehicleIds = Vehicle::where('user_id', $user->id)->pluck('id');

        $statistics = [
            'total_vehicles' => Vehicle::where('user_id', $user->id)->count(),
            'total_worth' => Vehicle::where('user_id', $user->id)->sum('price') ?? 0,
            'total_inquiries' => Enquiry::whereIn('vehicle_id', $vehicleIds)->count(),
            'total_views' => ListingViewsLog::whereIn('vehicle_id', $vehicleIds)->count(),
        ];

        // Also get views from vehicle_details for more accurate count
        $viewsFromDetails = VehicleDetail::whereIn('vehicle_id', $vehicleIds)->sum('views_count');
        if ($viewsFromDetails > $statistics['total_views']) {
            $statistics['total_views'] = $viewsFromDetails;
        }

        // Add status breakdown
        $statistics['by_status'] = [
            'published' => Vehicle::where('user_id', $user->id)
                ->where('vehicle_list_status_id', VehicleListStatus::PUBLISHED)
                ->count(),
            'draft' => Vehicle::where('user_id', $user->id)
                ->where('vehicle_list_status_id', VehicleListStatus::DRAFT)
                ->count(),
            'sold' => Vehicle::where('user_id', $user->id)
                ->where('vehicle_list_status_id', VehicleListStatus::SOLD)
                ->count(),
            'archived' => Vehicle::where('user_id', $user->id)
                ->where('vehicle_list_status_id', VehicleListStatus::ARCHIVED)
                ->count(),
        ];

        return $this->success($statistics);
    }
}
