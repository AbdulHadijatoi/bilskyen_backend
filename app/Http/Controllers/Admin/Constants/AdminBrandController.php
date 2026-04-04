<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrBrand;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dmr_brands', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $brand = DmrBrand::create($request->only(['name']));

        LookupService::forgetBrandAndDependentLookupCaches();

        return $this->created($brand);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = DmrBrand::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('dmr_brands', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $brand->update($request->only(['name']));

        LookupService::forgetBrandAndDependentLookupCaches();

        return $this->success($brand);
    }

    public function delete(int $id): JsonResponse
    {
        $brand = DmrBrand::findOrFail($id);
        $brand->delete();

        LookupService::forgetBrandAndDependentLookupCaches();

        return $this->noContent();
    }
}
