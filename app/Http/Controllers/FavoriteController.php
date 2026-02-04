<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Services\AuthService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Favorite Controller for Dealer and Web
 */
class FavoriteController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private AuditLogService $auditLogService
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

        // Format vehicles for JSON response (same format as featured vehicles API)
        $formattedVehicles = collect($favorites->items())->map(function ($favorite) {
            $vehicle = $favorite->vehicle;
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

        // Create new paginator with formatted vehicles
        $formattedPaginator = new LengthAwarePaginator(
            $formattedVehicles,
            $favorites->total(),
            $favorites->perPage(),
            $favorites->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->paginated($formattedPaginator);
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

        // Log audit trail (only if newly created)
        if ($favorite->wasRecentlyCreated) {
            try {
                $this->auditLogService->logCreate(
                    $request->user(),
                    'Favorite',
                    $favorite->id,
                    $favorite->toArray(),
                    $request,
                    'Vehicle',
                    $request->vehicle_id,
                    'Vehicle added to favorites',
                    ['favorite', 'vehicle']
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to create audit log for favorite creation', [
                    'favorite_id' => $favorite->id,
                    'user_id' => $request->user()->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->created($favorite);
    }

    public function destroy(int $vehicleId, Request $request): JsonResponse
    {
        // Get favorite before deletion for audit log
        $favorite = Favorite::where('user_id', $request->user()->id)
            ->where('vehicle_id', $vehicleId)
            ->first();

        if ($favorite) {
            // Log audit trail before deletion
            try {
                $this->auditLogService->logDelete(
                    $request->user(),
                    'Favorite',
                    $favorite->id,
                    $favorite->toArray(),
                    $request,
                    'Vehicle',
                    $vehicleId,
                    'Vehicle removed from favorites',
                    ['favorite', 'vehicle']
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to create audit log for favorite deletion', [
                    'favorite_id' => $favorite->id,
                    'user_id' => $request->user()->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $favorite->delete();
        }

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

        // Log audit trail (only if newly created)
        if ($favorite->wasRecentlyCreated) {
            try {
                $this->auditLogService->logCreate(
                    $user,
                    'Favorite',
                    $favorite->id,
                    $favorite->toArray(),
                    $request,
                    'Vehicle',
                    $request->vehicle_id,
                    'Vehicle added to favorites via web',
                    ['favorite', 'vehicle', 'web']
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to create audit log for favorite creation', [
                    'favorite_id' => $favorite->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

        // Get favorite before deletion for audit log
        $favorite = Favorite::where('user_id', $user->id)
            ->where('vehicle_id', $vehicleId)
            ->first();

        if ($favorite) {
            // Log audit trail before deletion
            try {
                $this->auditLogService->logDelete(
                    $user,
                    'Favorite',
                    $favorite->id,
                    $favorite->toArray(),
                    $request,
                    'Vehicle',
                    $vehicleId,
                    'Vehicle removed from favorites via web',
                    ['favorite', 'vehicle', 'web']
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to create audit log for favorite deletion', [
                    'favorite_id' => $favorite->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $favorite->delete();
        }

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

