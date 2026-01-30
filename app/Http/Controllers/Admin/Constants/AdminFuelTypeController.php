<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\FuelType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Fuel Type Controller
 */
class AdminFuelTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $fuelTypes = FuelType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($fuelTypes);
    }

    public function show(int $id): JsonResponse
    {
        $fuelType = FuelType::findOrFail($id);
        return $this->success($fuelType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:fuel_types,name',
        ]);

        $fuelType = FuelType::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_fuel_types');

        return $this->created($fuelType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $fuelType = FuelType::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:fuel_types,name,' . $id,
        ]);

        $fuelType->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_fuel_types');

        return $this->success($fuelType);
    }

    public function delete(int $id): JsonResponse
    {
        $fuelType = FuelType::findOrFail($id);
        $fuelType->delete();

        // Clear cache
        Cache::forget('constants_fuel_types');

        return $this->noContent();
    }
}
