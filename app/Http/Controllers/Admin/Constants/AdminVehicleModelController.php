<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrModel;
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
        $query = DmrModel::query()
            ->with('brand')
            ->orderBy('name');

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }

        return $this->paginated($query->paginate($request->get('limit', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $vehicleModel = DmrModel::with('brand')->findOrFail($id);
        return $this->success($vehicleModel);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand_id' => 'required|integer|exists:brands,id',
        ]);

        $vehicleModel = DmrModel::create($request->only(['name', 'brand_id']));

        LookupService::forgetLookupCacheGroup('dmr_models');
        LookupService::forgetLookupCacheGroup('brands');

        return $this->created($vehicleModel);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicleModel = DmrModel::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'brand_id' => 'sometimes|integer|exists:brands,id',
        ]);

        $vehicleModel->update($request->only(['name', 'brand_id']));

        LookupService::forgetLookupCacheGroup('dmr_models');
        LookupService::forgetLookupCacheGroup('brands');

        return $this->success($vehicleModel);
    }

    public function delete(int $id): JsonResponse
    {
        $vehicleModel = DmrModel::findOrFail($id);
        $vehicleModel->delete();

        LookupService::forgetLookupCacheGroup('dmr_models');
        LookupService::forgetLookupCacheGroup('brands');

        return $this->noContent();
    }
}
