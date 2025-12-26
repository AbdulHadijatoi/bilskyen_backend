<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\VehicleService;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    public function __construct(
        private VehicleService $vehicleService
    ) {}

    /**
     * Get featured vehicles
     */
    public function getFeaturedVehicles(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 6);
        $page = $request->input('page', 1);

        $vehicles = Vehicle::orderBy('listing_price', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return $this->paginated($vehicles);
    }

    /**
     * Get vehicles list (public or dealer)
     * Excludes deleted records by default unless with_deleted=true is passed
     */
    public function index(Request $request): JsonResponse
    {
        // Include deleted records only if explicitly requested
        if ($request->boolean('with_deleted')) {
            $query = Vehicle::withTrashed()->with(['dealer', 'location', 'images', 'details', 'equipment']);
        } else {
            $query = Vehicle::with(['dealer', 'location', 'images', 'details', 'equipment']);
        }

        // For dealer routes, filter by dealer_id
        if ($request->user() && $request->user()->dealers()->exists()) {
            $dealerId = $request->user()->dealers()->first()->id;
            $query->where('dealer_id', $dealerId);
        } else {
            // Public routes: only show published vehicles
            $query->where('vehicle_list_status_id', \App\Constants\VehicleListStatus::PUBLISHED);
        }

        // Apply search
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('registration', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if ($request->has('fuel_type_id')) {
            $query->where('fuel_type_id', $request->fuel_type_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('model_year_id')) {
            $query->where('model_year_id', $request->model_year_id);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Paginate
        $perPage = $request->input('limit', 15);
        $vehicles = $query->paginate($perPage);

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
            'location',
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

        return $this->created($vehicle->load(['dealer', 'location', 'images', 'details']));
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

        return $this->success($vehicle->load(['dealer', 'location', 'images', 'details']));
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
}

