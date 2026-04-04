<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\EquipmentType;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipment_types', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $equipmentType = EquipmentType::create($request->only(['name']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('equipment_types');
        LookupService::forgetLookupCacheGroup('equipments');

        return $this->created($equipmentType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $equipmentType = EquipmentType::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('equipment_types', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $equipmentType->update($request->only(['name']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('equipment_types');
        LookupService::forgetLookupCacheGroup('equipments');

        return $this->success($equipmentType);
    }

    public function delete(int $id): JsonResponse
    {
        $equipmentType = EquipmentType::findOrFail($id);
        $equipmentType->delete();

        // Clear cache
        LookupService::forgetLookupCacheGroup('equipment_types');
        LookupService::forgetLookupCacheGroup('equipments');

        return $this->noContent();
    }
}
