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
}
