<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\DmrBrand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\LookupService;

/**
 * Admin Brand Controller
 */
class AdminBrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $brands = DmrBrand::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($brands);
    }

    public function show(int $id): JsonResponse
    {
        $brand = DmrBrand::findOrFail($id);
        return $this->success($brand);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        $brand = DmrBrand::create($request->only(['name']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('brands');

        return $this->created($brand);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = DmrBrand::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:brands,name,' . $id,
        ]);

        $brand->update($request->only(['name']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('brands');

        return $this->success($brand);
    }

    public function delete(int $id): JsonResponse
    {
        $brand = DmrBrand::findOrFail($id);
        $brand->delete();

        // Clear cache
        LookupService::forgetLookupCacheGroup('brands');

        return $this->noContent();
    }
}
