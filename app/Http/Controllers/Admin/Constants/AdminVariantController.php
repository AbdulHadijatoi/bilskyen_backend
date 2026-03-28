<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrVariant;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ConstantsCacheTrait;


/**
 * Admin Variant Controller
 */
class AdminVariantController extends Controller
{
    use ConstantsCacheTrait;
    public function index(Request $request): JsonResponse
    {
        $query = DmrVariant::query()
            ->with('model')
            ->orderBy('name');

        if ($request->filled('model_id')) {
            $query->where('model_id', (int) $request->input('model_id'));
        }

        return $this->paginated($query->paginate($request->get('limit', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $variant = DmrVariant::with('model')->findOrFail($id);
        return $this->success($variant);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:variants,name',
            'model_id' => 'nullable|integer|exists:models,id',
        ]);

        $variant = DmrVariant::create($request->only(['name', 'model_id']));

        // Clear cache
        $this->clearConstantsCache('variants');

        return $this->created($variant);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $variant = DmrVariant::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:variants,name,' . $id,
            'model_id' => 'nullable|integer|exists:models,id',
        ]);

        $variant->update($request->only(['name', 'model_id']));

        // Clear cache
        $this->clearConstantsCache('variants');

        return $this->success($variant);
    }

    public function delete(int $id): JsonResponse
    {
        $variant = DmrVariant::findOrFail($id);
        $variant->delete();

        // Clear cache
        $this->clearConstantsCache('variants');

        return $this->noContent();
    }
}
