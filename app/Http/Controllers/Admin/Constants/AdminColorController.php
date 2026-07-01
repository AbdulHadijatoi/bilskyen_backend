<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrColour;
use App\Traits\ConstantsCacheTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin colour constants — backed by {@see DmrColour} / `dmr_colours`.
 */
class AdminColorController extends Controller
{
    use ConstantsCacheTrait;

    public function index(Request $request): JsonResponse
    {
        $colors = DmrColour::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($colors);
    }

    public function show(int $id): JsonResponse
    {
        $color = DmrColour::findOrFail($id);

        return $this->success($color);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dmr_colours', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $color = DmrColour::create($request->only(['name']));

        $this->clearConstantsCache('colors');

        return $this->created($color);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $color = DmrColour::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('dmr_colours', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $color->update($request->only(['name']));

        $this->clearConstantsCache('colors');

        return $this->success($color);
    }

    public function delete(int $id): JsonResponse
    {
        $color = DmrColour::findOrFail($id);
        $color->delete();

        $this->clearConstantsCache('colors');

        return $this->noContent();
    }
}
