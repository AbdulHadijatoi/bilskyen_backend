<?php

namespace App\Http\Controllers;

use App\Models\FeaturedListing;
use App\Models\Vehicle;
use App\Constants\VehicleListStatus;
use App\Services\DealerFeaturedListingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Featured Vehicle Controller
 * Manages featured vehicle listings
 */
class AdminFeaturedVehicleController extends Controller
{
    public function __construct(
        private DealerFeaturedListingService $featuredListingService
    ) {}

    /**
     * Get all featured vehicles with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = FeaturedListing::with([
            'vehicle.images',
            'vehicle.dealer',
            'vehicle.user',
            'vehicle.vehicleListStatus',
            'vehicle.dmrFactVehicle.variant.model.brand',
        ])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc');

        // Apply search filter if provided
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->whereHas('vehicle', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('registration', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Filter by vehicle status
        if ($request->has('status')) {
            $statusId = VehicleListStatus::nameToId($request->status);
            if ($statusId) {
                $query->whereHas('vehicle', function ($q) use ($statusId) {
                    $q->where('list_status_id', $statusId);
                });
            }
        }

        // Paginate
        $perPage = $request->input('limit', 15);
        $featuredVehicles = $query->paginate($perPage);

        return $this->paginated($featuredVehicles);
    }

    /**
     * Add vehicle to featured listings
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => [
                'required',
                'integer',
                'exists:vehicles,id',
                'unique:featured_listings,vehicle_id',
            ],
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        // Get the vehicle to ensure it exists and is published
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($vehicle->dealer_id && ! $this->featuredListingService->canFeatureVehicle($vehicle)) {
            return $this->error(__('messages.api.max_feature_listings_reached'), [], 403);
        }

        // Auto-assign sort_order if not provided (max + 1)
        $sortOrder = $request->input('sort_order');
        if ($sortOrder === null) {
            $maxSortOrder = FeaturedListing::max('sort_order') ?? 0;
            $sortOrder = $maxSortOrder + 1;
        }

        // Create featured listing
        $featuredListing = FeaturedListing::create([
            'vehicle_id' => $request->vehicle_id,
            'sort_order' => $sortOrder,
        ]);

        // Load relationships
        $featuredListing->load([
            'vehicle.images',
            'vehicle.dealer',
            'vehicle.user',
            'vehicle.vehicleListStatus',
            'vehicle.dmrFactVehicle.variant.model.brand',
        ]);

        return $this->created($featuredListing);
    }

    /**
     * Update featured vehicle sort order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $featuredListing = FeaturedListing::findOrFail($id);

        $request->validate([
            'sort_order' => 'required|integer|min:0',
        ]);

        $featuredListing->update([
            'sort_order' => $request->input('sort_order'),
        ]);

        // Load relationships
        $featuredListing->load([
            'vehicle.images',
            'vehicle.dealer',
            'vehicle.user',
            'vehicle.vehicleListStatus',
            'vehicle.dmrFactVehicle.variant.model.brand',
        ]);

        return $this->success($featuredListing);
    }

    /**
     * Remove vehicle from featured listings
     */
    public function delete(int $id): JsonResponse
    {
        $featuredListing = FeaturedListing::findOrFail($id);
        $featuredListing->delete();

        return $this->noContent();
    }
}
