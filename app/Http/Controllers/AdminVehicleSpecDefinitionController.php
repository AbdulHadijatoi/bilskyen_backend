<?php

namespace App\Http\Controllers;

use App\Models\DmrModel;
use App\Models\DmrVariant;
use App\Models\VehicleSpecDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminVehicleSpecDefinitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = VehicleSpecDefinition::query()
            ->with(['brand', 'model', 'variant'])
            ->orderByDesc('id');

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }
        if ($request->filled('model_id')) {
            $query->where('model_id', (int) $request->input('model_id'));
        }
        if ($request->filled('variant_id')) {
            $query->where('variant_id', (int) $request->input('variant_id'));
        }
        if ($request->filled('model_year')) {
            $y = (int) $request->input('model_year');
            $query->where('model_year_from', '<=', $y)
                ->where('model_year_to', '>=', $y);
        }
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            if ($term !== '') {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('value', 'like', $like);
                });
            }
        }

        return $this->paginated($query->paginate($request->get('limit', 15)));
    }

    public function show(int $id): JsonResponse
    {
        $row = VehicleSpecDefinition::with(['brand', 'model', 'variant'])->findOrFail($id);

        return $this->success($row);
    }

    public function create(Request $request): JsonResponse
    {
        $maxModelYear = (int) date('Y');

        $data = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:dmr_brands,id'],
            'model_id' => ['required', 'integer', 'exists:dmr_models,id'],
            'variant_id' => ['required', 'integer', 'exists:dmr_variants,id'],
            'model_year_from' => ['required', 'integer', 'min:1975', 'max:'.$maxModelYear],
            'model_year_to' => ['required', 'integer', 'min:1975', 'max:'.$maxModelYear, 'gte:model_year_from'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('vehicle_spec_definitions', 'name')->where(function ($query) use ($request) {
                    return $query
                        ->where('brand_id', (int) $request->input('brand_id'))
                        ->where('model_id', (int) $request->input('model_id'))
                        ->where('variant_id', (int) $request->input('variant_id'))
                        ->where('model_year_from', (int) $request->input('model_year_from'))
                        ->where('model_year_to', (int) $request->input('model_year_to'));
                }),
            ],
            'value' => ['required', 'string', 'max:65535'],
        ]);

        $hierarchyError = $this->validateHierarchy(
            (int) $data['brand_id'],
            (int) $data['model_id'],
            (int) $data['variant_id']
        );
        if ($hierarchyError !== null) {
            return $this->validationError($hierarchyError);
        }

        $row = VehicleSpecDefinition::create($data);

        return $this->created($row->load(['brand', 'model', 'variant']));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = VehicleSpecDefinition::findOrFail($id);

        $maxModelYear = (int) date('Y');

        $data = $request->validate([
            'brand_id' => ['sometimes', 'integer', 'exists:dmr_brands,id'],
            'model_id' => ['sometimes', 'integer', 'exists:dmr_models,id'],
            'variant_id' => ['sometimes', 'integer', 'exists:dmr_variants,id'],
            'model_year_from' => ['sometimes', 'integer', 'min:1975', 'max:'.$maxModelYear],
            'model_year_to' => ['sometimes', 'integer', 'min:1975', 'max:'.$maxModelYear],
            'name' => ['sometimes', 'string', 'max:255'],
            'value' => ['sometimes', 'string', 'max:65535'],
        ]);

        $merged = array_merge($row->only([
            'brand_id',
            'model_id',
            'variant_id',
            'model_year_from',
            'model_year_to',
            'name',
            'value',
        ]), $data);

        if ((int) $merged['model_year_from'] > (int) $merged['model_year_to']) {
            return $this->validationError([
                'model_year_to' => ['The model year end must be greater than or equal to the start year.'],
            ]);
        }

        $uniqueValidator = Validator::make(
            ['name' => $merged['name']],
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('vehicle_spec_definitions', 'name')
                        ->where(function ($query) use ($merged) {
                            return $query
                                ->where('brand_id', (int) $merged['brand_id'])
                                ->where('model_id', (int) $merged['model_id'])
                                ->where('variant_id', (int) $merged['variant_id'])
                                ->where('model_year_from', (int) $merged['model_year_from'])
                                ->where('model_year_to', (int) $merged['model_year_to']);
                        })
                        ->ignore($row->id),
                ],
            ]
        );
        if ($uniqueValidator->fails()) {
            return $this->validationError($uniqueValidator->errors()->toArray());
        }

        $hierarchyError = $this->validateHierarchy(
            (int) $merged['brand_id'],
            (int) $merged['model_id'],
            (int) $merged['variant_id']
        );
        if ($hierarchyError !== null) {
            return $this->validationError($hierarchyError);
        }

        $row->update($data);

        return $this->success($row->fresh()->load(['brand', 'model', 'variant']));
    }

    public function delete(int $id): JsonResponse
    {
        $row = VehicleSpecDefinition::findOrFail($id);
        $row->delete();

        return $this->noContent();
    }

    /**
     * @return array<string, array<int, string>>|null
     */
    private function validateHierarchy(int $brandId, int $modelId, int $variantId): ?array
    {
        $model = DmrModel::query()->find($modelId);
        if ($model === null) {
            return ['model_id' => ['Selected model does not exist.']];
        }
        if ((int) $model->brand_id !== $brandId) {
            return ['model_id' => ['Model does not belong to the selected brand.']];
        }

        $variant = DmrVariant::query()->find($variantId);
        if ($variant === null) {
            return ['variant_id' => ['Selected variant does not exist.']];
        }
        if ((int) $variant->model_id !== $modelId) {
            return ['variant_id' => ['Variant does not belong to the selected model.']];
        }

        return null;
    }
}
