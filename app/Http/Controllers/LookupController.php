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
use Illuminate\Support\Facades\Cache;

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
     * 
     * Uses the same cache keys as dealer and admin APIs for consistency
     */
    public function constants(): JsonResponse
    {
        try {
            // Fetch all lookup data with individual caching (24 hour TTL)
            // Using same cache keys as dealer and admin APIs
            $brands = Cache::remember('constants_brands', 86400, function () {
                return Brand::select('id', 'name')->orderBy('name')->get();
            });

            $modelYears = Cache::remember('constants_model_years', 86400, function () {
                return ModelYear::select('id', 'name')->orderBy('name')->get();
            });

            $fuelTypes = Cache::remember('constants_fuel_types', 86400, function () {
                return FuelType::select('id', 'name')->orderBy('name')->get();
            });

            $gearTypes = Cache::remember('constants_gear_types', 86400, function () {
                return GearType::select('id', 'name')->orderBy('name')->get();
            });

            $listingTypes = Cache::remember('constants_listing_types', 86400, function () {
                return ListingType::select('id', 'name')->orderBy('name')->get();
            });

            $bodyTypes = Cache::remember('constants_body_types', 86400, function () {
                return BodyType::select('id', 'name')->orderBy('name')->get();
            });

            $colors = Cache::remember('constants_colors', 86400, function () {
                return Color::select('id', 'name')->orderBy('name')->get();
            });

            $variants = Cache::remember('constants_variants', 86400, function () {
                return Variant::select('id', 'name')->orderBy('name')->get();
            });

            $types = Cache::remember('constants_types', 86400, function () {
                return Type::select('id', 'name')->orderBy('name')->get();
            });

            $conditions = Cache::remember('constants_conditions', 86400, function () {
                return Condition::select('id', 'name')->orderBy('name')->get();
            });

            $salesTypes = Cache::remember('constants_sales_types', 86400, function () {
                return SalesType::select('id', 'name')->orderBy('name')->get();
            });

            $priceTypes = Cache::remember('constants_price_types', 86400, function () {
                return PriceType::select('id', 'name')->orderBy('name')->get();
            });

            $euronorms = Cache::remember('constants_euronorms', 86400, function () {
                return Euronom::select('id', 'name')->orderBy('name')->get();
            });

            $vehicleModels = Cache::remember('constants_vehicle_models', 86400, function () {
                return VehicleModel::select('id', 'name', 'brand_id')->orderBy('name')->get();
            });

            $vehicleUses = Cache::remember('constants_vehicle_uses', 86400, function () {
                return VehicleUse::select('id', 'name')->orderBy('name')->get();
            });

            $equipmentTypes = Cache::remember('constants_equipment_types', 86400, function () {
                return EquipmentType::select('id', 'name')->orderBy('name')->get();
            });

            $equipments = Cache::remember('constants_equipments', 86400, function () {
                return Equipment::select('id', 'name', 'equipment_type_id')->orderBy('name')->get();
            });

            // Additional constants not in dealer/admin APIs
            $transmissions = Cache::remember('constants_transmissions', 86400, function () {
                return Transmission::select('id', 'name')->orderBy('name')->get();
            });

            $categories = Cache::remember('constants_categories', 86400, function () {
                return Category::select('id', 'name')->orderBy('name')->get();
            });

            $permits = Cache::remember('constants_permits', 86400, function () {
                return Permit::select('id', 'name')->orderBy('name')->get();
            });

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
                'models' => $vehicleModels,
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

