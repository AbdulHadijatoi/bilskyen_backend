<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\VehicleModel;
use App\Models\ModelYear;
use App\Models\FuelType;
use App\Models\Location;
use App\Models\ListingType;
use App\Models\VehicleListStatus;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Type;
use App\Models\VehicleUse;
use App\Models\PriceType;
use App\Models\Condition;
use App\Models\GearType;
use App\Models\SalesType;
use App\Models\Equipment;
use App\Models\Dealer;
use App\Models\DealerUser;
use App\Services\AuthService;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SellYourCarController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private VehicleService $vehicleService
    ) {}

    /**
     * Show the sell your car form page
     */
    public function show(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect()->route('login')->with('return_url', '/sell-your-car');
        }

        // Load all lookup data for dropdowns
        $lookupData = [
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'modelYears' => ModelYear::orderBy('name', 'desc')->get(),
            'fuelTypes' => FuelType::orderBy('name')->get(),
            'locations' => Location::orderBy('city')->get(),
            'listingTypes' => ListingType::orderBy('name')->get(),
            'vehicleListStatuses' => VehicleListStatus::orderBy('name')->get(),
            'bodyTypes' => BodyType::orderBy('name')->get(),
            'colors' => Color::orderBy('name')->get(),
            'types' => Type::orderBy('name')->get(),
            'uses' => VehicleUse::orderBy('name')->get(),
            'priceTypes' => PriceType::orderBy('name')->get(),
            'conditions' => Condition::orderBy('name')->get(),
            'gearTypes' => GearType::orderBy('name')->get(),
            'salesTypes' => SalesType::orderBy('name')->get(),
            'equipment' => Equipment::orderBy('name')->get(),
        ];

        return view('sell-your-car', [
            'user' => $user,
            'lookupData' => $lookupData,
        ]);
    }

    /**
     * Handle form submission and create vehicle
     */
    public function store(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect()->route('login')->with('return_url', '/sell-your-car');
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'registration' => 'required|string|max:20',
            'vin' => 'nullable|string|max:17',
            'price' => 'required|integer|min:0',
            'location_id' => 'required|exists:locations,id',
            'listing_type_id' => 'nullable|exists:listing_types,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'model_id' => 'nullable|exists:models,id',
            'model_year_id' => 'nullable|exists:model_years,id',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'mileage' => 'nullable|integer|min:0',
            'km_driven' => 'nullable|integer|min:0',
            'battery_capacity' => 'nullable|integer|min:0',
            'engine_power' => 'nullable|integer|min:0',
            'towing_weight' => 'nullable|integer|min:0',
            'ownership_tax' => 'nullable|integer|min:0',
            'first_registration_date' => 'nullable|date',
            'vehicle_list_status_id' => 'required|exists:vehicle_list_statuses,id',
            'published_at' => 'nullable|date',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipments,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Prepare vehicle data
            $vehicleData = $request->only([
                'title', 'registration', 'vin', 'price', 'location_id',
                'listing_type_id', 'category_id', 'brand_id', 'model_id',
                'model_year_id', 'fuel_type_id', 'mileage', 'km_driven',
                'battery_capacity', 'engine_power', 'towing_weight',
                'ownership_tax', 'first_registration_date',
                'vehicle_list_status_id', 'published_at'
            ]);

            // Add brand_name, model_name, and model_year_name if provided (for auto-creation)
            if ($request->has('brand_name')) {
                $vehicleData['brand_name'] = $request->input('brand_name');
            }
            if ($request->has('model_name')) {
                $vehicleData['model_name'] = $request->input('model_name');
            }
            if ($request->has('model_year_name')) {
                $vehicleData['model_year_name'] = $request->input('model_year_name');
            }
            if ($request->has('model_year')) {
                $vehicleData['model_year'] = $request->input('model_year');
            }

            // Set user_id and dealer_id
            $vehicleData['user_id'] = $user->id;
            $dealer = $user->dealers()->first();
            
            // If user doesn't have a dealer, create a default "Individual Seller" dealer
            if (!$dealer) {
                $dealer = DB::transaction(function () use ($user) {
                    // Create a default dealer for individual sellers
                    $dealer = Dealer::create([
                        'cvr' => 'INDIVIDUAL-' . $user->id . '-' . time(),
                        'address' => '',
                        'city' => '',
                        'postcode' => '',
                        'country_code' => 'DK',
                    ]);

                    // Associate user with dealer
                    DealerUser::create([
                        'dealer_id' => $dealer->id,
                        'user_id' => $user->id,
                        'role_id' => 1, // ROLE_OWNER
                        'created_at' => now(),
                    ]);

                    return $dealer;
                });
            }
            
            $vehicleData['dealer_id'] = $dealer->id;

            // Add equipment IDs if provided
            if ($request->has('equipment_ids')) {
                $vehicleData['equipment_ids'] = $request->input('equipment_ids');
            }

            // Add vehicle details if provided
            $detailsFields = [
                'description', 'vin_location', 'type_id', 'version', 'type_name',
                'registration_status', 'registration_status_updated_date', 'expire_date',
                'status_updated_date', 'total_weight', 'vehicle_weight',
                'technical_total_weight', 'coupling', 'towing_weight_brakes', 'minimum_weight',
                'gross_combination_weight', 'fuel_efficiency', 'engine_displacement',
                'engine_cylinders', 'engine_code', 'category', 'last_inspection_date',
                'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
                'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
                'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
                'leasing_period_end', 'use_id', 'color_id', 'body_type_id', 'dispensations',
                'permits', 'ncap_five', 'airbags', 'integrated_child_seats',
                'seat_belt_alarms', 'euronorm', 'price_type_id', 'condition_id',
                'gear_type_id', 'sales_type_id'
            ];

            foreach ($detailsFields as $field) {
                if ($request->has($field)) {
                    $vehicleData[$field] = $request->input($field);
                }
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                $vehicleData['images'] = $request->file('images');
            }

            // Create vehicle
            $vehicle = $this->vehicleService->createVehicle($vehicleData);

            return redirect()->route('vehicle.detail', ['serialNo' => $vehicle->id])
                ->with('success', 'Vehicle listed successfully!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to create vehicle: ' . $e->getMessage()])
                ->withInput();
        }
    }
}

