<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\VehicleListStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


/**
 * Admin Vehicle List Status Controller
 */
class AdminVehicleListStatusController extends Controller
{
    use ConstantsCacheHelper;
    public function index(Request $request): JsonResponse
    {
        $vehicleListStatuses = VehicleListStatus::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($vehicleListStatuses);
    }

    public function show(int $id): JsonResponse
    {
        $vehicleListStatus = VehicleListStatus::findOrFail($id);
        return $this->success($vehicleListStatus);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:vehicle_list_statuses,name',
        ]);

        $vehicleListStatus = VehicleListStatus::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('vehicle_list_statuses');

        return $this->created($vehicleListStatus);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicleListStatus = VehicleListStatus::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:vehicle_list_statuses,name,' . $id,
        ]);

        $vehicleListStatus->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('vehicle_list_statuses');

        return $this->success($vehicleListStatus);
    }

    public function delete(int $id): JsonResponse
    {
        $vehicleListStatus = VehicleListStatus::findOrFail($id);
        $vehicleListStatus->delete();

        // Clear cache
        $this->clearConstantsCache('vehicle_list_statuses');

        return $this->noContent();
    }
}
