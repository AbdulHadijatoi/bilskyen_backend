<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrEmissionNorm;
use App\Traits\ConstantsCacheTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin emission norm (“euronorm”) constants — backed by {@see DmrEmissionNorm} / `dmr_emission_norms`.
 */
class AdminEuronomController extends Controller
{
    use ConstantsCacheTrait;

    public function index(Request $request): JsonResponse
    {
        $euronorms = DmrEmissionNorm::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($euronorms);
    }

    public function show(int $id): JsonResponse
    {
        $euronom = DmrEmissionNorm::findOrFail($id);

        return $this->success($euronom);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dmr_emission_norms', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $euronom = DmrEmissionNorm::create($request->only(['name']));

        $this->clearConstantsCache('euronorms');

        return $this->created($euronom);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $euronom = DmrEmissionNorm::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('dmr_emission_norms', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $euronom->update($request->only(['name']));

        $this->clearConstantsCache('euronorms');

        return $this->success($euronom);
    }

    public function delete(int $id): JsonResponse
    {
        $euronom = DmrEmissionNorm::findOrFail($id);
        $euronom->delete();

        $this->clearConstantsCache('euronorms');

        return $this->noContent();
    }
}
