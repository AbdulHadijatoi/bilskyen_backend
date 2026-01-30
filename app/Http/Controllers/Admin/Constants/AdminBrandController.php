<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Brand Controller
 */
class AdminBrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $brands = Brand::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($brands);
    }

    public function show(int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        return $this->success($brand);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
        ]);

        $brand = Brand::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_brands');

        return $this->created($brand);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:brands,name,' . $id,
        ]);

        $brand->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_brands');

        return $this->success($brand);
    }

    public function delete(int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();

        // Clear cache
        Cache::forget('constants_brands');

        return $this->noContent();
    }
}
