<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\Dealer;
use App\Models\DealerUser;
use App\Models\FeaturedListing;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\ModelYear;
use App\Models\FuelType;
use App\Models\ListingType;
use App\Models\Variant;
use App\Models\Euronom;
use App\Models\Type;
use App\Models\VehicleUse;
use App\Models\BodyType;
use App\Models\PriceType;
use App\Models\Equipment;
use App\Services\VehicleService;
use App\Services\FileService;
use App\Services\AuditLogService;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\SellYourCarRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Helpers\FilterHelper;
use App\Constants\VehicleListStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleController extends Controller
{
    protected FileService $fileService;

    public function __construct(
        private VehicleService $vehicleService,
        FileService $fileService,
        private AuditLogService $auditLogService
    ) {
        $this->fileService = $fileService;
    }

    /**
     * Get featured vehicles
     * Returns only the fields needed for the featured vehicles display
     */
    public function getFeaturedVehicles(Request $request): JsonResponse
    {
        // Fetch featured vehicles from FeaturedListing model
        $featuredListings = FeaturedListing::with([
            'vehicle.images',
        ])
            ->orderBy('sort_order')
            ->get();

        // Format vehicles for JSON response (only fields used in home.blade.php)
        $formattedVehicles = $featuredListings->map(function ($featuredListing) {
            $vehicle = $featuredListing->vehicle;
            if (!$vehicle) {
                return null;
            }

            // Get first image
            $firstImage = $vehicle->images->first();
            $imageUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
            
            // Build title
            $title = $vehicle->title ?? trim(($vehicle->brand_name ?? '') . ' ' . ($vehicle->model_name ?? ''));

            return [
                'id' => $vehicle->id,
                'title' => $title,
                'version' => $vehicle->version ?? '',
                'price' => $vehicle->price ?? 0,
                'image' => $imageUrl,
                'km_driven' => $vehicle->km_driven ?? 0,
                'engine_power_hp' => $vehicle->engine_power_hp,
                'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                'fuel_type_name' => $vehicle->fuel_type_name,
                'gear_type_name' => $vehicle->gear_type_name,
            ];
        })
        ->filter() // Remove null entries
        ->values(); // Re-index array

        return $this->success([
            'vehicles' => $formattedVehicles,
        ]);
    }

    /**
     * Get vehicles list (public or dealer)
     * Uses the same filtering logic as HomeController::showVehicles
     */
    public function index(Request $request): JsonResponse
    {
        // Define advanced filter keys (vehicles and vehicle_details table attributes)
        $advancedFilterKeys = [
            // Price, Make, Model, Model Year, Mileage, Listing Type, Category
            'price_from', 'price_to', 'make', 'brand_id', 'model_id', 'model_year_id',
            'year_from', 'year_to', 'mileage_from', 'mileage_to', 
            'odometer_from', 'odometer_to', 'listing_type_id', 'vehicle_list_status_id',
            'category_id', 'price_type_id', 'condition_id',
            // Vehicle Body Type, Fuel Type, Gear Type, Drive Wheels
            'body_type_id', 'fuel_type_id', 'gear_type_id', 'drive_axles',
            // First Registration Year, Seller Type, Sales Type, Seller Distance
            'first_registration_year_from', 'first_registration_year_to',
            'seller_type', 'dealer_id', 'sales_type_id', 'seller_distance',
            // Performance
            'top_speed_from', 'top_speed_to', 'engine_power_from', 'engine_power_to',
            // Owner Tax
            'ownership_tax_from', 'ownership_tax_to',
            // Battery & Charging (EV)
            'battery_capacity_from', 'battery_capacity_to', 'range_km_from', 'range_km_to', 'charging_type',
            // Economy & Environment
            'fuel_efficiency_from', 'fuel_efficiency_to', 'euronorm',
            // Physical Details
            'color_id', 'doors', 'seats_min', 'seats_max', 'weight_from', 'weight_to',
            'wheels', 'axles', 'engine_cylinders', 'engine_displacement_from', 
            'engine_displacement_to', 'airbags', 'ncap_five',
            // Equipment
            'equipment_ids', 'equipment_id'
        ];
        
        // Check if any advanced filters are present
        $hasAdvancedFilters = $request->hasAny($advancedFilterKeys);
        
        // Basic filter keys (vehicles table attributes)
        $basicFilterKeys = [
            'search', 'category_id', 'brand_id', 'model_id', 'model_year_id', 
            'fuel_type_id', 'km_driven', 'price_from', 'price_to', 'listing_type_id', 'sort'
        ];
        
        if ($hasAdvancedFilters) {
            // Use advanced filtering method
            $basicFilters = $request->only($basicFilterKeys);
            $advancedFilters = $request->only($advancedFilterKeys);
            
            $vehicles = $this->vehicleService->getPublicVehiclesWithAdvancedFilters(
                $basicFilters,
                $advancedFilters,
                $request->input('limit', 15),
                $request->input('page', 1)
            );
        } else {
            // Use basic filtering method (faster, most common)
            $filters = $request->only($basicFilterKeys);
            
            $vehicles = $this->vehicleService->getPublicVehicles(
                $filters,
                $request->input('limit', 15),
                $request->input('page', 1)
            );
        }

        // Format vehicles for JSON response
        $formattedVehicles = collect($vehicles->items())->map(function ($vehicle) {
            // Get first image
            $firstImage = $vehicle->images->first();
            $imageUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
            
            // Get details
            $details = $vehicle->details;
            
            // Determine seller type (dealer or private)
            $isDealer = $vehicle->dealer && !str_starts_with($vehicle->dealer->cvr ?? '', 'INDIVIDUAL-');
            $sellerType = $isDealer ? 'Dealer' : 'Private';
            
            return [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'registration' => $vehicle->registration,
                'vin' => $vehicle->vin,
                'price' => $vehicle->price,
                'mileage' => $vehicle->mileage,
                'km_driven' => $vehicle->km_driven,
                'first_registration_date' => $vehicle->first_registration_date?->format('Y-m-d'),
                'version' => $vehicle->version,
                'brand_name' => $vehicle->brand_name,
                'model_name' => $vehicle->model_name,
                'category_name' => $vehicle->category_name,
                'fuel_type_name' => $vehicle->fuel_type_name,
                'gear_type_name' => $vehicle->gear_type_name,
                'model_year_name' => $vehicle->model_year_name,
                'vehicle_list_status_name' => $vehicle->vehicle_list_status_name,
                'engine_power_hp' => $vehicle->engine_power_hp,
                'seller_type' => $sellerType,
                'image_url' => $imageUrl,
                'thumbnail_url' => $firstImage?->thumbnail_url ?? null,
                'details' => $details ? [
                    'color_name' => $details->color_name ?? null,
                    'condition_name' => $details->condition_name ?? null,
                    'fuel_efficiency' => $vehicle->fuel_efficiency ?? null,
                ] : null,
            ];
        });

        // Create new paginator with formatted vehicles
        $formattedPaginator = new LengthAwarePaginator(
            $formattedVehicles,
            $vehicles->total(),
            $vehicles->perPage(),
            $vehicles->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->paginated($formattedPaginator);
    }

    /**
     * Get dealer vehicles list
     */
    public function dealerIndex(Request $request): JsonResponse
    {
        $dealer = $request->user()?->dealers()->first();
        if (!$dealer) {
            $emptyPaginator = new LengthAwarePaginator([], 0, $request->input('limit', 15), $request->input('page', 1));
            return $this->paginated($emptyPaginator);
        }

        $filters = $request->only([
            'search',
            'vehicle_list_status_id',
            'sort',
        ]);

        // Handle status parameter (convert status name to ID)
        if ($request->has('status') && $request->input('status')) {
            $statusId = VehicleListStatus::nameToId($request->input('status'));
            if ($statusId) {
                $filters['vehicle_list_status_id'] = $statusId;
            }
        }

        $vehicles = $this->vehicleService->getDealerVehicles(
            $dealer->id,
            $filters,
            $request->input('limit', 15),
            $request->input('page', 1)
        );

        return $this->paginated($vehicles);
    }

    /**
     * Get vehicle details
     */
    public function show(int $id): JsonResponse
    {
        $vehicle = Vehicle::with([
            'dealer',
            'user',
            'images',
            'details',
            'equipment'
        ])->findOrFail($id);

        return $this->success($vehicle);
    }

    /**
     * Get vehicles list (legacy method for backward compatibility)
     */
    public function getVehicles(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Get vehicles overview statistics
     */
    public function getVehiclesOverview(): JsonResponse
    {
        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::where('status', 'Available')->count();
        $pendingVehicles = Vehicle::whereIn('status', ['Pending Sale', 'Pending Purchase'])->count();
        $totalInventoryValue = Vehicle::where('status', 'Available')->sum('listing_price');
        $averageVehicleValue = $availableVehicles > 0 ? $totalInventoryValue / $availableVehicles : 0;
        
        // Calculate average days in inventory
        $averageDaysInInventory = Vehicle::where('status', 'Available')
            ->selectRaw('AVG(DATEDIFF(NOW(), inventory_date)) as avg_days')
            ->value('avg_days') ?? 0;

        $vehiclesOver90Days = Vehicle::where('status', 'Available')
            ->whereRaw('DATEDIFF(NOW(), inventory_date) > 90')
            ->count();

        $vehiclesNeedingWork = Vehicle::where('status', 'Available')
            ->whereJsonLength('pending_works', '>', 0)
            ->count();

        $newArrivals7Days = Vehicle::where('created_at', '>=', now()->subDays(7))->count();
        $recentlyUpdated24h = Vehicle::where('updated_at', '>=', now()->subDay())->count();

        return $this->success([
            'totalVehicles' => $totalVehicles,
            'availableVehicles' => $availableVehicles,
            'pendingVehicles' => $pendingVehicles,
            'totalInventoryValue' => $totalInventoryValue,
            'averageVehicleValue' => round($averageVehicleValue, 2),
            'averageDaysInInventory' => round($averageDaysInInventory, 2),
            'vehiclesOver90Days' => $vehiclesOver90Days,
            'vehiclesNeedingWork' => $vehiclesNeedingWork,
            'newArrivals7Days' => $newArrivals7Days,
            'recentlyUpdated24h' => $recentlyUpdated24h,
        ]);
    }

    /**
     * Get vehicle by serial number
     */
    public function getVehicleBySerial(int $serialNo): JsonResponse
    {
        $vehicle = Vehicle::where('serial_no', $serialNo)
            ->withCount(['purchases', 'sales'])
            ->firstOrFail();

        return $this->success($vehicle);
    }

    /**
     * Create vehicle
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        // Set dealer_id from authenticated user
        if ($request->user() && $request->user()->dealers()->exists()) {
            $data['dealer_id'] = $request->user()->dealers()->first()->id;
        }

        // Set user_id (creator)
        $data['user_id'] = $request->user()->id;

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $vehicle = $this->vehicleService->createVehicle($data);

        return $this->created($vehicle->load(['dealer', 'images', 'details']));
    }

    /**
     * Create vehicle draft (no validation)
     * Allows saving incomplete vehicle data without validation
     */
    public function storeDraft(Request $request): JsonResponse
    {
        $data = $request->all();

        // Set dealer_id from authenticated user
        if ($request->user() && $request->user()->dealers()->exists()) {
            $data['dealer_id'] = $request->user()->dealers()->first()->id;
        }

        // Set user_id (creator)
        $data['user_id'] = $request->user()->id;

        // Automatically set status to Draft (ID: 1)
        $data['vehicle_list_status_id'] = 1;

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        // No validation - allow partial/incomplete data
        $vehicle = $this->vehicleService->createVehicle($data);

        return $this->created($vehicle->load(['dealer', 'images', 'details']), 'Vehicle draft saved successfully');
    }

    /**
     * Update vehicle
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $data = $request->all();

        // Handle file uploads
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $vehicle = $this->vehicleService->updateVehicle($vehicle, $data);

        return $this->success($vehicle->load(['dealer', 'images', 'details']));
    }

    /**
     * Delete vehicle (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->vehicleService->deleteVehicle($vehicle);

        return $this->noContent();
    }

    /**
     * Update vehicle status (single endpoint replaces publish/unpublish)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', 'in:published,unpublished,archived,draft,sold'],
            'vehicle_list_status_id' => ['sometimes', 'integer', 'exists:vehicle_list_statuses,id'],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // If vehicle_list_status_id is provided, use it directly
        if ($request->has('vehicle_list_status_id')) {
            $statusId = $request->vehicle_list_status_id;
        } elseif ($request->has('status')) {
            // Otherwise, convert status name to ID
            $statusId = \App\Constants\VehicleListStatus::nameToId($request->status);
            
            if (!$statusId) {
                return $this->validationError(['status' => ['Invalid status value']]);
            }
        } else {
            return $this->validationError(['status' => ['Either status or vehicle_list_status_id is required']]);
        }

        $vehicle->vehicle_list_status_id = $statusId;
        
        if ($request->input('status') === 'published' && !$vehicle->published_at) {
            $vehicle->published_at = now();
        }

        $vehicle->save();

        return $this->success($vehicle);
    }

    /**
     * Update vehicle price (creates price_history entry)
     */
    public function updatePrice(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'price' => 'required|integer|min:0',
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $oldPrice = $vehicle->price;

        $vehicle->price = $request->price;
        $vehicle->save();

        // Create price history entry
        \App\Models\PriceHistory::create([
            'vehicle_id' => $vehicle->id,
            'old_price' => $oldPrice,
            'new_price' => $request->price,
            'changed_by_user_id' => $request->user()->id,
        ]);

        return $this->success($vehicle);
    }

    /**
     * Fetch vehicle data from Nummerplade API (for preview before creating listing)
     */
    public function fetchFromNummerplade(Request $request): JsonResponse
    {
        $request->validate([
            'registration' => 'required_without:vin|string|max:20',
            'vin' => 'required_without:registration|string|max:17',
        ]);

        try {
            $data = $this->vehicleService->fetchVehicleDataFromNummerplade(
                $request->input('registration'),
                $request->input('vin')
            );

            return $this->success($data);
        } catch (\App\Exceptions\NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Upload vehicle images
     */
    public function uploadImages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // Get current sort order (highest existing sort_order + 1)
        $currentMaxSortOrder = $vehicle->images()->max('sort_order') ?? -1;
        $sortOrder = $currentMaxSortOrder + 1;
        
        $uploadedImages = [];
        
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            // Handle both array format (images[]) and single file
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $this->fileService->validateFile($file);
                    
                    // Upload file (without thumbnail first to ensure file is saved)
                    $uploadedUrl = $this->fileService->uploadFiles(
                        [$file],
                        'public',
                        'vehicles',
                        false, // Don't create thumbnails here - we'll do it explicitly below
                        false, // optimizeImages
                        300, // thumbnailWidth
                        300  // thumbnailHeight
                    )[0];
                    
                    $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH));
                    
                    // Explicitly create thumbnail for each image to ensure it's created
                    $thumbnailPath = null;
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                    } catch (\Exception $e) {
                        // Log the error but continue - image will be saved without thumbnail
                        Log::warning('Failed to create thumbnail for vehicle image', [
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    $vehicleImage = VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                    
                    $uploadedImages[] = $vehicleImage;
                }
            }
        }

        return $this->success($vehicle->load('images'));
    }

    /**
     * Delete vehicle image
     */
    public function deleteImage(int $id, int $imageId): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $image = VehicleImage::where('id', $imageId)
            ->where('vehicle_id', $vehicle->id)
            ->firstOrFail();

        // Delete image file
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        // Delete thumbnail file
        if ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path)) {
            Storage::disk('public')->delete($image->thumbnail_path);
        }

        $image->delete();

        return $this->success(['message' => 'Image deleted successfully']);
    }

    /**
     * Update vehicle equipment
     */
    public function updateEquipment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'equipment_ids' => 'required|array',
            'equipment_ids.*' => 'integer|exists:equipments,id',
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // Sync equipment associations
        $vehicle->equipment()->sync($request->equipment_ids);

        return $this->success($vehicle->load(['equipment', 'equipment.equipmentType']));
    }

    /**
     * Sell Your Car API endpoint
     * Allows authenticated users (sellers) to create a vehicle listing
     * Automatically creates dealer if user doesn't have one
     * Supports auto-creation of brands, models, and model years
     */
    public function sellYourCar(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Unauthorized', [], 401);
        }

        // Log all request data for debugging
        $allRequestData = $request->all();
        
        // Separate files from other data for cleaner logging
        $requestDataWithoutFiles = $allRequestData;
        $fileInfo = [];
        
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileInfo = [
                'has_images' => true,
                'count' => is_array($images) ? count($images) : 1,
                'file_names' => is_array($images) 
                    ? array_map(fn($img) => $img->getClientOriginalName() . ' (' . $img->getSize() . ' bytes)', $images)
                    : [$images->getClientOriginalName() . ' (' . $images->getSize() . ' bytes)']
            ];
            // Remove files from the main log data
            unset($requestDataWithoutFiles['images']);
        } else {
            $fileInfo = ['has_images' => false];
        }
        
        Log::info('VehicleController::sellYourCar - Request received', [
            'user_id' => $user->id,
            'request_data' => $requestDataWithoutFiles,
            'files' => $fileInfo,
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
        ]);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'registration' => 'required|string|max:20',
            'vin' => 'nullable|string|max:17',
            'price' => 'required|integer|min:0',
            'listing_type_id' => 'nullable|exists:listing_types,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'model_id' => 'nullable|exists:models,id',
            'model_year_id' => 'nullable|exists:model_years,id',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'km_driven' => 'required|integer|min:0',
            'battery_capacity' => 'nullable|integer|min:0',
            'range_km' => 'nullable|integer|min:0',
            'charging_type' => 'nullable|string|max:100',
            'engine_power' => 'nullable|integer|min:0',
            'towing_weight' => 'nullable|integer|min:0',
            'ownership_tax' => 'nullable|integer|min:0',
            'first_registration_date' => 'nullable|date',
            'first_registration_month' => 'nullable|integer|min:1|max:12',
            'first_registration_year' => 'nullable|integer|min:1900|max:2100',
            'last_inspection_month' => 'nullable|integer|min:1|max:12',
            'last_inspection_year' => 'nullable|integer|min:1900|max:2100',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipments,id',
            'variant_id' => 'nullable|exists:variants,id',
            'variant_name' => 'nullable|string|max:100',
            'euronom_id' => 'nullable|exists:euronorms,id',
            'euronom_name' => 'nullable|string|max:100',
            'servicebog' => 'nullable|in:Yes,No,Default',
            'without_tax' => 'nullable|boolean',
            'seller_phone' => 'required|string|max:30',
            'seller_address' => 'required|string',
            'seller_postcode' => 'required|string|max:10',
            'images' => 'nullable',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif', // max 20MB per file
            // Additional API fields validation
            'vin_location' => 'nullable|string|max:255',
            'vehicle_external_id' => 'nullable|string|max:255',
            'type_id' => 'nullable|exists:types,id',
            'type_name' => 'nullable|string|max:255',
            'registration_status' => 'nullable|string|max:100',
            'registration_status_updated_date' => 'nullable|date',
            'expire_date' => 'nullable|date',
            'status_updated_date' => 'nullable|date',
            'total_weight' => 'nullable|integer|min:0',
            'vehicle_weight' => 'nullable|integer|min:0',
            'coupling' => 'nullable|boolean',
            'towing_weight_brakes' => 'nullable|integer|min:0',
            'minimum_weight' => 'nullable|integer|min:0',
            'gross_combination_weight' => 'nullable|integer|min:0',
            'engine_displacement' => 'nullable|integer|min:0',
            'engine_cylinders' => 'nullable|integer|min:0',
            'engine_code' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'last_inspection_result' => 'nullable|string|max:100',
            'last_inspection_odometer' => 'nullable|integer|min:0',
            'type_approval_code' => 'nullable|string|max:100',
            'top_speed' => 'nullable|integer|min:0',
            'doors' => 'nullable|integer|min:0',
            'minimum_seats' => 'nullable|integer|min:0',
            'maximum_seats' => 'nullable|integer|min:0',
            'wheels' => 'nullable|string|max:65535',
            'extra_equipment' => 'nullable|string',
            'axles' => 'nullable|integer|min:0',
            'drive_axles' => 'nullable|integer|min:0',
            'wheelbase' => 'nullable|integer|min:0',
            'leasing_period_start' => 'nullable|date',
            'leasing_period_end' => 'nullable|date',
            'use_id' => 'nullable|exists:uses,id',
            'body_type_id' => 'nullable|exists:body_types,id',
            'dispensations' => 'nullable|string',
            'permits' => 'nullable|string',
            'ncap_five' => 'nullable|boolean',
            'airbags' => 'nullable|integer|min:0',
            'integrated_child_seats' => 'nullable|integer|min:0',
            'seat_belt_alarms' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            // Handle variant lookup/insertion
            $variantId = null;
            if ($request->has('variant_id') && $request->input('variant_id')) {
                $variantId = $request->input('variant_id');
            } elseif ($request->has('variant_name') && $request->input('variant_name')) {
                $variant = Variant::firstOrCreate(['name' => $request->input('variant_name')]);
                $variantId = $variant->id;
            }

            // Handle euronom lookup/insertion
            $euronomId = null;
            if ($request->has('euronom_id') && $request->input('euronom_id')) {
                $euronomId = $request->input('euronom_id');
            } elseif ($request->has('euronom_name') && $request->input('euronom_name')) {
                $euronom = Euronom::firstOrCreate(['name' => $request->input('euronom_name')]);
                $euronomId = $euronom->id;
            }

            // Handle type lookup/insertion
            $typeId = null;
            if ($request->has('type_id') && $request->input('type_id')) {
                $typeId = $request->input('type_id');
            } elseif ($request->has('type_name') && $request->input('type_name')) {
                $type = Type::firstOrCreate(['name' => $request->input('type_name')]);
                $typeId = $type->id;
            } elseif ($request->has('type') && is_array($request->input('type')) && isset($request->input('type')['name'])) {
                $type = Type::firstOrCreate(['name' => $request->input('type')['name']]);
                $typeId = $type->id;
            }

            // Handle use lookup/insertion
            $useId = null;
            if ($request->has('use_id') && $request->input('use_id')) {
                $useId = $request->input('use_id');
            } elseif ($request->has('use') && is_array($request->input('use')) && isset($request->input('use')['name'])) {
                $use = VehicleUse::firstOrCreate(['name' => $request->input('use')['name']]);
                $useId = $use->id;
            }

            // Handle body_type lookup/insertion
            $bodyTypeId = null;
            if ($request->has('body_type_id') && $request->input('body_type_id')) {
                $bodyTypeId = $request->input('body_type_id');
            } elseif ($request->has('body_type') && is_array($request->input('body_type')) && isset($request->input('body_type')['name'])) {
                $bodyType = BodyType::firstOrCreate(['name' => $request->input('body_type')['name']]);
                $bodyTypeId = $bodyType->id;
            }

            // Handle month/year to date conversion for first_registration_date
            if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
                $month = $request->input('first_registration_month');
                $year = $request->input('first_registration_year');
                $firstRegistrationDate = sprintf('%04d-%02d-01', $year, $month);
            } elseif ($request->has('first_registration_date')) {
                $firstRegistrationDate = $request->input('first_registration_date');
            } else {
                $firstRegistrationDate = null;
            }

            // Handle month/year to date conversion for last_inspection_date
            $lastInspectionDate = null;
            if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
                $month = $request->input('last_inspection_month');
                $year = $request->input('last_inspection_year');
                $lastInspectionDate = sprintf('%04d-%02d-01', $year, $month);
            } elseif ($request->has('last_inspection_date')) {
                $lastInspectionDate = $request->input('last_inspection_date');
            }

            // Auto-generate title if not provided
            $title = $request->input('title');
            if (empty($title) && $request->has('brand_id') && $request->has('model_id') && 
                $request->has('model_year_id') && $request->has('fuel_type_id')) {
                $title = $this->generateTitle(
                    $request->input('brand_id'),
                    $request->input('model_id'),
                    $request->input('model_year_id'),
                    $request->input('fuel_type_id')
                );
            }

            // Prepare vehicle data
            $vehicleData = $request->only([
                'registration', 'vin', 'price',
                'listing_type_id', 'category_id', 'brand_id', 'model_id',
                'model_year_id', 'fuel_type_id', 'km_driven',
                'battery_capacity', 'range_km', 'charging_type', 'engine_power', 'towing_weight',
                'ownership_tax', 'fuel_efficiency'
            ]);
            
            // Set vehicle_list_status_id automatically to 2 (ignore any value from frontend)
            $vehicleData['vehicle_list_status_id'] = 2;
            
            // Set published_at automatically (ignore any value from frontend)
            $vehicleData['published_at'] = now();
            
            // Add title and first_registration_date
            if ($title) {
                $vehicleData['title'] = $title;
            }
            if ($firstRegistrationDate) {
                $vehicleData['first_registration_date'] = $firstRegistrationDate;
            }

            // Add brand_name, model_name, and model_year_name if provided (for auto-creation)
            if ($request->has('brand_name')) {
                $vehicleData['brand_name'] = $request->input('brand_name');
            }
            if ($request->has('model_name')) {
                $vehicleData['model_name'] = $request->input('model_name');
            }
            if ($request->has('model_year_name')) {
                $vehicleData['model_year_name'] = $request->input('model_year_name');
            }
            if ($request->has('model_year')) {
                $vehicleData['model_year'] = $request->input('model_year');
            }

            // Set user_id
            $vehicleData['user_id'] = $user->id;

            // Get or create dealer for the user
            // $dealer = $user->dealers()->first();
            
            // if (!$dealer) {
            //     $dealer = DB::transaction(function () use ($user) {
            //         // Create a default dealer for individual sellers
            //         $dealer = Dealer::create([
            //             'cvr' => 'INDIVIDUAL-' . $user->id . '-' . time(),
            //             'address' => $user->address ?? '',
            //             'city' => $user->city ?? '',
            //             'postcode' => $user->postcode ?? '',
            //             'country_code' => 'DK',
            //         ]);

            //         // Associate user with dealer
            //         DealerUser::create([
            //             'dealer_id' => $dealer->id,
            //             'user_id' => $user->id,
            //             'role_id' => 1, // ROLE_OWNER
            //             'created_at' => now(),
            //         ]);

            //         return $dealer;
            //     });
            // }
            
            // $vehicleData['dealer_id'] = $dealer->id;

            // Set default listing_type_id to "Purchase" if not provided
            if (!isset($vehicleData['listing_type_id']) || empty($vehicleData['listing_type_id'])) {
                $purchaseType = ListingType::where('name', 'Purchase')->first();
                if ($purchaseType) {
                    $vehicleData['listing_type_id'] = $purchaseType->id;
                }
            }

            // Add equipment IDs if provided
            if ($request->has('equipment_ids')) {
                $vehicleData['equipment_ids'] = $request->input('equipment_ids');
            }

            // Handle price type
            $priceTypeId = null;
            if ($request->has('without_tax') && $request->boolean('without_tax')) {
                $withoutTaxPriceType = PriceType::firstOrCreate(['name' => 'Without tax']);
                $priceTypeId = $withoutTaxPriceType->id;
            } elseif ($request->has('price_type_id')) {
                $priceTypeId = $request->input('price_type_id');
            }

            // Auto-generate description
            $description = $request->input('description');
            if (empty($description)) {
                $description = $this->generateDescription($request, $variantId, $euronomId);
            }

            // Add vehicle details if provided
            $detailsFields = [
                'vin_location', 'vehicle_external_id', 'type_name',
                'registration_status', 'registration_status_updated_date', 'expire_date',
                'status_updated_date', 'total_weight', 'vehicle_weight',
                'technical_total_weight', 'towing_weight_brakes', 'minimum_weight',
                'gross_combination_weight', 'engine_displacement',
                'engine_cylinders', 'engine_code', 'category',
                'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
                'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
                'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
                'leasing_period_end', 'color_id', 'dispensations',
                'permits', 'airbags', 'integrated_child_seats',
                'seat_belt_alarms', 'condition_id', 'sales_type_id', 'servicebog',
                'seller_phone', 'seller_address', 'seller_postcode', 'annual_tax', 'owners'
            ];

            $vehicleDetailsData = [];
            foreach ($detailsFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    // Handle JSON strings for arrays
                    if (($field === 'dispensations' || $field === 'permits') && is_string($value)) {
                        // Already a JSON string from frontend
                        $vehicleDetailsData[$field] = $value;
                    } else {
                        $vehicleDetailsData[$field] = $value;
                    }
                }
            }

            // Handle special fields
            // Map vehicle_id from API to vehicle_external_id
            if ($request->has('vehicle_id')) {
                $vehicleDetailsData['vehicle_external_id'] = $request->input('vehicle_id');
            }

            // Handle boolean fields - convert to integer
            if ($request->has('coupling')) {
                $vehicleDetailsData['coupling'] = $request->boolean('coupling') ? 1 : 0;
            }
            if ($request->has('ncap_five')) {
                $vehicleDetailsData['ncap_five'] = $request->boolean('ncap_five') ? 1 : 0;
            }

            // Handle arrays - convert to JSON strings
            if ($request->has('dispensations') && is_array($request->input('dispensations'))) {
                $vehicleDetailsData['dispensations'] = json_encode($request->input('dispensations'));
            }
            if ($request->has('permits') && is_array($request->input('permits'))) {
                $vehicleDetailsData['permits'] = json_encode($request->input('permits'));
            }

            // Add lookup IDs
            if ($variantId) {
                $vehicleDetailsData['variant_id'] = $variantId;
            }
            if ($euronomId) {
                $vehicleDetailsData['euronom_id'] = $euronomId;
            }
            if ($typeId) {
                $vehicleDetailsData['type_id'] = $typeId;
            }
            if ($useId) {
                $vehicleDetailsData['use_id'] = $useId;
            }
            if ($bodyTypeId) {
                $vehicleDetailsData['body_type_id'] = $bodyTypeId;
            }
            
            // Add type_name if provided separately
            if ($request->has('type_name')) {
                $vehicleDetailsData['type_name'] = $request->input('type_name');
            } elseif ($typeId) {
                // Get type name from database if we have the ID
                $type = Type::find($typeId);
                if ($type) {
                    $vehicleDetailsData['type_name'] = $type->name;
                }
            }

            if ($lastInspectionDate) {
                $vehicleDetailsData['last_inspection_date'] = $lastInspectionDate;
            }
            if ($description) {
                $vehicleDetailsData['description'] = $description;
            }
            if ($priceTypeId) {
                $vehicleDetailsData['price_type_id'] = $priceTypeId;
            }

            // Add vehicle details to vehicleData for VehicleService
            foreach ($vehicleDetailsData as $key => $value) {
                $vehicleData[$key] = $value;
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $vehicleData['images'] = $images;
                
                // Log for debugging (can be removed in production)
                Log::info('Images received in VehicleController::sellYourCar', [
                    'count' => is_array($images) ? count($images) : 1,
                    'file_names' => is_array($images) 
                        ? array_map(fn($img) => $img->getClientOriginalName(), $images)
                        : [$images->getClientOriginalName()]
                ]);
            } else {
                Log::info('No images found in request');
            }

            // Create vehicle
            $vehicle = $this->vehicleService->createVehicle($vehicleData);

            // Log audit trail
            try {
                $this->auditLogService->logCreate(
                    $user,
                    'Vehicle',
                    $vehicle->id,
                    $vehicle->toArray(),
                    $request,
                    null,
                    null,
                    'Vehicle listing created via Sell Your Car API',
                    ['vehicle', 'listing', 'sell-your-car']
                );
            } catch (\Exception $e) {
                // Log error but don't fail the main operation
                Log::warning('Failed to create audit log for vehicle creation', [
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->created($vehicle->load(['dealer', 'images', 'details', 'equipment']));
        } catch (\Exception $e) {
            Log::error('VehicleController::sellYourCar - Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error(
                'Failed to create vehicle listing: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Generate title from brand, model, model year, and fuel type
     */
    private function generateTitle(?int $brandId, ?int $modelId, ?int $modelYearId, ?int $fuelTypeId): ?string
    {
        $parts = [];
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            if ($brand) {
                $parts[] = $brand->name;
            }
        }
        
        if ($modelId) {
            $model = VehicleModel::find($modelId);
            if ($model) {
                $parts[] = $model->name;
            }
        }
        
        if ($modelYearId) {
            $modelYear = ModelYear::find($modelYearId);
            if ($modelYear) {
                $parts[] = $modelYear->name;
            }
        }
        
        if ($fuelTypeId) {
            $fuelType = FuelType::find($fuelTypeId);
            if ($fuelType) {
                $parts[] = $fuelType->name;
            }
        }
        
        return !empty($parts) ? implode(' ', $parts) : null;
    }

    /**
     * Generate description from various fields
     */
    private function generateDescription(Request $request, ?int $variantId, ?int $euronomId): string
    {
        $descriptionParts = [];
        
        // Equipment
        if ($request->has('equipment_ids') && is_array($request->input('equipment_ids'))) {
            $equipmentIds = $request->input('equipment_ids');
            if (!empty($equipmentIds)) {
                $equipments = Equipment::whereIn('id', $equipmentIds)->pluck('name')->toArray();
                if (!empty($equipments)) {
                    $descriptionParts[] = 'Equipment: ' . implode(', ', $equipments);
                }
            }
        }
        
        // Servicebog
        if ($request->has('servicebog') && $request->input('servicebog')) {
            $servicebog = $request->input('servicebog');
            if ($servicebog !== 'Default') {
                $descriptionParts[] = 'Service book: ' . $servicebog;
            }
        }
        
        // Kilometer Driven
        if ($request->has('km_driven') && $request->input('km_driven')) {
            $descriptionParts[] = 'Kilometers driven: ' . number_format($request->input('km_driven'), 0, ',', '.') . ' km';
        }
        
        // First Registration
        if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
            $month = $request->input('first_registration_month');
            $year = $request->input('first_registration_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'First registration: ' . $monthName . ' ' . $year;
        } elseif ($request->has('first_registration_date')) {
            $date = \Carbon\Carbon::parse($request->input('first_registration_date'));
            $descriptionParts[] = 'First registration: ' . $date->format('F Y');
        }
        
        // Last Inspection
        if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
            $month = $request->input('last_inspection_month');
            $year = $request->input('last_inspection_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'Last inspection: ' . $monthName . ' ' . $year;
        } elseif ($request->has('last_inspection_date')) {
            $date = \Carbon\Carbon::parse($request->input('last_inspection_date'));
            $descriptionParts[] = 'Last inspection: ' . $date->format('F Y');
        }
        
        // KM/L (Fuel Efficiency)
        if ($request->has('fuel_efficiency') && $request->input('fuel_efficiency')) {
            $descriptionParts[] = 'Fuel efficiency: ' . number_format($request->input('fuel_efficiency'), 2) . ' km/l';
        }
        
        // Euronom
        if ($euronomId) {
            $euronom = Euronom::find($euronomId);
            if ($euronom) {
                $descriptionParts[] = 'Euro norm: ' . $euronom->name;
            }
        }
        
        // Total Technical Weight
        if ($request->has('technical_total_weight') && $request->input('technical_total_weight')) {
            $descriptionParts[] = 'Total technical weight: ' . number_format($request->input('technical_total_weight'), 0, ',', '.') . ' kg';
        }
        
        return implode('. ', $descriptionParts) . '.';
    }
}

