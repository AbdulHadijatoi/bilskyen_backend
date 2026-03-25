<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Enquiry;
use App\Models\ListingViewsLog;
use App\Models\VehicleImage;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\Category;
use App\Models\ModelYear;
use App\Models\FuelType;
use App\Models\GearType;
use App\Models\ListingType;
use App\Models\Color;
use App\Models\Variant;
use App\Models\Euronom;
use App\Models\EquipmentType;
use App\Models\Equipment;
use App\Models\Location;
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
            }, 'dmrFactVehicle.variant.model.brand'])
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

        // Validate request - similar to SellYourCarController but for updates
        $validated = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'km_driven' => ['nullable', 'integer', 'min:0'],
            'vehicle_list_status_id' => ['sometimes', 'nullable', 'integer', 'exists:vehicle_list_statuses,id'],
            'description' => ['nullable', 'string'],
            'variant_id' => ['nullable', 'exists:variants,id'],
            'color_id' => ['nullable', 'exists:colors,id'],
            'first_registration_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'first_registration_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'last_inspection_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'last_inspection_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fuel_efficiency' => ['nullable', 'numeric', 'min:0'],
            'technical_total_weight' => ['nullable', 'integer', 'min:0'],
            'euronom_id' => ['nullable', 'exists:euronorms,id'],
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['exists:equipments,id'],
            'servicebog' => ['nullable', 'in:Yes,No,Default'],
            'seller_phone' => ['nullable', 'string', 'max:30'],
            'seller_address' => ['nullable', 'string'],
            'seller_postcode' => ['nullable', 'string', 'max:10'],
            'images' => ['nullable'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,gif'],
            'existing_image_ids' => ['nullable', 'array'],
            'existing_image_ids.*' => ['integer', 'exists:vehicle_images,id'],
            'deleted_image_ids' => ['nullable', 'array'],
            'deleted_image_ids.*' => ['integer', 'exists:vehicle_images,id'],
            'image_sort_order' => ['nullable', 'array'],
            'image_sort_order.*' => ['integer'],
        ]);

        // Store before state for audit log
        $beforeState = $vehicle->toArray();

        try {
            // Handle month/year to date conversion for first_registration_date
            $firstRegistrationDate = null;
            if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
                $month = $request->input('first_registration_month');
                $year = $request->input('first_registration_year');
                if ($month && $year) {
                    $firstRegistrationDate = sprintf('%04d-%02d-01', $year, $month);
                }
            }

            // Handle month/year to date conversion for last_inspection_date
            $lastInspectionDate = null;
            if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
                $month = $request->input('last_inspection_month');
                $year = $request->input('last_inspection_year');
                if ($month && $year) {
                    $lastInspectionDate = sprintf('%04d-%02d-01', $year, $month);
                }
            }

            // Prepare vehicle data
            $vehicleData = [];
            if ($request->has('title')) {
                $vehicleData['title'] = $request->input('title');
            }
            if ($request->has('price')) {
                $vehicleData['price'] = $request->input('price');
            }
            if ($request->has('km_driven')) {
                $vehicleData['km_driven'] = $request->input('km_driven');
            }
            if ($request->has('vehicle_list_status_id')) {
                $vehicleData['vehicle_list_status_id'] = $request->input('vehicle_list_status_id');
            }
            if ($request->has('fuel_efficiency')) {
                $vehicleData['fuel_efficiency'] = $request->input('fuel_efficiency');
            }
            if ($request->has('seller_address')) {
                $vehicleData['seller_address'] = $request->input('seller_address');
            }
            if ($request->has('seller_postcode')) {
                $vehicleData['seller_postcode'] = $request->input('seller_postcode');
            }
            if ($firstRegistrationDate) {
                $vehicleData['first_registration_date'] = $firstRegistrationDate;
            }

            // Prepare vehicle details data
            $vehicleDetailsData = [];
            if ($request->has('description')) {
                $vehicleDetailsData['description'] = $request->input('description');
            }
            if ($request->has('variant_id')) {
                $vehicleDetailsData['variant_id'] = $request->input('variant_id');
            }
            if ($request->has('color_id')) {
                $vehicleDetailsData['color_id'] = $request->input('color_id');
            }
            if ($request->has('technical_total_weight')) {
                $vehicleDetailsData['technical_total_weight'] = $request->input('technical_total_weight');
            }
            if ($request->has('euronom_id')) {
                $vehicleDetailsData['euronom_id'] = $request->input('euronom_id');
            }
            if ($lastInspectionDate) {
                $vehicleDetailsData['last_inspection_date'] = $lastInspectionDate;
            }
            if ($request->has('servicebog')) {
                $vehicleDetailsData['servicebog'] = $request->input('servicebog');
            }
            if ($request->has('seller_phone')) {
                $vehicleDetailsData['seller_phone'] = $request->input('seller_phone');
            }

            // Handle equipment
            $equipmentIds = null;
            if ($request->has('equipment_ids')) {
                $equipmentIds = $request->input('equipment_ids');
            }

            // Handle images separately - don't pass to VehicleService if we're keeping existing ones
            // VehicleService will delete all images if images array is provided, so we handle manually
            
            // Get existing images that should be kept
            $existingImageIds = [];
            if ($request->has('existing_image_ids') && is_array($request->input('existing_image_ids'))) {
                $existingImageIds = $request->input('existing_image_ids');
            }
            
            // Delete removed images first
            if ($request->has('deleted_image_ids') && is_array($request->input('deleted_image_ids'))) {
                $deletedImageIds = $request->input('deleted_image_ids');
                $imagesToDelete = VehicleImage::whereIn('id', $deletedImageIds)
                    ->where('vehicle_id', $vehicle->id)
                    ->get();
                
                foreach ($imagesToDelete as $img) {
                    // Delete files using FileService if available
                    try {
                        $fileService = app(\App\Services\FileService::class);
                        if ($img->image_path) {
                            $fileService->deleteFiles([$img->image_path]);
                        }
                        if ($img->thumbnail_path) {
                            $fileService->deleteFiles([$img->thumbnail_path]);
                        }
                    } catch (\Exception $e) {
                        // Fallback to direct file deletion
                        if ($img->image_path && file_exists(storage_path('app/public/' . $img->image_path))) {
                            @unlink(storage_path('app/public/' . $img->image_path));
                        }
                        if ($img->thumbnail_path && file_exists(storage_path('app/public/' . $img->thumbnail_path))) {
                            @unlink(storage_path('app/public/' . $img->thumbnail_path));
                        }
                    }
                    $img->delete();
                }
            }
            
            // Handle image sort order for existing images
            if ($request->has('image_sort_order') && is_array($request->input('image_sort_order'))) {
                $sortOrder = $request->input('image_sort_order');
                // Update sort order for existing images
                foreach ($sortOrder as $imageId => $order) {
                    VehicleImage::where('id', $imageId)
                        ->where('vehicle_id', $vehicle->id)
                        ->update(['sort_order' => (int)$order]);
                }
            }
            
            // Handle new image uploads separately (don't pass to VehicleService to avoid deleting existing)
            if ($request->hasFile('images')) {
                $newImages = $request->file('images');
                if (!is_array($newImages)) {
                    $newImages = [$newImages];
                }
                
                // Get current max sort_order
                $currentMaxSortOrder = VehicleImage::where('vehicle_id', $vehicle->id)->max('sort_order') ?? -1;
                $nextSortOrder = $currentMaxSortOrder + 1;
                
                // If we have sort order from request, use the max from that
                if ($request->has('image_sort_order') && is_array($request->input('image_sort_order'))) {
                    $sortOrder = $request->input('image_sort_order');
                    if (!empty($sortOrder)) {
                        $maxUsedOrder = max(array_values($sortOrder));
                        $nextSortOrder = $maxUsedOrder + 1;
                    }
                }
                
                $fileService = app(\App\Services\FileService::class);
                
                foreach ($newImages as $file) {
                    if ($file && $file->isValid()) {
                        try {
                            // Upload file with thumbnail generation
                            $fileService->validateFile($file);
                            $uploadedUrl = $fileService->uploadFiles(
                                [$file], 
                                'public', 
                                'vehicles',
                                true, // createThumbnails
                                false, // optimizeImages
                                300, // thumbnailWidth
                                300  // thumbnailHeight
                            )[0];
                            
                            // Extract relative path from URL
                            $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH));
                            
                            // Extract thumbnail path
                            $thumbnailPath = null;
                            try {
                                $thumbnailUrl = $fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                                $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                            } catch (\Exception $e) {
                                // Thumbnail generation failed, continue without thumbnail
                            }
                            
                            VehicleImage::create([
                                'vehicle_id' => $vehicle->id,
                                'image_path' => $imagePath,
                                'thumbnail_path' => $thumbnailPath,
                                'sort_order' => $nextSortOrder++,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Error uploading new image for vehicle', [
                                'vehicle_id' => $vehicle->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            // Add equipment IDs
            if ($equipmentIds !== null) {
                $vehicleData['equipment_ids'] = $equipmentIds;
            }

            // Add vehicle details data
            if (!empty($vehicleDetailsData)) {
                foreach ($vehicleDetailsData as $key => $value) {
                    $vehicleData[$key] = $value;
                }
            }

            // Update vehicle using VehicleService
            $updatedVehicle = $this->vehicleService->updateVehicle($vehicle, $vehicleData);

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
                'message' => __('messages.errors.vehicle_updated_success'),
                'vehicle' => $updatedVehicle->load(['images', 'equipment', 'dmrFactVehicle.variant.model.brand']),
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

        // Validate request
        $validated = $request->validate([
            'vehicle_list_status_id' => ['required', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);

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
            'colors' => \App\Models\Color::orderBy('name')->get(),
            'variants' => \App\Models\Variant::orderBy('name')->get(),
            'euronorms' => \App\Models\Euronom::orderBy('name')->get(),
            'equipmentTypes' => \App\Models\EquipmentType::with(['equipments' => function($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get(),
            'equipment' => \App\Models\Equipment::with('equipmentType')->orderBy('name')->get(),
            'locations' => \App\Models\Location::select('city', 'postcode', 'region')->orderBy('city')->get(),
        ];
    }
}
