<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Enquiry;
use App\Models\ListingViewsLog;
use App\Models\VehicleDetail;
use App\Constants\VehicleListStatus;
use App\Services\AuthService;
use App\Services\SellerTokenService;
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
        private AuditLogService $auditLogService
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

        // Get all vehicles for this seller
        $vehicles = Vehicle::where('user_id', $user->id)
            ->with(['images' => function ($q) {
                $q->orderBy('sort_order');
            }, 'details'])
            ->withCount(['enquiries as enquiries_count', 'viewLogs as views_count'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculate statistics
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

        // Get vehicle and verify ownership
        $vehicle = Vehicle::with(['images', 'details', 'equipment'])->findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            abort(403, 'You do not have permission to edit this vehicle');
        }

        // Load lookup data for form
        $lookupData = $this->getLookupData();

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
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to update this vehicle',
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'km_driven' => ['nullable', 'integer', 'min:0'],
            'vehicle_list_status_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicle_list_statuses,id'],
            'description' => ['nullable', 'string'],
        ]);

        // Store before state for audit log
        $beforeState = $vehicle->toArray();

        // Update vehicle using VehicleService
        $updatedVehicle = $this->vehicleService->updateVehicle($vehicle, $validated);

        // Audit log
        try {
            $this->auditLogService->logUpdate(
                $user,
                'Vehicle',
                $vehicle->id,
                $beforeState,
                $updatedVehicle->toArray(),
                $request,
                'Seller',
                null,
                "Vehicle updated by seller: {$updatedVehicle->title}",
                ['vehicle', 'seller', 'update']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for vehicle update', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle updated successfully',
            'vehicle' => $updatedVehicle->load(['images', 'details']),
        ]);
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
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to unpublish this vehicle',
            ], 403);
        }

        // Store before state
        $beforeState = $vehicle->toArray();

        // Update status to ARCHIVED
        $vehicle->vehicle_list_status_id = VehicleListStatus::ARCHIVED;
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
            'message' => 'Vehicle unpublished successfully',
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
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to delete this vehicle',
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
            'message' => 'Vehicle deleted successfully',
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
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'vehicle_list_status_id' => ['required', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to update this vehicle',
            ], 403);
        }

        // Store before state
        $beforeState = $vehicle->toArray();

        // Update status
        $vehicle->vehicle_list_status_id = $validated['vehicle_list_status_id'];
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
            'message' => 'Vehicle status updated successfully',
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
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get vehicle and verify ownership
        $vehicle = Vehicle::findOrFail($id);
        
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to view inquiries for this vehicle',
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

    /**
     * Get lookup data for forms
     */
    private function getLookupData(): array
    {
        return [
            'brands' => \App\Models\Brand::orderBy('name')->get(),
            'models' => \App\Models\VehicleModel::orderBy('name')->get(),
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'modelYears' => \App\Models\ModelYear::orderBy('name', 'desc')->get(),
            'fuelTypes' => \App\Models\FuelType::orderBy('name')->get(),
            'gearTypes' => \App\Models\GearType::orderBy('name')->get(),
            'listingTypes' => \App\Models\ListingType::orderBy('name')->get(),
            'vehicleListStatuses' => \App\Models\VehicleListStatus::orderBy('name')->get(),
        ];
    }
}
