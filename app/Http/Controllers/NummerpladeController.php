<?php

namespace App\Http\Controllers;

use App\Exceptions\NummerpladeApiException;
use App\Services\NummerpladeApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Nummerplade API Proxy Controller
 * Provides endpoints for Flutter/Vue.js to fetch vehicle data
 */
class NummerpladeController extends Controller
{
    public function __construct(
        private NummerpladeApiService $nummerpladeService
    ) {}

    /**
     * Get vehicle by registration (license plate)
     */
    public function getVehicleByRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'registration' => 'required|string|max:20',
            'advanced' => 'sometimes|boolean',
        ]);

        try {
            $data = $this->nummerpladeService->getVehicleByRegistration(
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
        }
    }

    /**
     * Get vehicle by VIN
     */
    public function getVehicleByVin(Request $request): JsonResponse
    {
        $request->validate([
            'vin' => 'required|string|max:17',
            'advanced' => 'sometimes|boolean',
        ]);

        try {
            $data = $this->nummerpladeService->getVehicleByVin(
                $request->input('vin'),
                $request->boolean('advanced', false)
            );

            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get body types reference data
     */
    public function getBodyTypes(): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getBodyTypes();
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get colors reference data
     */
    public function getColors(): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getColors();
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get fuel types reference data
     */
    public function getFuelTypes(): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getFuelTypes();
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get equipment reference data
     */
    public function getEquipment(): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getEquipment();
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get permits reference data
     */
    public function getPermits(): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getPermits();
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get types reference data
     */
    public function getTypes(): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getTypes();
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get vehicle uses reference data
     */
    public function getUses(): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getUses();
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get vehicle inspections
     */
    public function getInspections(int $vehicleId): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getInspections($vehicleId);
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get DMR data
     */
    public function getDmrData(int $vehicleId): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getDmrData($vehicleId);
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get debt data
     */
    public function getDebt(int $vehicleId): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getDebt($vehicleId);
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get tinglysning data
     */
    public function getTinglysning(string $vin): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getTinglysning($vin);
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get emissions data
     */
    public function getEmissions(string $input): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getEmissions($input);
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }

    /**
     * Get evaluations data
     */
    public function getEvaluations(string $input): JsonResponse
    {
        try {
            $data = $this->nummerpladeService->getEvaluations($input);
            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        }
    }
}

