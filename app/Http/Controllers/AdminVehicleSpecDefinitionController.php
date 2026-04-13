<?php

namespace App\Http\Controllers;

use App\Models\DmrModel;
use App\Models\DmrVariant;
use App\Models\VehicleSpecDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminVehicleSpecDefinitionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = VehicleSpecDefinition::query()
            ->with(['brand', 'model'])
            ->orderByDesc('id');

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }
        if ($request->filled('model_id')) {
            $query->where('model_id', (int) $request->input('model_id'));
        }
        if ($request->filled('variant_id')) {
            $vid = (int) $request->input('variant_id');
            $query->where(function ($q) use ($vid): void {
                $q->whereNull('variant_ids')
                    ->orWhereJsonContains('variant_ids', $vid);
            });
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
                $query->where(function ($q) use ($like): void {
                    $q->where('name', 'like', $like)
                        ->orWhere('value', 'like', $like);
                });
            }
        }

        $paginator = $query->paginate($request->get('limit', 15));
        $this->hydrateVariantsForDefinitions($paginator->getCollection());

        return $this->paginated($paginator);
    }

    public function show(int $id): JsonResponse
    {
        $row = VehicleSpecDefinition::with(['brand', 'model'])->findOrFail($id);
        $this->hydrateVariantsForDefinitions([$row]);

        return $this->success($row);
    }

    public function create(Request $request): JsonResponse
    {
        $maxModelYear = (int) date('Y');

        $data = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:dmr_brands,id'],
            'model_id' => ['required', 'integer', 'exists:dmr_models,id'],
            'variant_ids' => ['nullable', 'array'],
            'variant_ids.*' => ['integer', 'exists:dmr_variants,id'],
            'model_year_from' => ['required', 'integer', 'min:1975', 'max:'.$maxModelYear],
            'model_year_to' => ['required', 'integer', 'min:1975', 'max:'.$maxModelYear, 'gte:model_year_from'],
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:65535'],
        ]);

        $variantIds = $this->normalizeVariantIdsFromRequest($data['variant_ids'] ?? null);
        unset($data['variant_ids']);
        $data['variant_ids'] = $variantIds;

        if ($this->hasDuplicateNameInCatalog(
            (string) $data['name'],
            (int) $data['brand_id'],
            (int) $data['model_id'],
            $variantIds,
            (int) $data['model_year_from'],
            (int) $data['model_year_to'],
            null
        )) {
            return $this->validationError([
                'name' => ['A spec with this name already exists for the same brand, model, model years, and variant scope.'],
            ]);
        }

        $hierarchyError = $this->validateHierarchy(
            (int) $data['brand_id'],
            (int) $data['model_id'],
            $variantIds
        );
        if ($hierarchyError !== null) {
            return $this->validationError($hierarchyError);
        }

        $row = VehicleSpecDefinition::create($data);
        $row->load(['brand', 'model']);
        $this->hydrateVariantsForDefinitions([$row]);

        return $this->created($row);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = VehicleSpecDefinition::findOrFail($id);

        $maxModelYear = (int) date('Y');

        $data = $request->validate([
            'brand_id' => ['sometimes', 'integer', 'exists:dmr_brands,id'],
            'model_id' => ['sometimes', 'integer', 'exists:dmr_models,id'],
            'variant_ids' => ['sometimes', 'nullable', 'array'],
            'variant_ids.*' => ['integer', 'exists:dmr_variants,id'],
            'model_year_from' => ['sometimes', 'integer', 'min:1975', 'max:'.$maxModelYear],
            'model_year_to' => ['sometimes', 'integer', 'min:1975', 'max:'.$maxModelYear],
            'name' => ['sometimes', 'string', 'max:255'],
            'value' => ['sometimes', 'string', 'max:65535'],
        ]);

        if (array_key_exists('variant_ids', $data)) {
            $data['variant_ids'] = $this->normalizeVariantIdsFromRequest($data['variant_ids']);
        }

        $merged = array_merge($row->only([
            'brand_id',
            'model_id',
            'variant_ids',
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

        /** @var list<int>|null $mergedVariantIds */
        $mergedVariantIds = $merged['variant_ids'] ?? null;
        if (is_array($mergedVariantIds)) {
            $mergedVariantIds = $this->normalizeVariantIdsFromRequest($mergedVariantIds);
        } else {
            $mergedVariantIds = null;
        }

        if ($this->hasDuplicateNameInCatalog(
            (string) $merged['name'],
            (int) $merged['brand_id'],
            (int) $merged['model_id'],
            $mergedVariantIds,
            (int) $merged['model_year_from'],
            (int) $merged['model_year_to'],
            $row->id
        )) {
            return $this->validationError([
                'name' => ['A spec with this name already exists for the same brand, model, model years, and variant scope.'],
            ]);
        }

        $hierarchyError = $this->validateHierarchy(
            (int) $merged['brand_id'],
            (int) $merged['model_id'],
            $mergedVariantIds
        );
        if ($hierarchyError !== null) {
            return $this->validationError($hierarchyError);
        }

        $row->update($data);
        $fresh = $row->fresh()->load(['brand', 'model']);
        $this->hydrateVariantsForDefinitions([$fresh]);

        return $this->success($fresh);
    }

    public function delete(int $id): JsonResponse
    {
        $row = VehicleSpecDefinition::findOrFail($id);
        $row->delete();

        return $this->noContent();
    }

    /**
     * @param  iterable<VehicleSpecDefinition>  $definitions
     */
    private function hydrateVariantsForDefinitions(iterable $definitions): void
    {
        $definitions = Collection::wrap($definitions);
        $allIds = $definitions
            ->flatMap(static fn (VehicleSpecDefinition $d) => $d->normalizedVariantIds())
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($allIds === []) {
            foreach ($definitions as $d) {
                $d->setRelation('variants', collect());
            }

            return;
        }

        $byId = DmrVariant::query()
            ->whereIn('id', $allIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        foreach ($definitions as $d) {
            $ordered = collect($d->normalizedVariantIds())
                ->map(static fn (int $vid) => $byId->get($vid))
                ->filter()
                ->values();
            $d->setRelation('variants', $ordered);
        }
    }

    /**
     * @param  list<int>|null  $variantIds
     */
    private function hasDuplicateNameInCatalog(
        string $name,
        int $brandId,
        int $modelId,
        ?array $variantIds,
        int $modelYearFrom,
        int $modelYearTo,
        ?int $ignoreId,
    ): bool {
        $query = VehicleSpecDefinition::query()
            ->where('brand_id', $brandId)
            ->where('model_id', $modelId)
            ->where('model_year_from', $modelYearFrom)
            ->where('model_year_to', $modelYearTo)
            ->where('name', $name);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        $targetFp = $this->variantScopeFingerprint($variantIds ?? []);

        foreach ($query->get(['id', 'variant_ids']) as $existing) {
            if ($this->variantScopeFingerprint($existing->normalizedVariantIds()) === $targetFp) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<int>  $ids
     */
    private function variantScopeFingerprint(array $ids): string
    {
        if ($ids === []) {
            return '';
        }
        $copy = $ids;
        sort($copy);

        return implode(',', $copy);
    }

    /**
     * @return list<int>|null
     */
    private function normalizeVariantIdsFromRequest(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_array($value)) {
            return null;
        }

        $out = [];
        foreach ($value as $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $n = (int) $v;
            if ($n > 0) {
                $out[] = $n;
            }
        }
        $out = array_values(array_unique($out));

        return $out === [] ? null : $out;
    }

    /**
     * @param  list<int>|null  $variantIds
     * @return array<string, array<int, string>>|null
     */
    private function validateHierarchy(int $brandId, int $modelId, ?array $variantIds): ?array
    {
        if ($variantIds === null || $variantIds === []) {
            return null;
        }

        $model = DmrModel::query()->find($modelId);
        if ($model === null) {
            return ['model_id' => ['Selected model does not exist.']];
        }
        if ((int) $model->brand_id !== $brandId) {
            return ['model_id' => ['Model does not belong to the selected brand.']];
        }

        foreach ($variantIds as $variantId) {
            $variant = DmrVariant::query()->find($variantId);
            if ($variant === null) {
                return ['variant_ids' => ['Selected variant does not exist.']];
            }
            if ((int) $variant->model_id !== $modelId) {
                return ['variant_ids' => ['One or more variants do not belong to the selected model.']];
            }
        }

        return null;
    }
}
