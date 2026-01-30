<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Equipment Type Controller
 */
class AdminEquipmentTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $equipmentTypes = EquipmentType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($equipmentTypes);
    }

    public function show(int $id): JsonResponse
    {
        $equipmentType = EquipmentType::findOrFail($id);
        return $this->success($equipmentType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:equipment_types,name',
        ]);

        $equipmentType = EquipmentType::create($request->only(['name']));

        // Clear cache
        Cache::forget('constants_equipment_types');
        Cache::forget('constants_equipments'); // Also clear equipments cache as it may include types

        return $this->created($equipmentType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $equipmentType = EquipmentType::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:equipment_types,name,' . $id,
        ]);

        $equipmentType->update($request->only(['name']));

        // Clear cache
        Cache::forget('constants_equipment_types');
        Cache::forget('constants_equipments'); // Also clear equipments cache as it may include types

        return $this->success($equipmentType);
    }

    public function delete(int $id): JsonResponse
    {
        $equipmentType = EquipmentType::findOrFail($id);
        $equipmentType->delete();

        // Clear cache
        Cache::forget('constants_equipment_types');
        Cache::forget('constants_equipments'); // Also clear equipments cache as it may include types

        return $this->noContent();
    }
}
