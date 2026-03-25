<?php

namespace App\Http\Controllers;

use App\Exceptions\NummerpladeApiException;
use App\Services\DmrFactVehicleLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Local DMR fact vehicle lookup by registration (no external Nummerplade API).
 */
class DmrFactVehicleLookupController extends Controller
{
    public function __construct(
        private DmrFactVehicleLookupService $dmrFactVehicleLookupService
    ) {}

    /**
     * POST /api/v1/dmr/vehicle-by-registration
     * Body: { "registration": "AB12345", "advanced": false } — advanced is accepted for parity but unused.
     */
    public function lookupByRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'registration' => 'required|string|max:20',
            'advanced' => 'sometimes|boolean',
        ]);

        try {
            $data = $this->dmrFactVehicleLookupService->lookupByRegistration(
                $request->input('registration'),
                $request->boolean('advanced', false)
            );

            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DmrFactVehicleLookupController::lookupByRegistration', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->error(
                'An unexpected error occurred: ' . $e->getMessage(),
                ['exception' => get_class($e)],
                500
            );
        }
    }

    /**
     * GET /api/v1/dmr/manual-brands?search=&limit=
     * Returns { id, name } items for DMR brand dropdowns.
     */
    public function searchManualBrands(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'sometimes|nullable|string|max:120',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $search = $validated['search'] ?? null;
        $limit = (int) ($validated['limit'] ?? 10);

        $items = $this->dmrFactVehicleLookupService->{'searchManualBrands'}($search, $limit);

        return $this->success([
            'items' => $items,
            'limit' => $limit,
        ]);
    }

    /**
     * GET /api/v1/dmr/manual-models?search=&brand_id=&limit=
     * Returns { id, name, brand_id } items.
     */
    public function searchManualModels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'sometimes|nullable|string|max:120',
            'brand_id' => 'sometimes|nullable|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $search = $validated['search'] ?? null;
        $brandId = $validated['brand_id'] ?? null;
        $limit = (int) ($validated['limit'] ?? 10);

        $items = $this->dmrFactVehicleLookupService->{'searchManualModels'}($search, $brandId, $limit);

        return $this->success([
            'items' => $items,
            'limit' => $limit,
        ]);
    }

    /**
     * GET /api/v1/dmr/manual-model-years?search=&limit=
     * Returns { id, name } items.
     */
    public function searchManualModelYears(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'sometimes|nullable|string|max:20',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $search = $validated['search'] ?? null;
        $limit = (int) ($validated['limit'] ?? 10);

        $items = $this->dmrFactVehicleLookupService->{'searchManualModelYears'}($search, $limit);

        return $this->success([
            'items' => $items,
            'limit' => $limit,
        ]);
    }

    /**
     * GET /api/v1/dmr/manual-fuel-types?search=&limit=
     * Returns { id, name } items.
     */
    public function searchManualFuelTypes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'sometimes|nullable|string|max:120',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $search = $validated['search'] ?? null;
        $limit = (int) ($validated['limit'] ?? 10);

        $items = $this->dmrFactVehicleLookupService->{'searchManualFuelTypes'}($search, $limit);

        return $this->success([
            'items' => $items,
            'limit' => $limit,
        ]);
    }

    /**
     * POST /api/v1/dmr/vehicle-by-manual
     * Body: { manual_brand_id, manual_model_id, manual_model_year_id, manual_fuel_type_id }
     */
    public function lookupByManual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'manual_brand_id' => 'required|integer|exists:dmr_brands,id',
            'manual_model_id' => 'required|integer|exists:dmr_models,id',
            'manual_model_year_id' => 'required|integer|exists:model_years,id',
            'manual_fuel_type_id' => 'required|integer|exists:dmr_drive_energies,id',
        ]);

        $dmrFactVehicleId = $this->dmrFactVehicleLookupService->{'resolveDmrFactVehicleIdByManual'}(
            (int) $validated['manual_brand_id'],
            (int) $validated['manual_model_id'],
            (int) $validated['manual_model_year_id'],
            (int) $validated['manual_fuel_type_id'],
        );

        if (!$dmrFactVehicleId) {
            return $this->error(
                'No matching vehicle was found for the selected manual values.',
                [
                    'manual' => $validated,
                ],
                404,
                'NOT_FOUND'
            );
        }

        return $this->success([
            'dmr_fact_vehicle_id' => $dmrFactVehicleId,
        ]);
    }
}
