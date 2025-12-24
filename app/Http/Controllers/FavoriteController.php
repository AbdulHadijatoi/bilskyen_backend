<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Favorite Controller for Dealer
 */
class FavoriteController extends Controller
{
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

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'vehicle_id' => $request->vehicle_id,
        ]);

        return $this->created($favorite);
    }

    public function destroy(int $vehicleId, Request $request): JsonResponse
    {
        Favorite::where('user_id', $request->user()->id)
            ->where('vehicle_id', $vehicleId)
            ->delete();

        return $this->noContent();
    }
}

