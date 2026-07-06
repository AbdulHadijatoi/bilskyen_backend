<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Enquiry;
use App\Models\ListingViewsLog;
use App\Constants\VehicleListStatus;
use App\Services\AuthService;
use App\Services\SellerTokenService;
use App\Services\SellerVehicleEditService;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use App\Helpers\FormatHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellerController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private SellerTokenService $tokenService,
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService,
        private SellerVehicleEditService $sellerVehicleEditService
    ) {}

    /**
     * Show seller dashboard
     */
    public function dashboard(Request $request, string $token): View
    {
        // Validate token and get user
        $user = $this->tokenService->validateToken($token);
        $authenticatedUser = $this->authService->getAuthenticatedUser($request);
        
        // Security: Verify token matches authenticated user
        if (!$user || !$authenticatedUser || $user->id !== $authenticatedUser->id) {
            abort(403, 'Unauthorized access');
        }

        // Get all vehicles for this seller (eager-load relations used by Blade + Vehicle accessors)
        $query = Vehicle::where('user_id', $user->id)
            ->with([
                'images' => function ($q) {
                    $q->orderBy('sort_order');
                },
                'vehicleListStatus',
                'salesType',
                'fuelType',
                'gearType',
                'brand',
                'model',
                'variant',
                'dmrFactVehicle.variant.model.brand',
            ])
            ->withCount([
                'enquiries as enquiries_count',
                'viewLogs as views_count',
            ])
            ->orderBy('created_at', 'desc');

        $currentStatus = $request->filled('status') ? (int) $request->input('status') : null;
        if ($currentStatus !== null) {
            $query->where('list_status_id', $currentStatus);
        }

        $vehicles = $query->paginate(15)->withQueryString();

        // Calculate statistics
        $vehicleIds = Vehicle::where('user_id', $user->id)->pluck('id');

        $statistics = [
            'total_vehicles' => Vehicle::where('user_id', $user->id)->count(),
            'total_worth' => Vehicle::where('user_id', $user->id)->sum('price') ?? 0,
            'total_inquiries' => Enquiry::whereIn('vehicle_id', $vehicleIds)->count(),
            'total_views' => ListingViewsLog::whereIn('vehicle_id', $vehicleIds)->count(),
        ];

        $statusCounts = Vehicle::withoutGlobalScope('defaultOrder')
            ->where('user_id', $user->id)
            ->selectRaw('list_status_id, count(*) as count')
            ->groupBy('list_status_id')
            ->pluck('count', 'list_status_id');

        // Get all enquiries for display
        $enquiries = Enquiry::whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('vehicle_id');

        return view('seller-dashboard', [
            'user' => $user,
            'vehicles' => $vehicles,
            'statistics' => $statistics,
            'statusCounts' => $statusCounts,
            'currentStatus' => $currentStatus,
            'enquiries' => $enquiries,
            'token' => $token,
        ]);
    }

    /**
     * Show vehicle edit form
     */
    public function edit(Request $request, string $token, int $id): View
    {
        // Validate token and get user
        $user = $this->tokenService->validateToken($token);
        $authenticatedUser = $this->authService->getAuthenticatedUser($request);
        
        if (!$user || !$authenticatedUser || $user->id !== $authenticatedUser->id) {
            abort(403, 'Unauthorized access');
        }

        // Get vehicle and verify ownership - load all necessary relationships
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
            abort(403, 'You do not have permission to edit this vehicle');
        }

        // Load lookup data for form (DMR-aligned lists)
        $lookupData = $this->sellerVehicleEditService->getLookupData($vehicle);

        return view('seller-vehicle-edit', [
            'user' => $user,
            'vehicle' => $vehicle,
            'lookupData' => $lookupData,
            'token' => $token,
        ]);
    }

    /**
     * Update vehicle
     */
    public function update(Request $request, string $token, int $id): JsonResponse
    {
        // Validate token and get user
        $user = $this->tokenService->validateToken($token);
        $authenticatedUser = $this->authService->getAuthenticatedUser($request);
        
        if (!$user || !$authenticatedUser || $user->id !== $authenticatedUser->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.unauthorized_access'),
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::with(['images', 'equipment', 'dmrFactVehicle.variant.model.brand'])->findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.no_permission_update_vehicle'),
            ], 403);
        }

        try {
            $result = $this->sellerVehicleEditService->updateSellerVehicle($request, $vehicle, $user);

            return response()->json([
                'status' => 'success',
                'message' => __('messages.errors.vehicle_updated_success'),
                'vehicle' => $result['vehicle'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.api.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating vehicle', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.failed_to_update_vehicle', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Unpublish vehicle (set to ARCHIVED)
     */
    public function unpublish(Request $request, string $token, int $id): JsonResponse
    {
        // Validate token and get user
        $user = $this->tokenService->validateToken($token);
        $authenticatedUser = $this->authService->getAuthenticatedUser($request);
        
        if (!$user || !$authenticatedUser || $user->id !== $authenticatedUser->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.unauthorized_access'),
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.no_permission_unpublish_vehicle'),
            ], 403);
        }

        // Store before state
        $beforeState = $vehicle->toArray();

        // Update status to ARCHIVED
        $vehicle->list_status_id = VehicleListStatus::ARCHIVED;
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
                "Vehicle unpublished by seller: {$vehicle->title}",
                ['vehicle', 'seller', 'unpublish']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle unpublish', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('messages.errors.vehicle_unpublished_success'),
        ]);
    }

    /**
     * Delete vehicle (soft delete)
     */
    public function destroy(Request $request, string $token, int $id): JsonResponse
    {
        // Validate token and get user
        $user = $this->tokenService->validateToken($token);
        $authenticatedUser = $this->authService->getAuthenticatedUser($request);
        
        if (!$user || !$authenticatedUser || $user->id !== $authenticatedUser->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.unauthorized_access'),
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.no_permission_delete_vehicle'),
            ], 403);
        }

        // Store before state
        $beforeState = $vehicle->toArray();

        // Soft delete vehicle
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

        return response()->json([
            'status' => 'success',
            'message' => __('messages.errors.vehicle_deleted_success'),
        ]);
    }

    /**
     * Update vehicle status
     */
    public function updateStatus(Request $request, string $token, int $id): JsonResponse
    {
        // Validate token and get user
        $user = $this->tokenService->validateToken($token);
        $authenticatedUser = $this->authService->getAuthenticatedUser($request);
        
        if (!$user || !$authenticatedUser || $user->id !== $authenticatedUser->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.unauthorized_access'),
            ], 403);
        }

        // Accept canonical list_status_id or legacy vehicle_list_status_id (older Blade/JS).
        $validated = $request->validate([
            'list_status_id' => ['required_without:vehicle_list_status_id', 'integer', 'exists:vehicle_list_statuses,id'],
            'vehicle_list_status_id' => ['required_without:list_status_id', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);
        $newStatusId = (int) ($validated['list_status_id'] ?? $validated['vehicle_list_status_id']);

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.no_permission_update_vehicle'),
            ], 403);
        }

        // Store before state
        $beforeState = $vehicle->toArray();

        // Update status
        $vehicle->list_status_id = (int) $newStatusId;
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

        return response()->json([
            'status' => 'success',
            'message' => __('messages.errors.vehicle_status_updated_success'),
            'vehicle' => $vehicle->fresh(),
        ]);
    }

    /**
     * Get inquiries for a vehicle
     */
    public function getInquiries(Request $request, string $token, int $id): JsonResponse
    {
        // Validate token and get user
        $user = $this->tokenService->validateToken($token);
        $authenticatedUser = $this->authService->getAuthenticatedUser($request);
        
        if (!$user || !$authenticatedUser || $user->id !== $authenticatedUser->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.unauthorized_access'),
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.errors.no_permission_view_inquiries'),
            ], 403);
        }

        // Get inquiries
        $inquiries = Enquiry::where('vehicle_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'inquiries' => $inquiries,
        ]);
    }
}
