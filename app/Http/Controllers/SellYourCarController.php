<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\ModelYear;
use App\Models\FuelType;
use App\Models\ListingType;
use App\Models\VehicleListStatus;
use App\Constants\VehicleListStatus as VehicleListStatusConstant;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Type;
use App\Models\VehicleUse;
use App\Models\PriceType;
use App\Models\Condition;
use App\Models\GearType;
use App\Models\SalesType;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Variant;
use App\Models\Euronom;
use App\Models\Plan;
use App\Models\Dealer;
use App\Models\VehicleDetail;
use App\Models\Location;
use App\Models\FeaturedListing;
use App\Services\AuthService;
use App\Services\VehicleService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SellYourCarController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private VehicleService $vehicleService,
        private AuditLogService $auditLogService
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
            'models' => VehicleModel::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'modelYears' => ModelYear::orderBy('name', 'desc')->get(),
            'fuelTypes' => FuelType::orderBy('name')->get(),
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
            'variants' => Variant::orderBy('name')->get(),
            'euronorms' => Euronom::orderBy('name')->get(),
            'equipmentTypes' => EquipmentType::with(['equipments' => function($query) {
                $query->orderBy('name');
            }])->orderBy('name')->get(),
            'equipment' => Equipment::with('equipmentType')->orderBy('name')->get(), // Keep for backward compatibility
            'plans' => Plan::where('is_active', true)->with(['planFeatures.feature'])->orderBy('name')->get(),
            'locations' => Location::select('city', 'postcode', 'region')->orderBy('city')->get(),
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

        // Log all request data for debugging
        $allRequestData = $request->all();
        
        // Separate files from other data for cleaner logging
        $requestDataWithoutFiles = $allRequestData;
        $fileInfo = [];
        
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $fileInfo = [
                'has_images' => true,
                'count' => is_array($images) ? count($images) : 1,
                'file_names' => is_array($images) 
                    ? array_map(fn($img) => $img->getClientOriginalName() . ' (' . $img->getSize() . ' bytes)', $images)
                    : [$images->getClientOriginalName() . ' (' . $images->getSize() . ' bytes)']
            ];
            // Remove files from the main log data
            unset($requestDataWithoutFiles['images']);
        } else {
            $fileInfo = ['has_images' => false];
        }
        
        Log::info('SellYourCarController::store - Request received', [
            'user_id' => $user->id,
            'request_data' => $requestDataWithoutFiles,
            'files' => $fileInfo,
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->wantsJson(),
            'transmission_id' => $request->input('transmission_id'),
            'transmission_name' => $request->input('transmission_name'),
        ]);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'registration' => 'required|string|max:20',
            'vin' => 'nullable|string|max:17',
            'price' => 'required|integer|min:0',
            'listing_type_id' => 'nullable|exists:listing_types,id',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'model_id' => 'nullable|exists:models,id',
            'model_year_id' => 'nullable|exists:model_years,id',
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'km_driven' => 'required|integer|min:0',
            'battery_capacity' => 'nullable|integer|min:0',
            'range_km' => 'nullable|integer|min:0',
            'charging_type' => 'nullable|string|max:100',
            'engine_power' => 'nullable|integer|min:0',
            'towing_weight' => 'nullable|integer|min:0',
            'ownership_tax' => 'nullable|integer|min:0',
            'first_registration_date' => 'nullable|date',
            'first_registration_month' => 'nullable|integer|min:1|max:12',
            'first_registration_year' => 'nullable|integer|min:1900|max:2100',
            'last_inspection_month' => 'nullable|integer|min:1|max:12',
            'last_inspection_year' => 'nullable|integer|min:1900|max:2100',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipments,id',
            'variant_id' => 'nullable|exists:variants,id',
            'variant_name' => 'nullable|string|max:100',
            'euronom_id' => 'nullable|exists:euronorms,id',
            'euronom_name' => 'nullable|string|max:100',
            'servicebog' => 'nullable|in:Yes,No,Default',
            'without_tax' => 'nullable|boolean',
            'seller_phone' => 'required|string|max:30',
            'seller_address' => 'required|string',
            'seller_postcode' => 'required|string|max:10',
            'images' => 'nullable',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif', // max 20MB per file
            // Additional API fields validation
            'vin_location' => 'nullable|string|max:255',
            'vehicle_external_id' => 'nullable|string|max:255',
            'type_id' => 'nullable|exists:types,id',
            'type_name' => 'nullable|string|max:255',
            'registration_status' => 'nullable|string|max:100',
            'registration_status_updated_date' => 'nullable|date',
            'expire_date' => 'nullable|date',
            'status_updated_date' => 'nullable|date',
            'total_weight' => 'nullable|integer|min:0',
            'vehicle_weight' => 'nullable|integer|min:0',
            'coupling' => 'nullable|boolean',
            'towing_weight_brakes' => 'nullable|integer|min:0',
            'minimum_weight' => 'nullable|integer|min:0',
            'gross_combination_weight' => 'nullable|integer|min:0',
            'engine_displacement' => 'nullable|integer|min:0',
            'engine_cylinders' => 'nullable|integer|min:0',
            'engine_code' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'last_inspection_result' => 'nullable|string|max:100',
            'last_inspection_odometer' => 'nullable|integer|min:0',
            'type_approval_code' => 'nullable|string|max:100',
            'top_speed' => 'nullable|integer|min:0',
            'doors' => 'nullable|integer|min:0',
            'minimum_seats' => 'nullable|integer|min:0',
            'maximum_seats' => 'nullable|integer|min:0',
            'wheels' => 'nullable|string|max:65535',
            'extra_equipment' => 'nullable|string',
            'axles' => 'nullable|integer|min:0',
            'drive_axles' => 'nullable|integer|min:0',
            'wheelbase' => 'nullable|integer|min:0',
            'leasing_period_start' => 'nullable|date',
            'leasing_period_end' => 'nullable|date',
            'use_id' => 'nullable|exists:uses,id',
            'body_type_id' => 'nullable|exists:body_types,id',
            'dispensations' => 'nullable|string',
            'permits' => 'nullable|string',
            'ncap_five' => 'nullable|boolean',
            'airbags' => 'nullable|integer|min:0',
            'integrated_child_seats' => 'nullable|integer|min:0',
            'seat_belt_alarms' => 'nullable|integer|min:0',
            'transmission_id' => 'nullable|exists:transmissions,id',
            'transmission_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Handle variant lookup/insertion
            $variantId = null;
            if ($request->has('variant_id') && $request->input('variant_id')) {
                $variantId = $request->input('variant_id');
            } elseif ($request->has('variant_name') && $request->input('variant_name')) {
                $variant = Variant::firstOrCreate(['name' => $request->input('variant_name')]);
                $variantId = $variant->id;
            }

            // Handle euronom lookup/insertion
            $euronomId = null;
            if ($request->has('euronom_id') && $request->input('euronom_id')) {
                $euronomId = $request->input('euronom_id');
            } elseif ($request->has('euronom_name') && $request->input('euronom_name')) {
                $euronom = Euronom::firstOrCreate(['name' => $request->input('euronom_name')]);
                $euronomId = $euronom->id;
            }

            // Handle type lookup/insertion
            $typeId = null;
            if ($request->has('type_id') && $request->input('type_id')) {
                $typeId = $request->input('type_id');
            } elseif ($request->has('type_name') && $request->input('type_name')) {
                $type = \App\Models\Type::firstOrCreate(['name' => $request->input('type_name')]);
                $typeId = $type->id;
            } elseif ($request->has('type') && is_array($request->input('type')) && isset($request->input('type')['name'])) {
                $type = \App\Models\Type::firstOrCreate(['name' => $request->input('type')['name']]);
                $typeId = $type->id;
            }

            // Handle use lookup/insertion
            $useId = null;
            if ($request->has('use_id') && $request->input('use_id')) {
                $useId = $request->input('use_id');
            } elseif ($request->has('use') && is_array($request->input('use')) && isset($request->input('use')['name'])) {
                $use = \App\Models\VehicleUse::firstOrCreate(['name' => $request->input('use')['name']]);
                $useId = $use->id;
            }

            // Handle body_type lookup/insertion
            $bodyTypeId = null;
            if ($request->has('body_type_id') && $request->input('body_type_id')) {
                $bodyTypeId = $request->input('body_type_id');
            } elseif ($request->has('body_type') && is_array($request->input('body_type')) && isset($request->input('body_type')['name'])) {
                $bodyType = \App\Models\BodyType::firstOrCreate(['name' => $request->input('body_type')['name']]);
                $bodyTypeId = $bodyType->id;
            }

            // Handle month/year to date conversion for first_registration_date
            if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
                $month = $request->input('first_registration_month');
                $year = $request->input('first_registration_year');
                $firstRegistrationDate = sprintf('%04d-%02d-01', $year, $month);
            } elseif ($request->has('first_registration_date')) {
                $firstRegistrationDate = $request->input('first_registration_date');
            } else {
                $firstRegistrationDate = null;
            }

            // Handle month/year to date conversion for last_inspection_date
            $lastInspectionDate = null;
            if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
                $month = $request->input('last_inspection_month');
                $year = $request->input('last_inspection_year');
                $lastInspectionDate = sprintf('%04d-%02d-01', $year, $month);
            } elseif ($request->has('last_inspection_date')) {
                $lastInspectionDate = $request->input('last_inspection_date');
            }

            // Auto-generate title if not provided
            $title = $request->input('title');
            if (empty($title) && $request->has('brand_id') && $request->has('model_id') && 
                $request->has('model_year_id') && $request->has('fuel_type_id')) {
                $title = $this->generateTitle(
                    $request->input('brand_id'),
                    $request->input('model_id'),
                    $request->input('model_year_id'),
                    $request->input('fuel_type_id')
                );
            }

            // Prepare vehicle data
            $vehicleData = $request->only([
                'registration', 'vin', 'price',
                'listing_type_id', 'category_id', 'brand_id', 'model_id',
                'model_year_id', 'fuel_type_id', 'km_driven',
                'battery_capacity', 'range_km', 'charging_type', 'engine_power', 'towing_weight',
                'ownership_tax', 'fuel_efficiency', 'seller_address', 'seller_postcode'
            ]);
            
            // Set vehicle_list_status_id automatically to 2 (ignore any value from frontend)
            $vehicleData['vehicle_list_status_id'] = 2;
            
            // Set published_at automatically (ignore any value from frontend)
            $vehicleData['published_at'] = now();
            
            // Add title and first_registration_date
            if ($title) {
                $vehicleData['title'] = $title;
            }
            if ($firstRegistrationDate) {
                $vehicleData['first_registration_date'] = $firstRegistrationDate;
            }

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

            // Set default listing_type_id to "Purchase" if not provided
            if (!isset($vehicleData['listing_type_id']) || empty($vehicleData['listing_type_id'])) {
                $purchaseType = \App\Models\ListingType::where('name', 'Purchase')->first();
                if ($purchaseType) {
                    $vehicleData['listing_type_id'] = $purchaseType->id;
                }
            }

            // Add equipment IDs if provided
            if ($request->has('equipment_ids')) {
                $vehicleData['equipment_ids'] = $request->input('equipment_ids');
            }

            // Handle price type
            $priceTypeId = null;
            if ($request->has('without_tax') && $request->boolean('without_tax')) {
                $withoutTaxPriceType = PriceType::firstOrCreate(['name' => 'Without tax']);
                $priceTypeId = $withoutTaxPriceType->id;
            } elseif ($request->has('price_type_id')) {
                $priceTypeId = $request->input('price_type_id');
            }

            // Auto-generate description
            $description = $request->input('description');
            if (empty($description)) {
                $description = $this->generateDescription($request, $variantId, $euronomId);
            }

            // Add vehicle details if provided
            $detailsFields = [
                'vin_location', 'vehicle_external_id', 'type_name',
                'registration_status', 'registration_status_updated_date', 'expire_date',
                'status_updated_date', 'total_weight', 'vehicle_weight',
                'technical_total_weight', 'towing_weight_brakes', 'minimum_weight',
                'gross_combination_weight', 'engine_displacement',
                'engine_cylinders', 'engine_code', 'category',
                'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
                'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
                'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
                'leasing_period_end', 'color_id', 'dispensations',
                'permits', 'airbags', 'integrated_child_seats',
                'seat_belt_alarms', 'condition_id', 'sales_type_id', 'servicebog',
                'seller_phone', 'annual_tax', 'owners',
                'transmission_id', 'transmission_name'
            ];

            $vehicleDetailsData = [];
            foreach ($detailsFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    // Handle JSON strings for arrays
                    if (($field === 'dispensations' || $field === 'permits') && is_string($value)) {
                        // Already a JSON string from frontend
                        $vehicleDetailsData[$field] = $value;
                    } else {
                        $vehicleDetailsData[$field] = $value;
                    }
                }
            }

            // Handle special fields
            // Map vehicle_id from API to vehicle_external_id
            if ($request->has('vehicle_id')) {
                $vehicleDetailsData['vehicle_external_id'] = $request->input('vehicle_id');
            }

            // Handle boolean fields - convert to integer
            if ($request->has('coupling')) {
                $vehicleDetailsData['coupling'] = $request->boolean('coupling') ? 1 : 0;
            }
            if ($request->has('ncap_five')) {
                $vehicleDetailsData['ncap_five'] = $request->boolean('ncap_five') ? 1 : 0;
            }

            // Handle arrays - convert to JSON strings
            if ($request->has('dispensations') && is_array($request->input('dispensations'))) {
                $vehicleDetailsData['dispensations'] = json_encode($request->input('dispensations'));
            }
            if ($request->has('permits') && is_array($request->input('permits'))) {
                $vehicleDetailsData['permits'] = json_encode($request->input('permits'));
            }

            // Set default condition_id to 2 if not provided
            if (!isset($vehicleDetailsData['condition_id']) || empty($vehicleDetailsData['condition_id'])) {
                $vehicleDetailsData['condition_id'] = 2;
            }

            // Add lookup IDs
            if ($variantId) {
                $vehicleDetailsData['variant_id'] = $variantId;
            }
            if ($euronomId) {
                $vehicleDetailsData['euronom_id'] = $euronomId;
            }
            if ($typeId) {
                $vehicleDetailsData['type_id'] = $typeId;
            }
            if ($useId) {
                $vehicleDetailsData['use_id'] = $useId;
            }
            if ($bodyTypeId) {
                $vehicleDetailsData['body_type_id'] = $bodyTypeId;
            }
            
            // Handle transmission_id if provided
            if ($request->has('transmission_id') && $request->input('transmission_id')) {
                $vehicleDetailsData['transmission_id'] = $request->input('transmission_id');
            }
            
            // Add type_name if provided separately
            if ($request->has('type_name')) {
                $vehicleDetailsData['type_name'] = $request->input('type_name');
            } elseif ($typeId) {
                // Get type name from database if we have the ID
                $type = \App\Models\Type::find($typeId);
                if ($type) {
                    $vehicleDetailsData['type_name'] = $type->name;
                }
            }

            if ($lastInspectionDate) {
                $vehicleDetailsData['last_inspection_date'] = $lastInspectionDate;
            }
            if ($description) {
                $vehicleDetailsData['description'] = $description;
            }
            if ($priceTypeId) {
                $vehicleDetailsData['price_type_id'] = $priceTypeId;
            }

            // Add vehicle details to vehicleData for VehicleService
            foreach ($vehicleDetailsData as $key => $value) {
                $vehicleData[$key] = $value;
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $vehicleData['images'] = $images;
                
                // Log for debugging (can be removed in production)
                Log::info('Images received in SellYourCarController', [
                    'count' => is_array($images) ? count($images) : 1,
                    'file_names' => is_array($images) 
                        ? array_map(fn($img) => $img->getClientOriginalName(), $images)
                        : [$images->getClientOriginalName()]
                ]);
            } else {
                Log::info('No images found in request');
            }


            // Create vehicle
            $vehicle = $this->vehicleService->createVehicle($vehicleData);

            // Log audit trail
            try {
                $this->auditLogService->logCreate(
                    $user,
                    'Vehicle',
                    $vehicle->id,
                    $vehicle->toArray(),
                    $request,
                    null,
                    null,
                    'Vehicle listing created via Sell Your Car web form',
                    ['vehicle', 'listing', 'sell-your-car', 'web']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create audit log for vehicle creation', [
                    'vehicle_id' => $vehicle->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Generate secure token for success page access
            $token = $this->generateSuccessToken($vehicle->id, $user->id);

            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Vehicle listed successfully!',
                    'vehicle_id' => $vehicle->id,
                    'token' => $token,
                    'redirect_url' => route('sell-your-car.success', ['token' => $token])
                ]);
            }

            return redirect()->route('sell-your-car.success', ['token' => $token])
                ->with('success', 'Vehicle listed successfully!');
        } catch (\Exception $e) {
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create vehicle: ' . $e->getMessage(),
                    'errors' => ['error' => [$e->getMessage()]]
                ], 500);
            }
            
            return back()
                ->withErrors(['error' => 'Failed to create vehicle: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Generate title from brand, model, model year, and fuel type
     */
    private function generateTitle(?int $brandId, ?int $modelId, ?int $modelYearId, ?int $fuelTypeId): ?string
    {
        $parts = [];
        
        if ($brandId) {
            $brand = Brand::find($brandId);
            if ($brand) {
                $parts[] = $brand->name;
            }
        }
        
        if ($modelId) {
            $model = VehicleModel::find($modelId);
            if ($model) {
                $parts[] = $model->name;
            }
        }
        
        if ($modelYearId) {
            $modelYear = ModelYear::find($modelYearId);
            if ($modelYear) {
                $parts[] = $modelYear->name;
            }
        }
        
        if ($fuelTypeId) {
            $fuelType = FuelType::find($fuelTypeId);
            if ($fuelType) {
                $parts[] = $fuelType->name;
            }
        }
        
        return !empty($parts) ? implode(' ', $parts) : null;
    }

    /**
     * Generate description from various fields
     */
    private function generateDescription(Request $request, ?int $variantId, ?int $euronomId): string
    {
        $descriptionParts = [];
        
        // Equipment
        if ($request->has('equipment_ids') && is_array($request->input('equipment_ids'))) {
            $equipmentIds = $request->input('equipment_ids');
            if (!empty($equipmentIds)) {
                $equipments = Equipment::whereIn('id', $equipmentIds)->pluck('name')->toArray();
                if (!empty($equipments)) {
                    $descriptionParts[] = 'Equipment: ' . implode(', ', $equipments);
                }
            }
        }
        
        // Servicebog
        if ($request->has('servicebog') && $request->input('servicebog')) {
            $servicebog = $request->input('servicebog');
            if ($servicebog !== 'Default') {
                $descriptionParts[] = 'Service book: ' . $servicebog;
            }
        }
        
        // Kilometer Driven
        if ($request->has('km_driven') && $request->input('km_driven')) {
            $descriptionParts[] = 'Kilometers driven: ' . number_format($request->input('km_driven'), 0, ',', '.') . ' km';
        }
        
        // First Registration
        if ($request->has('first_registration_month') && $request->has('first_registration_year')) {
            $month = $request->input('first_registration_month');
            $year = $request->input('first_registration_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'First registration: ' . $monthName . ' ' . $year;
        } elseif ($request->has('first_registration_date')) {
            $date = \Carbon\Carbon::parse($request->input('first_registration_date'));
            $descriptionParts[] = 'First registration: ' . $date->format('F Y');
        }
        
        // Last Inspection
        if ($request->has('last_inspection_month') && $request->has('last_inspection_year')) {
            $month = $request->input('last_inspection_month');
            $year = $request->input('last_inspection_year');
            $monthName = date('F', mktime(0, 0, 0, $month, 1));
            $descriptionParts[] = 'Last inspection: ' . $monthName . ' ' . $year;
        } elseif ($request->has('last_inspection_date')) {
            $date = \Carbon\Carbon::parse($request->input('last_inspection_date'));
            $descriptionParts[] = 'Last inspection: ' . $date->format('F Y');
        }
        
        // KM/L (Fuel Efficiency)
        if ($request->has('fuel_efficiency') && $request->input('fuel_efficiency')) {
            $descriptionParts[] = 'Fuel efficiency: ' . number_format($request->input('fuel_efficiency'), 2) . ' km/l';
        }
        
        // Euronom
        if ($euronomId) {
            $euronom = Euronom::find($euronomId);
            if ($euronom) {
                $descriptionParts[] = 'Euro norm: ' . $euronom->name;
            }
        }
        
        // Total Technical Weight
        if ($request->has('technical_total_weight') && $request->input('technical_total_weight')) {
            $descriptionParts[] = 'Total technical weight: ' . number_format($request->input('technical_total_weight'), 0, ',', '.') . ' kg';
        }
        
        return implode('. ', $descriptionParts) . '.';
    }

    /**
     * Generate secure token for success page access
     */
    private function generateSuccessToken(int $vehicleId, int $userId): string
    {
        // Generate cryptographically secure random token
        $token = Str::random(32);
        
        // Store token in cache with 1 hour expiration
        // Token data includes vehicle_id and user_id for verification
        Cache::put("vehicle_success_token:{$token}", [
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
        ], now()->addHour());
        
        return $token;
    }

    /**
     * Verify token and retrieve vehicle data
     */
    private function verifySuccessToken(string $token, int $expectedUserId): ?array
    {
        $tokenData = Cache::get("vehicle_success_token:{$token}");
        
        if (!$tokenData) {
            return null; // Token doesn't exist or expired
        }
        
        // Verify user_id matches
        if ($tokenData['user_id'] !== $expectedUserId) {
            return null; // Token doesn't belong to this user
        }
        
        return $tokenData;
    }

    /**
     * Show success page after vehicle creation
     */
    public function showSuccess(Request $request, string $token)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return redirect()->route('login')->with('return_url', '/sell-your-car');
        }

        // Verify token and get vehicle_id
        $tokenData = $this->verifySuccessToken($token, $user->id);
        
        if (!$tokenData) {
            return redirect()->route('sell-your-car')
                ->with('error', 'Invalid or expired access token. Please create a new vehicle listing.');
        }

        $vehicleId = $tokenData['vehicle_id'];
        
        try {
            // Use withTrashed() to include soft-deleted vehicles for the original creator
            $vehicle = Vehicle::withTrashed()
                ->with(['images', 'brand', 'model', 'modelYear', 'fuelType', 'details'])
                ->findOrFail($vehicleId);
        } catch (ModelNotFoundException $e) {
            // Vehicle has been permanently deleted
            return redirect()->route('sell-your-car')
                ->with('error', 'This vehicle listing no longer exists. It may have been permanently deleted.');
        }

        // Verify user owns this vehicle (double check)
        if ($vehicle->user_id !== $user->id) {
            return redirect()->route('sell-your-car')
                ->with('error', 'You do not have permission to access this vehicle listing.');
        }

        // Check if vehicle is soft-deleted
        if ($vehicle->trashed()) {
            return redirect()->route('sell-your-car')
                ->with('error', 'This vehicle listing has been deleted and is no longer accessible.');
        }

        // Check if vehicle is already featured
        $isFeatured = FeaturedListing::where('vehicle_id', $vehicleId)->exists();
        
        // Check if user has permission to feature vehicles
        $canFeature = $user->can('vehicle.seller.feature');

        return view('sell-your-car-success', [
            'vehicle' => $vehicle,
            'isFeatured' => $isFeatured,
            'canFeature' => $canFeature,
            'token' => $token, // Pass token to view for feature button
        ]);
    }

    /**
     * Feature a vehicle listing
     */
    public function feature(Request $request, string $token)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'You must be logged in to feature a vehicle.'
            ], 401);
        }

        // Check permission to feature vehicles
        if (!$user->can('vehicle.feature')) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to feature vehicles. Please contact your administrator.'
            ], 403);
        }

        // Verify token and get vehicle_id
        $tokenData = $this->verifySuccessToken($token, $user->id);
        
        if (!$tokenData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired access token.'
            ], 403);
        }

        $vehicleId = $tokenData['vehicle_id'];
        
        try {
            $vehicle = Vehicle::findOrFail($vehicleId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vehicle not found. It may have been deleted.'
            ], 404);
        }

        // Verify user owns this vehicle (double check)
        if ($vehicle->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to feature this vehicle.'
            ], 403);
        }

        // Check if vehicle is soft-deleted
        if ($vehicle->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot feature a deleted vehicle listing.'
            ], 400);
        }

        // Check if already featured
        $existingFeatured = FeaturedListing::where('vehicle_id', $vehicleId)->first();
        if ($existingFeatured) {
            return response()->json([
                'status' => 'success',
                'message' => 'Vehicle is already featured.',
                'already_featured' => true
            ]);
        }

        // Get max sort order and add 1
        $maxSortOrder = FeaturedListing::max('sort_order') ?? 0;
        $sortOrder = $maxSortOrder + 1;

        // Create featured listing
        $featuredListing = FeaturedListing::create([
            'vehicle_id' => $vehicleId,
            'sort_order' => $sortOrder,
        ]);

        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Vehicle featured successfully!',
                'featured_listing' => $featuredListing
            ]);
        }

        return redirect()->route('sell-your-car.success', ['token' => $token])
            ->with('success', 'Vehicle featured successfully!');
    }
}

