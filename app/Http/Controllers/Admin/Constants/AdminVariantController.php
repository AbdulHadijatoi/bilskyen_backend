<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ConstantsCacheTrait;
use Illuminate\Validation\Rule;


/**
 * Admin Variant Controller
 */
class AdminVariantController extends Controller
{
    use ConstantsCacheTrait;
    public function index(Request $request): JsonResponse
    {
        $query = DmrVariant::query()
            ->with(['model.brand'])
            ->orderBy('name');

        if ($request->filled('brand_id')) {
            $query->whereHas('model', function ($q) use ($request) {
                $q->where('brand_id', (int) $request->input('brand_id'));
            });
        }

        if ($request->filled('model_id')) {
            $query->where('model_id', (int) $request->input('model_id'));
        }

        return $this->paginated($query->paginate($request->get('limit', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $variant = DmrVariant::with(['model.brand'])->findOrFail($id);

        return $this->success($variant);
    }

    public function create(Request $request): JsonResponse
    {
        $brandId = (int) $request->input('brand_id');

        $request->validate([
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('dmr_brands', 'id')->whereNull('deleted_at'),
            ],
            'model_id' => [
                'required',
                'integer',
                Rule::exists('dmr_models', 'id')
                    ->where('brand_id', $brandId)
                    ->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dmr_variants', 'name')->where(
                    fn ($q) => $q->where('model_id', (int) $request->input('model_id'))
                ),
            ],
        ]);

        $variant = DmrVariant::create($request->only(['name', 'model_id']));
        $variant->load(['model.brand']);

        // Clear cache
        $this->clearConstantsCache('variants');

        return $this->created($variant);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $variant = DmrVariant::query()->with('model')->findOrFail($id);

        $modelIdForUnique = $request->filled('model_id')
            ? (int) $request->input('model_id')
            : (int) $variant->model_id;

        $brandIdForModelRule = $request->filled('brand_id')
            ? (int) $request->input('brand_id')
            : (int) ($variant->model?->brand_id ?? 0);

        $request->validate([
            'brand_id' => [
                'sometimes',
                'integer',
                Rule::exists('dmr_brands', 'id')->whereNull('deleted_at'),
            ],
            'model_id' => [
                'sometimes',
                'integer',
                Rule::exists('dmr_models', 'id')
                    ->where('brand_id', $brandIdForModelRule)
                    ->whereNull('deleted_at'),
            ],
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('dmr_variants', 'name')
                    ->where(fn ($q) => $q->where('model_id', $modelIdForUnique))
                    ->ignore($id),
            ],
        ]);

        $variant->update($request->only(['name', 'model_id']));
        $variant->load(['model.brand']);

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
