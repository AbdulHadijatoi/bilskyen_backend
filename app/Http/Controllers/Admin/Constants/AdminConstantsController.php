<?php

namespace App\Http\Controllers\Admin\Constants;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ModelYear;
use App\Models\FuelType;
use App\Models\GearType;
use App\Models\ListingType;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Variant;
use App\Models\Type;
use App\Models\Condition;
use App\Models\SalesType;
use App\Models\PriceType;
use App\Models\Euronom;
use App\Models\VehicleModel;
use App\Models\VehicleUse;
use App\Models\VehicleListStatus;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Constants Controller
 * Provides a single endpoint to fetch all constant data with caching
 */
class AdminConstantsController extends Controller
{
    /**
     * Get all constants data with individual model caching
     * GET /api/v1/admin/constants
     */
    public function getConstantsData(): JsonResponse
    {
        try {
            // Fetch all lookup data with individual caching (24 hour TTL)
            $brands = Cache::remember('constants_brands', 86400, function () {
                return Brand::orderBy('name')->get();
            });

            $modelYears = Cache::remember('constants_model_years', 86400, function () {
                return ModelYear::orderBy('name')->get();
            });

            $fuelTypes = Cache::remember('constants_fuel_types', 86400, function () {
                return FuelType::orderBy('name')->get();
            });

            $gearTypes = Cache::remember('constants_gear_types', 86400, function () {
                return GearType::orderBy('name')->get();
            });

            $listingTypes = Cache::remember('constants_listing_types', 86400, function () {
                return ListingType::orderBy('name')->get();
            });

            $bodyTypes = Cache::remember('constants_body_types', 86400, function () {
                return BodyType::orderBy('name')->get();
            });

            $colors = Cache::remember('constants_colors', 86400, function () {
                return Color::orderBy('name')->get();
            });

            $variants = Cache::remember('constants_variants', 86400, function () {
                return Variant::with('model')->orderBy('name')->get();
            });

            $types = Cache::remember('constants_types', 86400, function () {
                return Type::orderBy('name')->get();
            });

            $conditions = Cache::remember('constants_conditions', 86400, function () {
                return Condition::orderBy('name')->get();
            });

            $salesTypes = Cache::remember('constants_sales_types', 86400, function () {
                return SalesType::orderBy('name')->get();
            });

            $priceTypes = Cache::remember('constants_price_types', 86400, function () {
                return PriceType::orderBy('name')->get();
            });

            $euronorms = Cache::remember('constants_euronorms', 86400, function () {
                return Euronom::orderBy('name')->get();
            });

            $vehicleModels = Cache::remember('constants_vehicle_models', 86400, function () {
                return VehicleModel::with('brand')->orderBy('name')->get();
            });

            $vehicleUses = Cache::remember('constants_vehicle_uses', 86400, function () {
                return VehicleUse::orderBy('name')->get();
            });

            // Cache vehicle list statuses forever since they are fixed constants
            $vehicleListStatuses = Cache::rememberForever('constants_vehicle_list_statuses', function () {
                return VehicleListStatus::orderBy('name')->get();
            });

            $equipmentTypes = Cache::remember('constants_equipment_types', 86400, function () {
                return EquipmentType::with(['equipments' => function ($query) {
                    $query->orderBy('name');
                }])->orderBy('name')->get();
            });

            $equipments = Cache::remember('constants_equipments', 86400, function () {
                return Equipment::with('equipmentType')->orderBy('name')->get();
            });

            return $this->success([
                'brands' => $brands,
                'model_years' => $modelYears,
                'fuel_types' => $fuelTypes,
                'gear_types' => $gearTypes,
                'listing_types' => $listingTypes,
                'body_types' => $bodyTypes,
                'colors' => $colors,
                'variants' => $variants,
                'types' => $types,
                'conditions' => $conditions,
                'sales_types' => $salesTypes,
                'price_types' => $priceTypes,
                'euronorms' => $euronorms,
                'vehicle_models' => $vehicleModels,
                'vehicle_uses' => $vehicleUses,
                'vehicle_list_statuses' => $vehicleListStatuses,
                'equipment_types' => $equipmentTypes,
                'equipments' => $equipments,
            ]);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to fetch constants data: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
}
