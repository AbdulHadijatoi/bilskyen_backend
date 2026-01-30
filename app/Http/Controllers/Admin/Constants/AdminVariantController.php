<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


/**
 * Admin Variant Controller
 */
class AdminVariantController extends Controller
{
    use ConstantsCacheHelper;
    public function index(Request $request): JsonResponse
    {
        $variants = Variant::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($variants);
    }

    public function show(int $id): JsonResponse
    {
        $variant = Variant::findOrFail($id);
        return $this->success($variant);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:variants,name',
        ]);

        $variant = Variant::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('variants');

        return $this->created($variant);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $variant = Variant::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:variants,name,' . $id,
        ]);

        $variant->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('variants');

        return $this->success($variant);
    }

    public function delete(int $id): JsonResponse
    {
        $variant = Variant::findOrFail($id);
        $variant->delete();

        // Clear cache
        $this->clearConstantsCache('variants');

        return $this->noContent();
    }
}
