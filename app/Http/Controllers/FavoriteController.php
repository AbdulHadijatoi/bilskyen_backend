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
            ->with('vehicle')
            ->paginate($request->get('limit', 15));

        return $this->paginated($favorites);
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

