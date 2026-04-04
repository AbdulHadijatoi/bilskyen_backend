<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrModel;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('dmr_brands', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $vehicleModel = DmrModel::create($request->only(['name', 'brand_id']));

        LookupService::forgetLookupCacheGroup('vehicle_models');
        LookupService::forgetLookupCacheGroup('brands');
        LookupService::forgetLookupCacheGroup('variants');

        return $this->created($vehicleModel);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicleModel = DmrModel::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'brand_id' => [
                'sometimes',
                'integer',
                Rule::exists('dmr_brands', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $vehicleModel->update($request->only(['name', 'brand_id']));

        LookupService::forgetLookupCacheGroup('vehicle_models');
        LookupService::forgetLookupCacheGroup('brands');
        LookupService::forgetLookupCacheGroup('variants');

        return $this->success($vehicleModel);
    }

    public function delete(int $id): JsonResponse
    {
        $vehicleModel = DmrModel::findOrFail($id);
        $vehicleModel->delete();

        LookupService::forgetLookupCacheGroup('vehicle_models');
        LookupService::forgetLookupCacheGroup('brands');
        LookupService::forgetLookupCacheGroup('variants');

        return $this->noContent();
    }
}
