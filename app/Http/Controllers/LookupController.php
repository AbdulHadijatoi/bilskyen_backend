<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\FuelType;
use App\Models\Transmission;
use App\Models\VehicleModel;
use App\Models\GearType;
use App\Models\VehicleUse;
use App\Models\SalesType;
use App\Models\PriceType;
use App\Models\Condition;
use App\Models\Variant;
use App\Models\Category;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Type;
use App\Models\Permit;
use App\Models\ModelYear;
use App\Models\ListingType;
use App\Models\EquipmentType;
use App\Models\Equipment;
use App\Models\Euronom;
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

    /**
     * Get all lookup constants in a single response
     * GET /api/v1/constants
     * 
     * Returns all lookup tables data with consistent format:
     * - Simple lookups: id and name
     * - Models: id, name, and brand_id
     * - Equipments: id, name, and equipment_type_id
     */
    public function constants(): JsonResponse
    {
        try {
            // Simple lookups (id and name only)
            $brands = Brand::select('id', 'name')->orderBy('name')->get();
            $fuelTypes = FuelType::select('id', 'name')->orderBy('name')->get();
            $transmissions = Transmission::select('id', 'name')->orderBy('name')->get();
            $gearTypes = GearType::select('id', 'name')->orderBy('name')->get();
            $vehicleUses = VehicleUse::select('id', 'name')->orderBy('name')->get();
            $salesTypes = SalesType::select('id', 'name')->orderBy('name')->get();
            $priceTypes = PriceType::select('id', 'name')->orderBy('name')->get();
            $conditions = Condition::select('id', 'name')->orderBy('name')->get();
            $variants = Variant::select('id', 'name')->orderBy('name')->get();
            $categories = Category::select('id', 'name')->orderBy('name')->get();
            $bodyTypes = BodyType::select('id', 'name')->orderBy('name')->get();
            $colors = Color::select('id', 'name')->orderBy('name')->get();
            $types = Type::select('id', 'name')->orderBy('name')->get();
            $permits = Permit::select('id', 'name')->orderBy('name')->get();
            $modelYears = ModelYear::select('id', 'name')->orderBy('name')->get();
            $listingTypes = ListingType::select('id', 'name')->orderBy('name')->get();
            $equipmentTypes = EquipmentType::select('id', 'name')->orderBy('name')->get();
            $euronorms = Euronom::select('id', 'name')->orderBy('name')->get();

            // Models with parent reference (brand_id)
            $models = VehicleModel::select('id', 'name', 'brand_id')->orderBy('name')->get();

            // Equipments with parent reference (equipment_type_id)
            $equipments = Equipment::select('id', 'name', 'equipment_type_id')->orderBy('name')->get();

            return $this->success([
                'brands' => $brands,
                'fuel_types' => $fuelTypes,
                'transmissions' => $transmissions,
                'gear_types' => $gearTypes,
                'vehicle_uses' => $vehicleUses,
                'sales_types' => $salesTypes,
                'price_types' => $priceTypes,
                'conditions' => $conditions,
                'variants' => $variants,
                'categories' => $categories,
                'body_types' => $bodyTypes,
                'colors' => $colors,
                'types' => $types,
                'permits' => $permits,
                'model_years' => $modelYears,
                'listing_types' => $listingTypes,
                'equipment_types' => $equipmentTypes,
                'euronorms' => $euronorms,
                'models' => $models,
                'equipments' => $equipments,
            ]);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to fetch constants: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
}

