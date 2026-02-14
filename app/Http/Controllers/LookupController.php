<?php

namespace App\Http\Controllers;

use App\Services\LookupService;
use Illuminate\Http\JsonResponse;

/**
 * Lookup Controller
 * Provides lookup endpoints for reference data (public API).
 */
class LookupController extends Controller
{
    /**
     * Get all lookup constants in a single response
     * GET /api/v1/constants
     *
     * Returns all lookup tables data with consistent format:
     * - Simple lookups: id and name
     * - Models: id, name, and brand_id
     * - Equipments: id, name, and equipment_type_id
     */
    public function constants(LookupService $lookupService): JsonResponse
    {
        try {
            $data = $lookupService->getPublicConstants();

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to fetch constants: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
}
