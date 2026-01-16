<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Favorite Controller for Dealer and Web
 */
class FavoriteController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with([
                'vehicle.images',
                'vehicle.details',
                'vehicle.dealer',
            ])
            ->paginate($request->get('limit', 15));

        // Format vehicles for JSON response (same format as vehicles API)
        $formattedVehicles = collect($favorites->items())->map(function ($favorite) {
            $vehicle = $favorite->vehicle;
            if (!$vehicle) {
                return null;
            }

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
        })
        ->filter() // Remove null entries
        ->values(); // Re-index array

        return response()->json([
            'vehicles' => $formattedVehicles,
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
                'from' => $favorites->firstItem(),
                'to' => $favorites->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $favorite = Favorite::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'vehicle_id' => $request->vehicle_id,
            ],
            [
                'created_at' => now(),
            ]
        );

        return $this->created($favorite);
    }

    public function destroy(int $vehicleId, Request $request): JsonResponse
    {
        Favorite::where('user_id', $request->user()->id)
            ->where('vehicle_id', $vehicleId)
            ->delete();

        return $this->noContent();
    }
    
    /**
     * Check if vehicle is favorited by user
     */
    public function check(int $vehicleId, Request $request): JsonResponse
    {
        $isFavorited = Favorite::where('user_id', $request->user()->id)
            ->where('vehicle_id', $vehicleId)
            ->exists();

        return $this->success(['is_favorited' => $isFavorited]);
    }
    
    /**
     * Store favorite for web (uses web authentication)
     */
    public function storeWeb(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $favorite = Favorite::firstOrCreate(
            [
                'user_id' => $user->id,
                'vehicle_id' => $request->vehicle_id,
            ],
            [
                'created_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Saved to favorites',
            'data' => $favorite,
        ], 201);
    }
    
    /**
     * Destroy favorite for web (uses web authentication)
     */
    public function destroyWeb(int $vehicleId, Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        Favorite::where('user_id', $user->id)
            ->where('vehicle_id', $vehicleId)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Removed from favorites',
        ], 200);
    }
    
    /**
     * Check if vehicle is favorited by user (web version)
     */
    public function checkWeb(int $vehicleId, Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $isFavorited = Favorite::where('user_id', $user->id)
            ->where('vehicle_id', $vehicleId)
            ->exists();

        return response()->json([
            'status' => 'success',
            'data' => ['is_favorited' => $isFavorited],
        ], 200);
    }
    
    /**
     * Check favorite status for multiple vehicles at once (web version)
     */
    public function checkBatchWeb(Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'vehicle_ids' => 'required|array',
            'vehicle_ids.*' => 'required|integer|exists:vehicles,id',
        ]);

        $vehicleIds = $request->input('vehicle_ids', []);
        
        if (empty($vehicleIds)) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ], 200);
        }

        // Get all favorited vehicle IDs for this user in one query
        $favoritedVehicleIds = Favorite::where('user_id', $user->id)
            ->whereIn('vehicle_id', $vehicleIds)
            ->pluck('vehicle_id')
            ->toArray();

        // Build response with status for each vehicle
        $favoritesMap = [];
        foreach ($vehicleIds as $vehicleId) {
            $favoritesMap[$vehicleId] = in_array($vehicleId, $favoritedVehicleIds);
        }

        return response()->json([
            'status' => 'success',
            'data' => $favoritesMap,
        ], 200);
    }
    
    /**
     * Check favorite status for multiple vehicles at once (API version)
     */
    public function checkBatch(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_ids' => 'required|array',
            'vehicle_ids.*' => 'required|integer|exists:vehicles,id',
        ]);

        $vehicleIds = $request->input('vehicle_ids', []);
        
        if (empty($vehicleIds)) {
            return $this->success([]);
        }

        // Get all favorited vehicle IDs for this user in one query
        $favoritedVehicleIds = Favorite::where('user_id', $request->user()->id)
            ->whereIn('vehicle_id', $vehicleIds)
            ->pluck('vehicle_id')
            ->toArray();

        // Build response with status for each vehicle
        $favoritesMap = [];
        foreach ($vehicleIds as $vehicleId) {
            $favoritesMap[$vehicleId] = in_array($vehicleId, $favoritedVehicleIds);
        }

        return $this->success($favoritesMap);
    }
}

