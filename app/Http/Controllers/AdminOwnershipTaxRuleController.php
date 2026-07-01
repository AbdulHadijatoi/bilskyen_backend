<?php

namespace App\Http\Controllers;

use App\Models\OwnershipTaxRule;
use App\Services\OwnershipTaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOwnershipTaxRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rules = OwnershipTaxRule::query()
            ->with('driveEnergy')
            ->orderByDesc('id')
            ->paginate($request->get('limit', 15));

        return $this->paginated($rules);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'registration_year_from' => ['required', 'integer', 'min:1900', 'max:2100'],
            'registration_year_to' => ['required', 'integer', 'min:1900', 'max:2100', 'gte:registration_year_from'],
            'km_per_liter_from' => ['required', 'numeric', 'min:0'],
            'km_per_liter_to' => ['required', 'numeric', 'min:0', 'gte:km_per_liter_from'],
            'dmr_drive_energy_id' => ['required', 'integer', 'exists:dmr_drive_energies,id'],
            'tax_amount' => ['required', 'integer', 'min:0'],
        ]);

        $rule = OwnershipTaxRule::create($data);

        app(OwnershipTaxService::class)->recalculateAllVehicles();

        return $this->created($rule->load('driveEnergy'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rule = OwnershipTaxRule::findOrFail($id);

        $data = $request->validate([
            'registration_year_from' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'registration_year_to' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'km_per_liter_from' => ['sometimes', 'numeric', 'min:0'],
            'km_per_liter_to' => ['sometimes', 'numeric', 'min:0'],
            'dmr_drive_energy_id' => ['sometimes', 'integer', 'exists:dmr_drive_energies,id'],
            'tax_amount' => ['sometimes', 'integer', 'min:0'],
        ]);

        $merged = array_merge($rule->only([
            'registration_year_from',
            'registration_year_to',
            'km_per_liter_from',
            'km_per_liter_to',
            'dmr_drive_energy_id',
            'tax_amount',
        ]), $data);

        if ((int) $merged['registration_year_to'] < (int) $merged['registration_year_from']) {
            return $this->validationError([
                'registration_year_to' => ['registration_year_to must be greater than or equal to registration_year_from'],
            ]);
        }

        if ((float) $merged['km_per_liter_to'] < (float) $merged['km_per_liter_from']) {
            return $this->validationError([
                'km_per_liter_to' => ['km_per_liter_to must be greater than or equal to km_per_liter_from'],
            ]);
        }

        $rule->update($data);

        app(OwnershipTaxService::class)->recalculateAllVehicles();

        return $this->success($rule->fresh()->load('driveEnergy'));
    }

    public function delete(int $id): JsonResponse
    {
        $rule = OwnershipTaxRule::findOrFail($id);
        $rule->delete();

        app(OwnershipTaxService::class)->recalculateAllVehicles();

        return $this->noContent();
    }
}

