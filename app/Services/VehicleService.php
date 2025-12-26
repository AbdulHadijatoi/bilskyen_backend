<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\VehicleDetail;
use App\Models\FuelType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ModelYear;
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
            // Fetch from Nummerplade API if registration or VIN is provided
            $registration = $vehicleData['registration'] ?? null;
            $vin = $vehicleData['vin'] ?? null;

            if ($registration || $vin) {
                try {
                    $nummerpladeData = $this->fetchVehicleDataFromNummerplade($registration, $vin);
                    $vehicleData = $this->transformNummerpladeData($nummerpladeData, $vehicleData);
                } catch (NummerpladeApiException $e) {
                    // Log error but don't fail - allow user to create vehicle manually
                    Log::warning('Failed to fetch vehicle data from Nummerplade API', [
                        'registration' => $registration,
                        'vin' => $vin,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with manual data entry
                }
            }

            // Separate vehicle details if present
            $vehicleDetailsData = [];
            $detailsFields = [
                'description', 'views_count', 'vin_location', 'type', 'version', 'type_name',
                'registration_status', 'registration_status_updated_date', 'expire_date',
                'status_updated_date', 'model_year', 'total_weight', 'vehicle_weight',
                'technical_total_weight', 'coupling', 'towing_weight_brakes', 'minimum_weight',
                'gross_combination_weight', 'fuel_efficiency', 'engine_displacement',
                'engine_cylinders', 'engine_code', 'category', 'last_inspection_date',
                'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
                'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
                'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
                'leasing_period_end', 'use', 'color', 'body_type', 'dispensations',
                'permits', 'equipment', 'ncap_five', 'airbags', 'integrated_child_seats',
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
                        // Already a path/URL
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $file,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Upload file
                        $this->fileService->validateFile($file);
                        $uploadedPath = $this->fileService->uploadFiles([$file], 'public', 'vehicles')[0];
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $uploadedPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
            }

            return $vehicle->fresh(['images', 'details']);
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
            // Separate vehicle details if present
            $vehicleDetailsData = [];
            $detailsFields = [
                'description', 'views_count', 'vin_location', 'type', 'version', 'type_name',
                'registration_status', 'registration_status_updated_date', 'expire_date',
                'status_updated_date', 'model_year', 'total_weight', 'vehicle_weight',
                'technical_total_weight', 'coupling', 'towing_weight_brakes', 'minimum_weight',
                'gross_combination_weight', 'fuel_efficiency', 'engine_displacement',
                'engine_cylinders', 'engine_code', 'category', 'last_inspection_date',
                'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
                'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
                'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
                'leasing_period_end', 'use', 'color', 'body_type', 'dispensations',
                'permits', 'equipment', 'ncap_five', 'airbags', 'integrated_child_seats',
                'seat_belt_alarms', 'euronorm'
            ];

            foreach ($detailsFields as $field) {
                if (isset($vehicleData[$field])) {
                    $vehicleDetailsData[$field] = $vehicleData[$field];
                    unset($vehicleData[$field]);
                }
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
                // Delete old images
                $oldImages = $vehicle->images;
                foreach ($oldImages as $oldImage) {
                    $this->fileService->deleteFiles([$oldImage->image_path]);
                    $oldImage->delete();
                }

                // Upload and create new images
                $sortOrder = 0;
                foreach ($vehicleData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a path/URL
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $file,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Upload file
                        $this->fileService->validateFile($file);
                        $uploadedPath = $this->fileService->uploadFiles([$file], 'public', 'vehicles')[0];
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $uploadedPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
                unset($vehicleData['images']);
            }

            // Update vehicle
            $vehicle->update($vehicleData);

            return $vehicle->fresh(['images', 'details']);
        });
    }

    /**
     * Delete a vehicle
     */
    public function deleteVehicle(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            // Delete vehicle images
            $images = $vehicle->images;
            foreach ($images as $image) {
                $this->fileService->deleteFiles([$image->image_path]);
            }

            // Delete vehicle (soft delete)
            $vehicle->delete();
        });
    }
}

