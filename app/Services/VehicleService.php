<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\VehicleDetail;
use App\Models\FuelType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ModelYear;
use App\Models\VehicleModel;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Type;
use App\Models\VehicleUse;
use App\Constants\VehicleListStatus;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\NummerpladeApiService;
use App\Exceptions\NummerpladeApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleService
{
    public function __construct(
        private FileService $fileService,
        private NotificationService $notificationService,
        private NummerpladeApiService $nummerpladeService
    ) {}

    /**
     * Create a vehicle
     * Fetches data from Nummerplade API if registration or VIN is provided
     */
    public function createVehicle(array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicleData) {
            // Handle VehicleModel creation if model name is provided but model_id is not
            if (isset($vehicleData['model_name']) && !isset($vehicleData['model_id']) && isset($vehicleData['brand_id'])) {
                $model = VehicleModel::firstOrCreate(
                    [
                        'brand_id' => $vehicleData['brand_id'],
                        'name' => $vehicleData['model_name']
                    ]
                );
                $vehicleData['model_id'] = $model->id;
                unset($vehicleData['model_name']);
            }

            // Handle ModelYear creation if model year name is provided but model_year_id is not
            if (isset($vehicleData['model_year_name']) && !isset($vehicleData['model_year_id'])) {
                $modelYear = ModelYear::firstOrCreate(
                    ['name' => (string) $vehicleData['model_year_name']]
                );
                $vehicleData['model_year_id'] = $modelYear->id;
                unset($vehicleData['model_year_name']);
            }

            // Also handle if model_year is provided as a number
            if (isset($vehicleData['model_year']) && !isset($vehicleData['model_year_id'])) {
                $modelYear = ModelYear::firstOrCreate(
                    ['name' => (string) $vehicleData['model_year']]
                );
                $vehicleData['model_year_id'] = $modelYear->id;
                unset($vehicleData['model_year']);
            }

            // Separate equipment IDs if present
            $equipmentIds = null;
            if (isset($vehicleData['equipment_ids']) && is_array($vehicleData['equipment_ids'])) {
                $equipmentIds = $vehicleData['equipment_ids'];
                unset($vehicleData['equipment_ids']);
            } elseif (isset($vehicleData['equipment']) && is_array($vehicleData['equipment'])) {
                // Support legacy 'equipment' key for backward compatibility
                $equipmentIds = $vehicleData['equipment'];
                unset($vehicleData['equipment']);
            }

            // Separate vehicle details if present
            $vehicleDetailsData = [];
            $detailsFields = [
                'description', 'views_count', 'vin_location', 'type_id', 'version', 'type_name',
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
                'seat_belt_alarms', 'euronorm'
            ];

            foreach ($detailsFields as $field) {
                if (isset($vehicleData[$field])) {
                    $vehicleDetailsData[$field] = $vehicleData[$field];
                    unset($vehicleData[$field]);
                }
            }

            // Create vehicle
            $vehicle = Vehicle::create($vehicleData);

            // Sync equipment if provided
            if ($equipmentIds !== null) {
                $vehicle->equipment()->sync($equipmentIds);
            }

            // Create vehicle details if provided
            if (!empty($vehicleDetailsData)) {
                $vehicleDetailsData['vehicle_id'] = $vehicle->id;
                VehicleDetail::create($vehicleDetailsData);
            }

            // Handle file uploads if present
            if (isset($vehicleData['images']) && is_array($vehicleData['images'])) {
                $sortOrder = 0;
                foreach ($vehicleData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a path/URL - try to generate thumbnail if it doesn't exist
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($file, 300, 300, 'public');
                            // Extract path from URL
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $file,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Upload file with thumbnail generation
                        $this->fileService->validateFile($file);
                        $uploadedPath = $this->fileService->uploadFiles(
                            [$file], 
                            'public', 
                            'vehicles',
                            true, // createThumbnails
                            false, // optimizeImages
                            300, // thumbnailWidth
                            300  // thumbnailHeight
                        )[0];
                        
                        // Extract thumbnail path from URL
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($uploadedPath, 300, 300, 'public');
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $uploadedPath,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
            }

            return $vehicle->fresh(['images', 'details', 'equipment']);
        });
    }

    /**
     * Fetch vehicle data from Nummerplade API
     * Accepts either registration or VIN
     */
    public function fetchVehicleDataFromNummerplade(?string $registration = null, ?string $vin = null): array
    {
        if ($registration) {
            return $this->nummerpladeService->getVehicleByRegistration($registration, true);
        }

        if ($vin) {
            return $this->nummerpladeService->getVehicleByVin($vin, true);
        }

        throw new \InvalidArgumentException('Either registration or VIN must be provided');
    }

    /**
     * Transform Nummerplade API response to match our database schema
     */
    protected function transformNummerpladeData(array $apiData, array $existingData = []): array
    {
        $transformed = $existingData;

        // Map Nummerplade fields to our database fields
        // Note: Field mapping depends on actual Nummerplade API response structure
        // This is a template - adjust based on actual API response

        // Lookup brand_id from brands table
        if (isset($apiData['make']) || isset($apiData['brand'])) {
            $brandName = $apiData['make'] ?? $apiData['brand'];
            $brand = Brand::where('name', $brandName)->first();
            if ($brand) {
                $transformed['brand_id'] = $brand->id;
            }
        }

        // Lookup model_year_id from model_years table
        if (isset($apiData['year']) || isset($apiData['modelYear'])) {
            $year = $apiData['year'] ?? $apiData['modelYear'];
            $modelYear = ModelYear::where('name', (string) $year)->first();
            if ($modelYear) {
                $transformed['model_year_id'] = $modelYear->id;
            }
        }

        // Lookup category_id from categories table
        if (isset($apiData['category']) || isset($apiData['vehicleType'])) {
            $categoryName = $apiData['category'] ?? $apiData['vehicleType'];
            $category = Category::where('name', $categoryName)->first();
            if ($category) {
                $transformed['category_id'] = $category->id;
            }
        }

        // Lookup fuel_type_id from fuel_types table
        if (isset($apiData['fuelType'])) {
            $fuelType = FuelType::where('name', $apiData['fuelType'])->first();
            if ($fuelType) {
                $transformed['fuel_type_id'] = $fuelType->id;
            }
        }

        // Map mileage/km_driven
        if (isset($apiData['mileage'])) {
            $transformed['mileage'] = $apiData['mileage'];
            $transformed['km_driven'] = $apiData['mileage'];
        }

        if (isset($apiData['kmDriven'])) {
            $transformed['km_driven'] = $apiData['kmDriven'];
            if (!isset($transformed['mileage'])) {
                $transformed['mileage'] = $apiData['kmDriven'];
            }
        }

        // Map other vehicle specifications
        if (isset($apiData['batteryCapacity'])) {
            $transformed['battery_capacity'] = $apiData['batteryCapacity'];
        }

        if (isset($apiData['enginePower'])) {
            $transformed['engine_power'] = $apiData['enginePower'];
        }

        if (isset($apiData['towingWeight'])) {
            $transformed['towing_weight'] = $apiData['towingWeight'];
        }

        if (isset($apiData['ownershipTax'])) {
            $transformed['ownership_tax'] = $apiData['ownershipTax'];
        }

        if (isset($apiData['firstRegistrationDate'])) {
            $transformed['first_registration_date'] = $apiData['firstRegistrationDate'];
        }

        if (isset($apiData['price'])) {
            $transformed['price'] = $apiData['price'];
        }

        // Store registration and VIN
        if (isset($apiData['registration'])) {
            $transformed['registration'] = $apiData['registration'];
        }

        if (isset($apiData['vin'])) {
            $transformed['vin'] = $apiData['vin'];
        }

        // Store title if available
        if (isset($apiData['title'])) {
            $transformed['title'] = $apiData['title'];
        } elseif (isset($apiData['make']) && isset($apiData['model'])) {
            // Generate title from make and model
            $transformed['title'] = ($apiData['make'] ?? '') . ' ' . ($apiData['model'] ?? '');
        }

        return $transformed;
    }

    /**
     * Update a vehicle
     */
    public function updateVehicle(Vehicle $vehicle, array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $vehicleData) {
            // Separate equipment IDs if present
            $equipmentIds = null;
            if (isset($vehicleData['equipment_ids']) && is_array($vehicleData['equipment_ids'])) {
                $equipmentIds = $vehicleData['equipment_ids'];
                unset($vehicleData['equipment_ids']);
            } elseif (isset($vehicleData['equipment']) && is_array($vehicleData['equipment'])) {
                // Support legacy 'equipment' key for backward compatibility
                $equipmentIds = $vehicleData['equipment'];
                unset($vehicleData['equipment']);
            }

            // Separate vehicle details if present
            $vehicleDetailsData = [];
            $detailsFields = [
                'description', 'views_count', 'vin_location', 'type_id', 'version', 'type_name',
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
                'seat_belt_alarms', 'euronorm'
            ];

            foreach ($detailsFields as $field) {
                if (isset($vehicleData[$field])) {
                    $vehicleDetailsData[$field] = $vehicleData[$field];
                    unset($vehicleData[$field]);
                }
            }

            // Sync equipment if provided
            if ($equipmentIds !== null) {
                $vehicle->equipment()->sync($equipmentIds);
            }

            // Update vehicle details if provided
            if (!empty($vehicleDetailsData)) {
                $details = $vehicle->details;
                if ($details) {
                    $details->update($vehicleDetailsData);
                } else {
                    $vehicleDetailsData['vehicle_id'] = $vehicle->id;
                    VehicleDetail::create($vehicleDetailsData);
                }
            }

            // Handle image updates if provided
            if (isset($vehicleData['images']) && is_array($vehicleData['images'])) {
                // Delete old images and thumbnails
                $oldImages = $vehicle->images;
                foreach ($oldImages as $oldImage) {
                    $this->fileService->deleteFiles([$oldImage->image_path]);
                    if ($oldImage->thumbnail_path) {
                        $this->fileService->deleteFiles([$oldImage->thumbnail_path]);
                    }
                    $oldImage->delete();
                }

                // Upload and create new images
                $sortOrder = 0;
                foreach ($vehicleData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a path/URL - try to generate thumbnail if it doesn't exist
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($file, 300, 300, 'public');
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $file,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Upload file with thumbnail generation
                        $this->fileService->validateFile($file);
                        $uploadedPath = $this->fileService->uploadFiles(
                            [$file], 
                            'public', 
                            'vehicles',
                            true, // createThumbnails
                            false, // optimizeImages
                            300, // thumbnailWidth
                            300  // thumbnailHeight
                        )[0];
                        
                        // Extract thumbnail path from URL
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($uploadedPath, 300, 300, 'public');
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $uploadedPath,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
                unset($vehicleData['images']);
            }

            // Update vehicle
            $vehicle->update($vehicleData);

            return $vehicle->fresh(['images', 'details', 'equipment']);
        });
    }

    /**
     * Delete a vehicle
     */
    public function deleteVehicle(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            // Delete vehicle images and thumbnails
            $images = $vehicle->images;
            foreach ($images as $image) {
                $this->fileService->deleteFiles([$image->image_path]);
                if ($image->thumbnail_path) {
                    $this->fileService->deleteFiles([$image->thumbnail_path]);
                }
            }

            // Delete vehicle (soft delete)
            $vehicle->delete();
        });
    }

    /**
     * Get public vehicles with basic filters (vehicles table only)
     * 
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicVehicles(array $filters = [], int $perPage = 15, int $page = 1)
    {
        $query = Vehicle::query()
            ->where('vehicle_list_status_id', VehicleListStatus::PUBLISHED)
            ->with(['images' => function ($query) {
                $query->orderBy('sort_order');
            }, 'details']);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('registration', 'like', "%{$search}%")
                  ->orWhere('vin', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Brand filter
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Model filter
        if (!empty($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        // Model Year filter
        if (!empty($filters['model_year_id'])) {
            $query->where('model_year_id', $filters['model_year_id']);
        }

        // Fuel Type filter (supports array for multiple values)
        if (!empty($filters['fuel_type_id'])) {
            if (is_array($filters['fuel_type_id'])) {
                $query->whereIn('fuel_type_id', $filters['fuel_type_id']);
            } else {
                $query->where('fuel_type_id', $filters['fuel_type_id']);
            }
        }

        // Kilometers Driven filter
        if (!empty($filters['km_driven'])) {
            $query->where('km_driven', $filters['km_driven']);
        }

        // Price range filter
        if (!empty($filters['price_from'])) {
            $query->where('price', '>=', $filters['price_from']);
        }
        if (!empty($filters['price_to'])) {
            $query->where('price', '<=', $filters['price_to']);
        }

        // Listing Type filter
        if (!empty($filters['listing_type_id'])) {
            $query->where('listing_type_id', $filters['listing_type_id']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get public vehicles with advanced filters (vehicles and vehicle_details tables)
     * 
     * @param array $basicFilters
     * @param array $advancedFilters
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicVehiclesWithAdvancedFilters(array $basicFilters = [], array $advancedFilters = [], int $perPage = 15, int $page = 1)
    {
        // Start with base query
        $query = Vehicle::query()
            ->where('vehicles.vehicle_list_status_id', VehicleListStatus::PUBLISHED);

        // Apply basic filters first
        if (!empty($basicFilters['search'])) {
            $search = $basicFilters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('vehicles.title', 'like', "%{$search}%")
                  ->orWhere('vehicles.registration', 'like', "%{$search}%")
                  ->orWhere('vehicles.vin', 'like', "%{$search}%");
            });
        }

        if (!empty($basicFilters['category_id'])) {
            $query->where('vehicles.category_id', $basicFilters['category_id']);
        }

        if (!empty($basicFilters['brand_id'])) {
            $query->where('vehicles.brand_id', $basicFilters['brand_id']);
        }

        if (!empty($basicFilters['model_id'])) {
            $query->where('vehicles.model_id', $basicFilters['model_id']);
        }

        if (!empty($basicFilters['model_year_id'])) {
            $query->where('vehicles.model_year_id', $basicFilters['model_year_id']);
        }

        if (!empty($basicFilters['fuel_type_id'])) {
            if (is_array($basicFilters['fuel_type_id'])) {
                $query->whereIn('vehicles.fuel_type_id', $basicFilters['fuel_type_id']);
            } else {
                $query->where('vehicles.fuel_type_id', $basicFilters['fuel_type_id']);
            }
        }

        if (!empty($basicFilters['km_driven'])) {
            $query->where('vehicles.km_driven', $basicFilters['km_driven']);
        }

        if (!empty($basicFilters['price_from'])) {
            $query->where('vehicles.price', '>=', $basicFilters['price_from']);
        }
        if (!empty($basicFilters['price_to'])) {
            $query->where('vehicles.price', '<=', $basicFilters['price_to']);
        }

        if (!empty($basicFilters['listing_type_id'])) {
            $query->where('vehicles.listing_type_id', $basicFilters['listing_type_id']);
        }

        // Join with vehicle_details for advanced filters
        $query->leftJoin('vehicle_details', 'vehicles.id', '=', 'vehicle_details.vehicle_id');

        // Apply advanced filters
        // Make (brand name lookup)
        if (!empty($advancedFilters['make']) && empty($basicFilters['brand_id'])) {
            $brand = Brand::where('name', 'like', "%{$advancedFilters['make']}%")->first();
            if ($brand) {
                $query->where('vehicles.brand_id', $brand->id);
            }
        }

        // Mileage range
        if (!empty($advancedFilters['mileage_from'])) {
            $query->where('vehicles.mileage', '>=', $advancedFilters['mileage_from']);
        }
        if (!empty($advancedFilters['mileage_to'])) {
            $query->where('vehicles.mileage', '<=', $advancedFilters['mileage_to']);
        }
        if (!empty($advancedFilters['odometer_from'])) {
            $query->where('vehicles.mileage', '>=', $advancedFilters['odometer_from']);
        }
        if (!empty($advancedFilters['odometer_to'])) {
            $query->where('vehicles.mileage', '<=', $advancedFilters['odometer_to']);
        }

        // Listing Status
        if (!empty($advancedFilters['vehicle_list_status_id'])) {
            $query->where('vehicles.vehicle_list_status_id', $advancedFilters['vehicle_list_status_id']);
        }

        // Vehicle Body Type (supports array for multiple values)
        if (!empty($advancedFilters['body_type_id'])) {
            if (is_array($advancedFilters['body_type_id'])) {
                $query->whereIn('vehicle_details.body_type_id', $advancedFilters['body_type_id']);
            } else {
                $query->where('vehicle_details.body_type_id', $advancedFilters['body_type_id']);
            }
        }

        // Drive Wheels (supports array for multiple values)
        if (!empty($advancedFilters['drive_axles'])) {
            if (is_array($advancedFilters['drive_axles'])) {
                $query->whereIn('vehicle_details.drive_axles', $advancedFilters['drive_axles']);
            } else {
                $query->where('vehicle_details.drive_axles', $advancedFilters['drive_axles']);
            }
        }

        // First Registration Year
        if (!empty($advancedFilters['first_registration_year_from'])) {
            $query->whereYear('vehicles.first_registration_date', '>=', $advancedFilters['first_registration_year_from']);
        }
        if (!empty($advancedFilters['first_registration_year_to'])) {
            $query->whereYear('vehicles.first_registration_date', '<=', $advancedFilters['first_registration_year_to']);
        }

        // Seller Type / Dealer
        if (!empty($advancedFilters['dealer_id'])) {
            $query->where('vehicles.dealer_id', $advancedFilters['dealer_id']);
        }

        // Price Type (supports array for multiple values)
        if (!empty($advancedFilters['price_type_id'])) {
            if (is_array($advancedFilters['price_type_id'])) {
                $query->whereIn('vehicle_details.price_type_id', $advancedFilters['price_type_id']);
            } else {
                $query->where('vehicle_details.price_type_id', $advancedFilters['price_type_id']);
            }
        }

        // Condition
        if (!empty($advancedFilters['condition_id'])) {
            $query->where('vehicle_details.condition_id', $advancedFilters['condition_id']);
        }

        // Gear Type (supports array for multiple values)
        if (!empty($advancedFilters['gear_type_id'])) {
            if (is_array($advancedFilters['gear_type_id'])) {
                $query->whereIn('vehicle_details.gear_type_id', $advancedFilters['gear_type_id']);
            } else {
                $query->where('vehicle_details.gear_type_id', $advancedFilters['gear_type_id']);
            }
        }

        // Sales Type (supports array for multiple values)
        if (!empty($advancedFilters['sales_type_id'])) {
            if (is_array($advancedFilters['sales_type_id'])) {
                $query->whereIn('vehicle_details.sales_type_id', $advancedFilters['sales_type_id']);
            } else {
                $query->where('vehicle_details.sales_type_id', $advancedFilters['sales_type_id']);
            }
        }

        // Performance - Top Speed
        if (!empty($advancedFilters['top_speed_from'])) {
            $query->where('vehicle_details.top_speed', '>=', $advancedFilters['top_speed_from']);
        }
        if (!empty($advancedFilters['top_speed_to'])) {
            $query->where('vehicle_details.top_speed', '<=', $advancedFilters['top_speed_to']);
        }

        // Performance - Engine Power
        if (!empty($advancedFilters['engine_power_from'])) {
            $query->where('vehicles.engine_power', '>=', $advancedFilters['engine_power_from']);
        }
        if (!empty($advancedFilters['engine_power_to'])) {
            $query->where('vehicles.engine_power', '<=', $advancedFilters['engine_power_to']);
        }

        // Battery & Charging (EV)
        if (!empty($advancedFilters['battery_capacity_from'])) {
            $query->where('vehicles.battery_capacity', '>=', $advancedFilters['battery_capacity_from']);
        }
        if (!empty($advancedFilters['battery_capacity_to'])) {
            $query->where('vehicles.battery_capacity', '<=', $advancedFilters['battery_capacity_to']);
        }

        // Economy & Environment
        if (!empty($advancedFilters['fuel_efficiency_from'])) {
            $query->where('vehicle_details.fuel_efficiency', '>=', $advancedFilters['fuel_efficiency_from']);
        }
        if (!empty($advancedFilters['fuel_efficiency_to'])) {
            $query->where('vehicle_details.fuel_efficiency', '<=', $advancedFilters['fuel_efficiency_to']);
        }
        if (!empty($advancedFilters['euronorm'])) {
            $query->where('vehicle_details.euronorm', $advancedFilters['euronorm']);
        }

        // Physical Details
        if (!empty($advancedFilters['color_id'])) {
            $query->where('vehicle_details.color_id', $advancedFilters['color_id']);
        }
        if (!empty($advancedFilters['doors'])) {
            $query->where('vehicle_details.doors', $advancedFilters['doors']);
        }
        if (!empty($advancedFilters['seats_min'])) {
            $query->where('vehicle_details.minimum_seats', '>=', $advancedFilters['seats_min']);
        }
        if (!empty($advancedFilters['seats_max'])) {
            $query->where('vehicle_details.maximum_seats', '<=', $advancedFilters['seats_max']);
        }
        if (!empty($advancedFilters['weight_from'])) {
            $query->where(function ($q) use ($advancedFilters) {
                $q->where('vehicle_details.vehicle_weight', '>=', $advancedFilters['weight_from'])
                  ->orWhere('vehicle_details.total_weight', '>=', $advancedFilters['weight_from']);
            });
        }
        if (!empty($advancedFilters['weight_to'])) {
            $query->where(function ($q) use ($advancedFilters) {
                $q->where('vehicle_details.vehicle_weight', '<=', $advancedFilters['weight_to'])
                  ->orWhere('vehicle_details.total_weight', '<=', $advancedFilters['weight_to']);
            });
        }
        if (!empty($advancedFilters['wheels'])) {
            $query->where('vehicle_details.wheels', $advancedFilters['wheels']);
        }
        if (!empty($advancedFilters['axles'])) {
            $query->where('vehicle_details.axles', $advancedFilters['axles']);
        }
        if (!empty($advancedFilters['engine_cylinders'])) {
            $query->where('vehicle_details.engine_cylinders', $advancedFilters['engine_cylinders']);
        }
        if (!empty($advancedFilters['engine_displacement_from'])) {
            $query->where('vehicle_details.engine_displacement', '>=', $advancedFilters['engine_displacement_from']);
        }
        if (!empty($advancedFilters['engine_displacement_to'])) {
            $query->where('vehicle_details.engine_displacement', '<=', $advancedFilters['engine_displacement_to']);
        }
        if (!empty($advancedFilters['airbags'])) {
            $query->where('vehicle_details.airbags', $advancedFilters['airbags']);
        }
        if (isset($advancedFilters['ncap_five'])) {
            $query->where('vehicle_details.ncap_five', (bool) $advancedFilters['ncap_five']);
        }

        // Equipment (many-to-many)
        if (!empty($advancedFilters['equipment_ids']) || !empty($advancedFilters['equipment_id'])) {
            $equipmentIds = $advancedFilters['equipment_ids'] ?? [$advancedFilters['equipment_id']];
            if (is_array($equipmentIds) && !empty($equipmentIds)) {
                $query->whereHas('equipment', function ($q) use ($equipmentIds) {
                    $q->whereIn('equipments.id', $equipmentIds);
                });
            }
        }

        // Select distinct vehicles to avoid duplicates from joins
        $query->select('vehicles.*')
              ->distinct();

        // Eager load relationships
        $query->with(['images' => function ($query) {
            $query->orderBy('sort_order');
        }, 'details']);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}

