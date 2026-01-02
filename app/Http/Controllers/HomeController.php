<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Category;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\ModelYear;
use App\Models\ListingType;
use App\Models\PriceType;
use App\Models\BodyType;
use App\Models\GearType;
use App\Models\FuelType;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Condition;
use App\Models\SalesType;
use App\Services\AuthService;
use App\Services\VehicleService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private VehicleService $vehicleService
    ) {}

    /**
     * Show the home page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Fetch filter options for the view
        $filterOptions = [
            'categories' => Category::orderBy('name')->get(),
            'listingTypes' => ListingType::orderBy('name')->get(),
            'priceTypes' => PriceType::orderBy('name')->get(),
            'bodyTypes' => BodyType::orderBy('name')->get(),
            'gearTypes' => GearType::orderBy('name')->get(),
            'fuelTypes' => FuelType::orderBy('name')->get(),
            'equipment' => Equipment::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'models' => VehicleModel::orderBy('name')->get(),
            'modelYears' => ModelYear::orderBy('name', 'desc')->get(),
            'conditions' => Condition::orderBy('name')->get(),
            'salesTypes' => SalesType::orderBy('name')->get(),
        ];

        // Popular brands (most common brands - can be customized)
        $popularBrandNames = ['Volvo', 'BMW', 'Mercedes-Benz', 'Audi', 'VW', 'Toyota', 'Ford', 'Peugeot', 'Opel', 'Skoda', 'Nissan', 'Hyundai', 'Kia', 'Mazda', 'Honda'];
        $filterOptions['popularBrands'] = Brand::whereIn('name', $popularBrandNames)->orderBy('name')->get();

        return view('home', [
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Show the profile page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showProfile(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        return view('profile', [
            'user' => $user,
        ]);
    }

    /**
     * Update user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);

        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        // Update user profile
        $user->name = $validated['name'];
        $user->email = strtolower($validated['email']);
        $user->phone = $validated['phone'] ?? null;
        $user->address = $validated['address'] ?? null;
        $user->save();

        return redirect('/profile')->with('status', 'Profile updated successfully!');
    }

    /**
     * Show the about page
     *
     * @return \Illuminate\View\View
     */
    public function showAbout()
    {
        return view('about');
    }

    /**
     * Show the contact page
     *
     * @return \Illuminate\View\View
     */
    public function showContact()
    {
        return view('contact');
    }

    /**
     * Show the vehicles listing page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showVehicles(Request $request)
    {
        // Define advanced filter keys (vehicles and vehicle_details table attributes)
        $advancedFilterKeys = [
            // Price, Make, Model, Model Year, Mileage, Listing Type, Category
            'price_from', 'price_to', 'make', 'brand_id', 'model_id', 'model_year_id',
            'year_from', 'year_to', 'mileage_from', 'mileage_to', 
            'odometer_from', 'odometer_to', 'listing_type_id', 'vehicle_list_status_id',
            'category_id', 'price_type_id', 'condition_id',
            // Vehicle Body Type, Fuel Type, Gear Type, Drive Wheels
            'body_type_id', 'fuel_type_id', 'gear_type_id', 'drive_axles',
            // First Registration Year, Seller Type, Sales Type, Seller Distance
            'first_registration_year_from', 'first_registration_year_to',
            'seller_type', 'dealer_id', 'sales_type_id', 'seller_distance',
            // Performance
            'top_speed_from', 'top_speed_to', 'engine_power_from', 'engine_power_to',
            // Owner Tax
            'ownership_tax_from', 'ownership_tax_to',
            // Battery & Charging (EV)
            'battery_capacity_from', 'battery_capacity_to', 'range_km_from', 'range_km_to', 'charging_type',
            // Economy & Environment
            'fuel_efficiency_from', 'fuel_efficiency_to', 'euronorm',
            // Physical Details
            'color_id', 'doors', 'seats_min', 'seats_max', 'weight_from', 'weight_to',
            'wheels', 'axles', 'engine_cylinders', 'engine_displacement_from', 
            'engine_displacement_to', 'airbags', 'ncap_five',
            // Equipment
            'equipment_ids', 'equipment_id'
        ];
        
        // Check if any advanced filters are present
        $hasAdvancedFilters = $request->hasAny($advancedFilterKeys);
        
        // Basic filter keys (vehicles table attributes)
        $basicFilterKeys = [
            'search', 'category_id', 'brand_id', 'model_id', 'model_year_id', 
            'fuel_type_id', 'km_driven', 'price_from', 'price_to', 'listing_type_id', 'sort'
        ];
        
        if ($hasAdvancedFilters) {
            // Use advanced filtering method
            $basicFilters = $request->only($basicFilterKeys);
            $advancedFilters = $request->only($advancedFilterKeys);
            
            $vehicles = $this->vehicleService->getPublicVehiclesWithAdvancedFilters(
                $basicFilters,
                $advancedFilters,
                $request->input('limit', 15),
                $request->input('page', 1)
            );
        } else {
            // Use basic filtering method (faster, most common)
            $filters = $request->only($basicFilterKeys);
            
            $vehicles = $this->vehicleService->getPublicVehicles(
                $filters,
                $request->input('limit', 15),
                $request->input('page', 1)
            );
        }

        // Fetch filter options for the view
        $filterOptions = [
            'categories' => Category::orderBy('name')->get(),
            'listingTypes' => ListingType::orderBy('name')->get(),
            'priceTypes' => PriceType::orderBy('name')->get(),
            'bodyTypes' => BodyType::orderBy('name')->get(),
            'gearTypes' => GearType::orderBy('name')->get(),
            'fuelTypes' => FuelType::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'modelYears' => ModelYear::orderBy('name', 'desc')->get(),
            'conditions' => Condition::orderBy('name')->get(),
            'salesTypes' => SalesType::orderBy('name')->get(),
        ];

        // Popular brands (most common brands - can be customized)
        $popularBrandNames = ['Volvo', 'BMW', 'Mercedes-Benz', 'Audi', 'VW', 'Toyota', 'Ford', 'Peugeot', 'Opel', 'Skoda', 'Nissan', 'Hyundai', 'Kia', 'Mazda', 'Honda'];
        $filterOptions['popularBrands'] = Brand::whereIn('name', $popularBrandNames)->orderBy('name')->get();

        // Filter models by selected brand if provided
        $selectedBrandId = $request->input('brand_id');
        if ($selectedBrandId) {
            $filterOptions['models'] = VehicleModel::where('brand_id', $selectedBrandId)->orderBy('name')->get();
        } else {
            $filterOptions['models'] = VehicleModel::orderBy('name')->get();
        }

        // Group equipment by equipment type
        $equipmentTypes = EquipmentType::with(['equipments' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();
        
        $filterOptions['equipmentTypes'] = $equipmentTypes;

        return view('vehicles', [
            'vehicles' => $vehicles,
            'filterOptions' => $filterOptions,
            'currentFilters' => $request->all(),
        ]);
    }

    /**
     * Show the vehicle detail page
     *
     * @param int $serialNo
     * @return \Illuminate\View\View
     */
    public function showVehicleDetail($serialNo)
    {
        $vehicle = Vehicle::with([
            'details',
            'equipment',
            'location',
            'listingType',
            'images'
        ])->findOrFail($serialNo);

        return view('vehicle-detail', [
            'vehicle' => $vehicle,
        ]);
    }
}

