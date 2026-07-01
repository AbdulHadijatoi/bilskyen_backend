<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Enquiry;
use App\Models\ListingViewsLog;
use App\Constants\VehicleListStatus;
use App\Services\VehicleService;
use App\Services\VehicleDetailPresentationService;
use App\Services\AuditLogService;
use App\Services\SellerVehicleEditService;
use App\Constants\ApiStatusCode;
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
        private AuditLogService $auditLogService,
        private VehicleDetailPresentationService $vehicleDetailPresentationService,
        private SellerVehicleEditService $sellerVehicleEditService
    ) {}

    /**
     * Get seller's vehicles list
     * GET /api/v1/seller/vehicles
     */
    public function getVehicles(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = $request->only([
            'list_status_id',
            'search',
            'sort',
        ]);

        // Build query for seller's vehicles
        $query = Vehicle::where('user_id', $user->id)
            ->with(['images' => function ($q) {
                $q->orderBy('sort_order');
            }, 'equipment', 'dmrFactVehicle']);

        // Apply status filter
        if ($request->has('list_status_id') && $request->input('list_status_id')) {
            $query->where('list_status_id', $request->input('list_status_id'));
        }

        // Apply search filter
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('registration', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
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
                'vin' => $vehicle->dmrFactVehicle?->stel_nummer,
                'price' => $vehicle->price,
                'km_driven' => $vehicle->km_driven,
                'list_status_id' => $vehicle->list_status_id,
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
     * Edit form: vehicle values + lookups (same data as seller-vehicle-edit Blade / JS).
     * GET /api/v1/seller/vehicles/{id}/edit
     */
    public function getVehicleEditForm(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $vehicle = Vehicle::with([
            'images' => function ($q) {
                $q->orderBy('sort_order');
            },
            'equipment',
            'dmrFactVehicle.variant.model.brand',
            'dmrFactVehicle.emissionNorm',
            'dmrFactVehicle.colour',
        ])->findOrFail($id);

        if ($vehicle->user_id !== $user->id) {
            return $this->forbidden(__('messages.errors.no_permission_update_vehicle'));
        }

        $data = $this->sellerVehicleEditService->buildEditFormApiPayload($vehicle, $user);

        return $this->success($data, ApiStatusCode::OK, __('messages.api.data_retrieved_successfully'));
    }

    /**
     * Get vehicle details
     * GET /api/v1/seller/vehicles/{id}
     */
    public function getVehicle(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $vehicle = Vehicle::with(array_merge($this->vehicleDetailPresentationService->detailEagerLoads(), [
            'images' => function ($q) {
                $q->orderBy('sort_order');
            },
        ]))->findOrFail($id);

        // Verify ownership
        if ($vehicle->user_id !== $user->id) {
            return $this->error(__('messages.errors.no_permission_view_vehicle'), null, 403);
        }

        $payload = $this->vehicleDetailPresentationService->buildDetailPayload($vehicle);

        return $this->success(array_merge($payload, [
            'images' => $vehicle->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->image_url,
                    'thumbnail_url' => $image->thumbnail_url,
                    'sort_order' => $image->sort_order,
                ];
            }),
        ]));
    }

    /**
     * Update vehicle (same rules and image handling as web seller.vehicle.update).
     * PUT /api/v1/seller/vehicles/{id}
     *
     * Send multipart/form-data with the same fields as the web form (including images[], deleted_image_ids[], existing_image_ids[], image_sort_order).
     */
    public function updateVehicle(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $vehicle = Vehicle::with(['images', 'equipment', 'dmrFactVehicle.variant.model.brand'])->findOrFail($id);

        if ($vehicle->user_id !== $user->id) {
            return $this->error(__('messages.errors.no_permission_update_vehicle'), null, 403);
        }

        try {
            $result = $this->sellerVehicleEditService->updateSellerVehicle($request, $vehicle, $user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            Log::error('Seller API vehicle update failed', [
                'vehicle_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                __('messages.errors.failed_to_update_vehicle', ['message' => $e->getMessage()]),
                null,
                ApiStatusCode::INTERNAL_SERVER_ERROR
            );
        }

        return $this->success(
            ['vehicle' => $result['vehicle']],
            ApiStatusCode::OK,
            __('messages.errors.vehicle_updated_success')
        );
    }

    /**
     * Update vehicle status
     * PATCH /api/v1/seller/vehicles/{id}/status
     */
    public function updateVehicleStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'list_status_id' => ['required', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $vehicle = Vehicle::findOrFail($id);

        // Verify ownership
        if ($vehicle->user_id !== $user->id) {
            return $this->error(__('messages.errors.no_permission_update_vehicle'), null, 403);
        }

        // Store before state
        $beforeState = $vehicle->toArray();

        // Update status
        $vehicle->list_status_id = $request->input('list_status_id');
        
        // Set published_at if publishing
        if ($request->input('list_status_id') == VehicleListStatus::PUBLISHED && !$vehicle->published_at) {
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
            return $this->error(__('messages.errors.no_permission_delete_vehicle'), null, 403);
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
            return $this->error(__('messages.errors.no_permission_view_inquiries'), null, 403);
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

        $viewsFromVehicles = Vehicle::whereIn('id', $vehicleIds)->sum('views_count');
        if ($viewsFromVehicles > $statistics['total_views']) {
            $statistics['total_views'] = $viewsFromVehicles;
        }

        // Add status breakdown
        $statistics['by_status'] = [
            'published' => Vehicle::where('user_id', $user->id)
                ->where('list_status_id', VehicleListStatus::PUBLISHED)
                ->count(),
            'draft' => Vehicle::where('user_id', $user->id)
                ->where('list_status_id', VehicleListStatus::DRAFT)
                ->count(),
            'sold' => Vehicle::where('user_id', $user->id)
                ->where('list_status_id', VehicleListStatus::SOLD)
                ->count(),
            'archived' => Vehicle::where('user_id', $user->id)
                ->where('list_status_id', VehicleListStatus::ARCHIVED)
                ->count(),
        ];

        return $this->success($statistics);
    }
}
