<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\GearType;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin Gear Type Controller
 */
class AdminGearTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $gearTypes = GearType::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($gearTypes);
    }

    public function show(int $id): JsonResponse
    {
        $gearType = GearType::findOrFail($id);
        return $this->success($gearType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('gear_types', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $gearType = GearType::create($request->only(['name']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('gear_types');

        return $this->created($gearType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $gearType = GearType::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('gear_types', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $gearType->update($request->only(['name']));

        // Clear cache
        LookupService::forgetLookupCacheGroup('gear_types');

        return $this->success($gearType);
    }

    public function delete(int $id): JsonResponse
    {
        $gearType = GearType::findOrFail($id);
        $gearType->delete();

        // Clear cache
        LookupService::forgetLookupCacheGroup('gear_types');

        return $this->noContent();
    }
}
