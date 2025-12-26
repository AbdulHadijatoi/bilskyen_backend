<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Constants\VehicleListStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Admin Vehicle Controller
 * Admin can see listings from any dealer (not restricted to own dealer)
 */
class AdminVehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::with(['dealer', 'user', 'location', 'images', 'details']);

        // Apply filters
        if ($request->has('dealer_id')) {
            $query->where('dealer_id', $request->dealer_id);
        }

        if ($request->has('status')) {
            $statusId = VehicleListStatus::nameToId($request->status);
            if ($statusId) {
                $query->where('vehicle_list_status_id', $statusId);
            }
        }

        $vehicles = $query->paginate($request->get('limit', 15));

        return $this->paginated($vehicles);
    }

    public function show(int $id): JsonResponse
    {
        $vehicle = Vehicle::with([
            'dealer',
            'user',
            'location',
            'images',
            'details',
            'priceHistory'
        ])->findOrFail($id);

        return $this->success($vehicle);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(VehicleListStatus::names())],
        ]);

        $vehicle = Vehicle::findOrFail($id);
        $statusId = VehicleListStatus::nameToId($request->status);

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

    public function destroy(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete(); // Soft delete

        return $this->noContent();
    }

    public function getHistory(int $id): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        
        $history = [
            'price_history' => $vehicle->priceHistory()->orderBy('created_at', 'desc')->get(),
            // Add other history types as needed
        ];

        return $this->success($history);
    }
}

