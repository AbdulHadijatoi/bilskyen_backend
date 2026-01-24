<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\FuelType;
use App\Models\GearType;
use App\Models\VehicleUse;
use App\Models\SalesType;
use App\Models\EquipmentType;
use App\Models\Variant;
use App\Models\PriceType;
use App\Models\Condition;
use App\Models\VehicleModel;
use App\Services\NummerpladeApiService;
use App\Exceptions\NummerpladeApiException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DealerLookupController extends Controller
{
    public function __construct(
        private NummerpladeApiService $nummerpladeService
    ) {}

    /**
     * Get all lookup constants in a single response
     * GET /api/v1/dealer/lookup-constants
     */
    public function getLookupConstants(): JsonResponse
    {
        try {
            // Fetch all lookup data in parallel
            $brands = Brand::orderBy('name')->get();
            $fuelTypes = FuelType::orderBy('name')->get();
            $gearTypes = GearType::orderBy('name')->get();
            $vehicleUses = VehicleUse::orderBy('name')->get();
            $equipmentTypes = EquipmentType::with(['equipments' => function ($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get();
            $salesTypes = SalesType::orderBy('name')->get();
            $priceTypes = PriceType::orderBy('name')->get();
            $conditions = Condition::orderBy('name')->get();
            $variants = Variant::orderBy('name')->get();
            $models = VehicleModel::orderBy('name')->get();

            // Static drivetrain types
            $drivetrainTypes = [
                ['value' => 'FWD', 'title' => 'FWD'],
                ['value' => 'RWD', 'title' => 'RWD'],
                ['value' => 'AWD', 'title' => 'AWD'],
            ];

            return $this->success([
                'brands' => $brands,
                'fuel_types' => $fuelTypes,
                'gear_types' => $gearTypes,
                'vehicle_uses' => $vehicleUses,
                'equipment_types' => $equipmentTypes,
                'sales_types' => $salesTypes,
                'price_types' => $priceTypes,
                'conditions' => $conditions,
                'variants' => $variants,
                'models' => $models,
                'drivetrain_types' => $drivetrainTypes,
            ]);
        } catch (\Exception $e) {
            return $this->error(
                'Failed to fetch lookup constants: ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Lookup vehicle by registration number using Nummerplade API
     * POST /api/v1/dealer/lookup/vehicle-by-registration
     */
    public function lookupVehicleByRegistration(Request $request): JsonResponse
    {
        $request->validate([
            'registration' => 'required|string|max:20',
            'advanced' => 'sometimes|boolean',
        ]);

        try {
            $data = $this->nummerpladeService->getVehicleByRegistration(
                $request->input('registration'),
                $request->boolean('advanced', true)
            );

            return $this->success($data);
        } catch (NummerpladeApiException $e) {
            return $this->error(
                $e->getMessage(),
                $e->toArray(),
                $e->isRetryable() ? 503 : 400
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to lookup vehicle: ' . $e->getMessage(),
                [],
                500
            );
        }
    }
}
