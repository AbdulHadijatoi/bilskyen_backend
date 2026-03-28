<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\LookupService;

/**
 * Admin Vehicle Model Controller
 */
class AdminVehicleModelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vehicleModels = VehicleModel::with('brand')
            ->orderBy('name')
            ->paginate($request->get('limit', 15));

        return $this->paginated($vehicleModels);
    }

    public function show(int $id): JsonResponse
    {
        $vehicleModel = VehicleModel::with('brand')->findOrFail($id);
        return $this->success($vehicleModel);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|integer|exists:brands,id',
        ]);

        $vehicleModel = VehicleModel::create($request->only(['name', 'brand_id']));

        LookupService::forgetLookupCacheGroup('vehicle_models');
        LookupService::forgetLookupCacheGroup('brands');

        return $this->created($vehicleModel);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicleModel = VehicleModel::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'brand_id' => 'sometimes|integer|exists:brands,id',
        ]);

        $vehicleModel->update($request->only(['name', 'brand_id']));

        LookupService::forgetLookupCacheGroup('vehicle_models');
        LookupService::forgetLookupCacheGroup('brands');

        return $this->success($vehicleModel);
    }

    public function delete(int $id): JsonResponse
    {
        $vehicleModel = VehicleModel::findOrFail($id);
        $vehicleModel->delete();

        LookupService::forgetLookupCacheGroup('vehicle_models');
        LookupService::forgetLookupCacheGroup('brands');

        return $this->noContent();
    }
}
