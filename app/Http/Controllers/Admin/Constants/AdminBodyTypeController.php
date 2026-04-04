<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrBodyType;
use App\Traits\ConstantsCacheTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin body type constants — backed by {@see DmrBodyType} / `dmr_body_types`.
 */
class AdminBodyTypeController extends Controller
{
    use ConstantsCacheTrait;

    public function index(Request $request): JsonResponse
    {
        $bodyTypes = DmrBodyType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($bodyTypes);
    }

    public function show(int $id): JsonResponse
    {
        $bodyType = DmrBodyType::findOrFail($id);

        return $this->success($bodyType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dmr_body_types', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $bodyType = DmrBodyType::create($request->only(['name']));

        $this->clearConstantsCache('body_types');

        return $this->created($bodyType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bodyType = DmrBodyType::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('dmr_body_types', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $bodyType->update($request->only(['name']));

        $this->clearConstantsCache('body_types');

        return $this->success($bodyType);
    }

    public function delete(int $id): JsonResponse
    {
        $bodyType = DmrBodyType::findOrFail($id);
        $bodyType->delete();

        $this->clearConstantsCache('body_types');

        return $this->noContent();
    }
}
