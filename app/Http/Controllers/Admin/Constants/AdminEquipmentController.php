<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\LookupService;

/**
 * Admin Equipment Controller
 */
class AdminEquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $equipments = Equipment::with('equipmentType')
            ->orderBy('name')
            ->paginate($request->get('limit', 15));

        return $this->paginated($equipments);
    }

    public function show(int $id): JsonResponse
    {
        $equipment = Equipment::with('equipmentType')->findOrFail($id);
        return $this->success($equipment);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'equipment_type_id' => 'required|integer|exists:equipment_types,id',
        ]);

        $equipment = Equipment::create($request->only(['name', 'equipment_type_id']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('equipments');
        LookupService::forgetLookupCacheGroup('equipment_types');

        return $this->created($equipment);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'equipment_type_id' => 'sometimes|integer|exists:equipment_types,id',
        ]);

        $equipment->update($request->only(['name', 'equipment_type_id']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('equipments');
        LookupService::forgetLookupCacheGroup('equipment_types');

        return $this->success($equipment);
    }

    public function delete(int $id): JsonResponse
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();

        // Clear cache
        LookupService::forgetLookupCacheGroup('equipments');
        LookupService::forgetLookupCacheGroup('equipment_types');

        return $this->noContent();
    }
}
