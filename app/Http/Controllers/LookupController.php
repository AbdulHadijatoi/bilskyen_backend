<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\FuelType;
use App\Models\Transmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Lookup Controller
 * Provides lookup endpoints for reference data
 */
class LookupController extends Controller
{
    /**
     * Get locations
     * GET /api/v1/locations
     */
    public function locations(Request $request): JsonResponse
    {
        $query = Location::query();

        // Apply search if provided
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('city', 'like', "%{$search}%")
                  ->orWhere('postcode', 'like', "%{$search}%")
                  ->orWhere('region', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if ($request->has('country_code')) {
            $query->where('country_code', $request->input('country_code'));
        }

        if ($request->has('region')) {
            $query->where('region', $request->input('region'));
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'city');
        $sortOrder = $request->input('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate if requested, otherwise return all
        if ($request->has('limit')) {
            $limit = $request->input('limit', 15);
            $locations = $query->paginate($limit);
            return $this->paginated($locations);
        }

        $locations = $query->get();
        return $this->success($locations);
    }

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
}

