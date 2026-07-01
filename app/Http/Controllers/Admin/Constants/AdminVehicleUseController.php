<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrVehicleUse;
use App\Traits\ConstantsCacheTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin vehicle use constants — backed by {@see DmrVehicleUse} / `dmr_vehicle_uses`.
 */
class AdminVehicleUseController extends Controller
{
    use ConstantsCacheTrait;

    public function index(Request $request): JsonResponse
    {
        $vehicleUses = DmrVehicleUse::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($vehicleUses);
    }

    public function show(int $id): JsonResponse
    {
        $vehicleUse = DmrVehicleUse::findOrFail($id);

        return $this->success($vehicleUse);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dmr_vehicle_uses', 'name')->whereNull('deleted_at'),
            ],
        ]);

        $vehicleUse = DmrVehicleUse::create($request->only(['name']));

        $this->clearConstantsCache('vehicle_uses');

        return $this->created($vehicleUse);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vehicleUse = DmrVehicleUse::findOrFail($id);

        $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('dmr_vehicle_uses', 'name')->ignore($id)->whereNull('deleted_at'),
            ],
        ]);

        $vehicleUse->update($request->only(['name']));

        $this->clearConstantsCache('vehicle_uses');

        return $this->success($vehicleUse);
    }

    public function delete(int $id): JsonResponse
    {
        $vehicleUse = DmrVehicleUse::findOrFail($id);
        $vehicleUse->delete();

        $this->clearConstantsCache('vehicle_uses');

        return $this->noContent();
    }
}
