<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\PriceType;
use App\Traits\ConstantsCacheTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


/**
 * Admin Price Type Controller
 */
class AdminPriceTypeController extends Controller
{
    use ConstantsCacheTrait;
    public function index(Request $request): JsonResponse
    {
        $priceTypes = PriceType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($priceTypes);
    }

    public function show(int $id): JsonResponse
    {
        $priceType = PriceType::findOrFail($id);
        return $this->success($priceType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('price_types', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $priceType = PriceType::create($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('price_types');

        return $this->created($priceType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $priceType = PriceType::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('price_types', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $priceType->update($request->only(['name']));

        // Clear cache
        $this->clearConstantsCache('price_types');

        return $this->success($priceType);
    }

    public function delete(int $id): JsonResponse
    {
        $priceType = PriceType::findOrFail($id);
        $priceType->delete();

        // Clear cache
        $this->clearConstantsCache('price_types');

        return $this->noContent();
    }
}
