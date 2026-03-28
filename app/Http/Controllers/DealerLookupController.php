<?php

namespace App\Http\Controllers;

use App\Exceptions\NummerpladeApiException;
use App\Services\DmrFactVehicleLookupService;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerLookupController extends Controller
{
    public function __construct(
        private DmrFactVehicleLookupService $dmrFactVehicleLookupService
    ) {}

    /**
     * Get all lookup constants in a single response with caching
     * GET /api/v1/dealer/lookup-constants
     */
    public function getLookupConstants(LookupService $lookupService): JsonResponse
    {
        try {
            $data = $lookupService->getDealerConstants();

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to fetch constants data: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Lookup vehicle by registration number (local DMR dataset, same as sell-your-car /api/v1/dmr/vehicle-by-registration).
     * POST /api/v1/dealer/lookup/vehicle-by-registration
     */
    public function lookupVehicleByRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'registration' => 'required|string|max:20',
            'advanced' => 'sometimes|boolean',
        ]);

        try {
            $data = $this->dmrFactVehicleLookupService->lookupByRegistration(
                $request->input('registration')
            );

            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to lookup vehicle: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
}
