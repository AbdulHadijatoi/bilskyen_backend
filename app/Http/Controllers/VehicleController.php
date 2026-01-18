<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Dealer;
use App\Models\DealerUser;
use App\Models\FeaturedListing;
use App\Services\VehicleService;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\SellYourCarRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService
    ) {}

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
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $data = $request->validated();

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
     * Update vehicle
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $data = $request->validated();

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
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->vehicleService->deleteVehicle($vehicle);

        return $this->noContent();
    }

    /**
     * Update vehicle status (single endpoint replaces publish/unpublish)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:published,unpublished,archived,draft'],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $statusId = \App\Constants\VehicleListStatus::nameToId($request->status);

        if (!$statusId) {
            return $this->validationError(['status' => ['Invalid status value']]);
        }

        $vehicle->vehicle_list_status_id = $statusId;
        
        if ($request->status === 'published' && !$vehicle->published_at) {
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
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $vehicle = Vehicle::findOrFail($id);
        
        // TODO: Implement image upload logic
        // This should use FileService to upload images and associate with vehicle

        return $this->success(['message' => 'Images uploaded successfully']);
    }

    /**
     * Delete vehicle image
     */
    public function deleteImage(int $id, int $imageId): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        
        // TODO: Implement image deletion logic
        // This should remove the image from vehicle_images table and delete file

        return $this->noContent();
    }

    /**
     * Sell Your Car API endpoint
     * Allows authenticated users (sellers) to create a vehicle listing
     * Automatically creates dealer if user doesn't have one
     * Supports auto-creation of brands, models, and model years
     */
    public function sellYourCar(SellYourCarRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Prepare vehicle data
            $vehicleData = $request->only([
                'title', 'registration', 'vin', 'price',
                'listing_type_id', 'category_id', 'brand_id', 'model_id',
                'model_year_id', 'fuel_type_id', 'mileage', 'km_driven',
                'battery_capacity', 'engine_power', 'towing_weight',
                'ownership_tax', 'first_registration_date',
                'vehicle_list_status_id', 'published_at'
            ]);

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
            $dealer = $user->dealers()->first();
            
            if (!$dealer) {
                $dealer = DB::transaction(function () use ($user) {
                    // Create a default dealer for individual sellers
                    $dealer = Dealer::create([
                        'cvr' => 'INDIVIDUAL-' . $user->id . '-' . time(),
                        'address' => $user->address ?? '',
                        'city' => $user->city ?? '',
                        'postcode' => $user->postcode ?? '',
                        'country_code' => 'DK',
                    ]);

                    // Associate user with dealer
                    DealerUser::create([
                        'dealer_id' => $dealer->id,
                        'user_id' => $user->id,
                        'role_id' => 1, // ROLE_OWNER
                        'created_at' => now(),
                    ]);

                    return $dealer;
                });
            }
            
            $vehicleData['dealer_id'] = $dealer->id;

            // Add equipment IDs if provided
            if ($request->has('equipment_ids')) {
                $vehicleData['equipment_ids'] = $request->input('equipment_ids');
            }

            // Add vehicle details if provided
            $detailsFields = [
                'description', 'vin_location', 'type_id', 'type_name',
                'registration_status', 'registration_status_updated_date', 'expire_date',
                'status_updated_date', 'total_weight', 'vehicle_weight',
                'technical_total_weight', 'coupling', 'towing_weight_brakes', 'minimum_weight',
                'gross_combination_weight', 'engine_displacement',
                'engine_cylinders', 'engine_code', 'category', 'last_inspection_date',
                'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
                'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
                'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
                'leasing_period_end', 'use_id', 'color_id', 'body_type_id', 'dispensations',
                'permits', 'ncap_five', 'airbags', 'integrated_child_seats',
                'seat_belt_alarms', 'euronorm', 'price_type_id', 'condition_id',
                'sales_type_id'
            ];

            foreach ($detailsFields as $field) {
                if ($request->has($field)) {
                    $vehicleData[$field] = $request->input($field);
                }
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                $vehicleData['images'] = $request->file('images');
            }

            // Create vehicle using the service
            $vehicle = $this->vehicleService->createVehicle($vehicleData);

            return $this->created($vehicle->load(['dealer', 'images', 'details', 'equipment']));
        } catch (\Exception $e) {
            return $this->error(
                'Failed to create vehicle listing: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}

