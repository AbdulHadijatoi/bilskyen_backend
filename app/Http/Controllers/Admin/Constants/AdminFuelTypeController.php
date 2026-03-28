<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\DmrDriveEnergy;
use App\Services\LookupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Fuel Type Controller
 */
class AdminFuelTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $fuelTypes = DmrDriveEnergy::orderBy('name')->paginate($request->get('limit', 15));

        return $this->paginated($fuelTypes);
    }

    public function show(int $id): JsonResponse
    {
        $fuelType = DmrDriveEnergy::findOrFail($id);
        return $this->success($fuelType);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dmr_drive_energies,name',
            'type_nummer' => 'nullable|integer',
        ]);

        $fuelType = DmrDriveEnergy::create($request->only(['name', 'type_nummer']));

        LookupService::forgetFuelTypesLookupCache();

        return $this->created($fuelType);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $fuelType = DmrDriveEnergy::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:dmr_drive_energies,name,' . $id,
            'type_nummer' => 'nullable|integer',
        ]);

        $fuelType->update($request->only(['name', 'type_nummer']));

        LookupService::forgetFuelTypesLookupCache();

        return $this->success($fuelType);
    }

    public function delete(int $id): JsonResponse
    {
        $fuelType = DmrDriveEnergy::findOrFail($id);
        $fuelType->delete();

        LookupService::forgetFuelTypesLookupCache();

        return $this->noContent();
    }
}
