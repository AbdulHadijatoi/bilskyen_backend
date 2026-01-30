<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\VehicleUse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


/**
 * Admin Vehicle Use Controller
 */
class AdminVehicleUseController extends Controller
{
    use ConstantsCacheHelper;
    public function index(Request $request): JsonResponse
    {
        $vehicleUses = VehicleUse::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($vehicleUses);
    }

    public function show(int $id): JsonResponse
    {
        $vehicleUse = VehicleUse::findOrFail($id);
        return $this->success($vehicleUse);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:uses,name',
        ]);

        $vehicleUse = VehicleUse::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('vehicle_uses');

        return $this->created($vehicleUse);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicleUse = VehicleUse::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:uses,name,' . $id,
        ]);

        $vehicleUse->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('vehicle_uses');

        return $this->success($vehicleUse);
    }

    public function delete(int $id): JsonResponse
    {
        $vehicleUse = VehicleUse::findOrFail($id);
        $vehicleUse->delete();

        // Clear cache
        $this->clearConstantsCache('vehicle_uses');

        return $this->noContent();
    }
}
