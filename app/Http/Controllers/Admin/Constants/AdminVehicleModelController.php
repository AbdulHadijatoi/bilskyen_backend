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

    /**
     * Paginated models that appear on published listings (optional brand scope), with shortened names for dropdowns.
     * Used by Vehicle Spec Definitions and similar catalog UIs — not the full DMR CRUD list.
     */
    public function indexForListingFilters(Request $request, LookupService $lookupService): JsonResponse
    {
        $query = DmrModel::query()
            ->with('brand')
            ->orderBy('name');

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }

        $brandIds = $request->filled('brand_id') ? [(int) $request->input('brand_id')] : [];
        $publishedModelIds = $lookupService->publishedListingModelIds($brandIds);
        if ($publishedModelIds === []) {
            $query->whereRaw('0 = 1');
        } else {
            $query->whereIn('id', $publishedModelIds);
        }

        $paginator = $query->paginate($request->get('limit', 15));

        $paginator->getCollection()->transform(function (DmrModel $m): DmrModel {
            $m->setAttribute('name', DmrModel::dropdownDisplayName((string) $m->name));

            return $m;
        });

        return $this->paginated($paginator);
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
