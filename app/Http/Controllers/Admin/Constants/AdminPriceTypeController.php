<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\PriceType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Price Type Controller
 */
class AdminPriceTypeController extends Controller
{
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
            'name' => 'required|string|max:255|unique:price_types,name',
        ]);

        $priceType = PriceType::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_price_types');

        return $this->created($priceType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $priceType = PriceType::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:price_types,name,' . $id,
        ]);

        $priceType->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_price_types');

        return $this->success($priceType);
    }

    public function delete(int $id): JsonResponse
    {
        $priceType = PriceType::findOrFail($id);
        $priceType->delete();

        // Clear cache
        Cache::forget('constants_price_types');

        return $this->noContent();
    }
}
