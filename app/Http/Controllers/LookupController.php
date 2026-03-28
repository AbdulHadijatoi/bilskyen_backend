<?php

namespace App\Http\Controllers;

use App\Services\LookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lookup Controller
 * Provides lookup endpoints for reference data (public API).
 */
class LookupController extends Controller
{
    /**
     * Parse query param that can be either:
     * - comma-separated string: "1,2,3"
     * - array: ["1","2"]
     * - empty/null
     */
    private function parseIdList(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            return array_values(array_filter(array_map(fn ($v) => (int) $v, $raw), fn ($v) => $v > 0));
        }

        if (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') return [];

            $parts = array_filter(array_map('trim', explode(',', $raw)));
            return array_values(array_filter(array_map(fn ($v) => (int) $v, $parts), fn ($v) => $v > 0));
        }

        return [];
    }

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

    /**
     * Search brands (partial dataset).
     * GET /api/v1/brands?search=
     */
    public function searchBrands(Request $request, LookupService $lookupService): JsonResponse
    {
        try {
            $search = $request->input('search');

            $items = $lookupService->searchBrands($search);

            return $this->success([
                'items' => $items,
            ], 200, 'Brands retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch brands: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Search models (partial dataset).
     * GET /api/v1/models?search=&brand_ids=
     */
    public function searchModels(Request $request, LookupService $lookupService): JsonResponse
    {
        try {
            $search = $request->input('search');
            $brandIds = $this->parseIdList($request->input('brand_ids'));

            $items = $lookupService->searchModels($search, $brandIds);

            return $this->success([
                'items' => $items,
            ], 200, 'Models retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch models: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Search types (partial dataset).
     * GET /api/v1/types?search=&limit=
     */
    public function searchTypes(Request $request, LookupService $lookupService): JsonResponse
    {
        try {
            $search = $request->input('search');
            $limit = (int) $request->input('limit', 25);

            $items = $lookupService->searchTypes($search, $limit);

            return $this->success([
                'items' => $items,
                'limit' => $limit,
            ], 200, 'Types retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch types: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Search variants (partial dataset).
     * GET /api/v1/variants?search=&model_ids=
     */
    public function searchVariants(Request $request, LookupService $lookupService): JsonResponse
    {
        try {
            $search = $request->input('search');
            $modelIds = $this->parseIdList($request->input('model_ids'));

            $items = $lookupService->searchVariants($search, $modelIds);

            return $this->success([
                'items' => $items,
            ], 200, 'Variants retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch variants: ' . $e->getMessage(), [], 500);
        }
    }
}
