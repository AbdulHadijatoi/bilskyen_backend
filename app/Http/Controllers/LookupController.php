<?php

namespace App\Http\Controllers;

use App\Models\FuelType;
use App\Models\Transmission;
use App\Models\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Lookup Controller
 * Provides lookup endpoints for reference data
 */
class LookupController extends Controller
{
    /**
     * Get fuel types
     * GET /api/v1/fuel-types
     */
    public function fuelTypes(Request $request): JsonResponse
    {
        $query = FuelType::query();

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply sorting
        $query->orderBy('name', 'asc');

        // Paginate if requested, otherwise return all
        if ($request->has('limit')) {
            $limit = $request->input('limit', 15);
            $fuelTypes = $query->paginate($limit);
            return $this->paginated($fuelTypes);
        }

        $fuelTypes = $query->get();
        return $this->success($fuelTypes);
    }

    /**
     * Get transmission types
     * GET /api/v1/transmissions
     */
    public function transmissions(Request $request): JsonResponse
    {
        $query = Transmission::query();

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply sorting
        $query->orderBy('name', 'asc');

        // Paginate if requested, otherwise return all
        if ($request->has('limit')) {
            $limit = $request->input('limit', 15);
            $transmissions = $query->paginate($limit);
            return $this->paginated($transmissions);
        }

        $transmissions = $query->get();
        return $this->success($transmissions);
    }

    /**
     * Get vehicle models by brand
     * GET /api/v1/models?brand_id=1
     */
    public function models(Request $request): JsonResponse
    {
        $query = VehicleModel::query();

        // Filter by brand_id if provided
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply sorting
        $query->orderBy('name', 'asc');

        // Paginate if requested, otherwise return all
        if ($request->has('limit')) {
            $limit = $request->input('limit', 15);
            $models = $query->paginate($limit);
            return $this->paginated($models);
        }

        $models = $query->get();
        return $this->success($models);
    }
}

