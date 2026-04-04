<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Services\LookupService;
use Illuminate\Http\JsonResponse;

/**
 * Admin Constants Controller
 * Provides a single endpoint to fetch all constant data with caching.
 */
class AdminConstantsController extends Controller
{
    /**
     * Get all constants data with individual model caching
     * GET /api/v1/admin/constants
     */
    public function getConstantsData(LookupService $lookupService): JsonResponse
    {
        try {
            $data = $lookupService->getAdminConstants();

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error(
                __('messages.api.failed_fetch_constants_data', ['message' => $e->getMessage()]),
                [],
                500
            );
        }
    }
}
