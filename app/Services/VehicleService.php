<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use App\Models\VehicleDetail;
use App\Models\DmrBrand;
use App\Models\DmrDriveEnergy;
use App\Models\DmrModel;
use App\Models\FuelType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ModelYear;
use App\Models\VehicleModel;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\Type;
use App\Models\VehicleUse;
use App\Models\Variant;
use App\Models\Transmission;
use App\Models\Euronom;
use App\Constants\VehicleListStatus;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\DmrFactVehicleLookupService;
use App\Services\NummerpladeApiService;
use App\Services\OwnershipTaxService;
use App\Exceptions\NummerpladeApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleService
{
    public function __construct(
        private FileService $fileService,
        private NotificationService $notificationService,
        private NummerpladeApiService $nummerpladeService,
        private DmrFactVehicleLookupService $dmrFactVehicleLookupService,
        private OwnershipTaxService $ownershipTaxService,
    ) {}

    /**
     * Create a vehicle (DMR-linked listing row).
     */
    public function createVehicle(array $vehicleData): Vehicle
    {
        $equipmentIds = null;
        if (isset($vehicleData['equipment_ids']) && is_array($vehicleData['equipment_ids'])) {
            $equipmentIds = $vehicleData['equipment_ids'];
            unset($vehicleData['equipment_ids']);
        } elseif (isset($vehicleData['equipment']) && is_array($vehicleData['equipment'])) {
            $equipmentIds = $vehicleData['equipment'];
            unset($vehicleData['equipment']);
        }

        $extraDescription = '';
        $detailsFields = [
            'description', 'vin_location', 'vehicle_external_id', 'type_id', 'type_name',
            'registration_status', 'registration_status_updated_date', 'expire_date',
            'status_updated_date', 'total_weight', 'vehicle_weight',
            'technical_total_weight', 'coupling', 'towing_weight_brakes', 'minimum_weight',
            'gross_combination_weight', 'engine_displacement',
            'engine_cylinders', 'engine_code', 'category', 'last_inspection_date',
            'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
            'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
            'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
            'leasing_period_end', 'use_id', 'color_id', 'body_type_id', 'variant_id',
            'dispensations', 'permits', 'ncap_five', 'airbags', 'integrated_child_seats',
            'seat_belt_alarms', 'euronom_id', 'servicebog', 'price_type_id', 'condition_id',
            'sales_type_id', 'seller_phone', 'annual_tax', 'owners',
            'production_date', 'cover_image_index', 'fuel_consumption_wltp', 'fuel_consumption_nedc',
            'co2_emissions', 'is_import', 'is_factory_new', 'transmission_id', 'transmission_name',
            'wholesale_price', 'internal_cost_price', 'engine_type',
        ];
        foreach ($detailsFields as $field) {
            if (isset($vehicleData[$field])) {
                if ($field === 'description' && empty($vehicleData['description'])) {
                    $vehicleData['description'] = (string) $vehicleData[$field];
                } elseif ($field === 'description') {
                    $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '') . "\n\n" . (string) $vehicleData[$field]);
                } elseif ($field === 'condition_id' && ! isset($vehicleData['condition_id'])) {
                    $vehicleData['condition_id'] = $vehicleData[$field];
                } elseif ($field === 'servicebog' && ! isset($vehicleData['servicebog'])) {
                    $vehicleData['servicebog'] = $vehicleData[$field];
                } else {
                    $extraDescription .= $field . ': ' . json_encode($vehicleData[$field]) . "\n";
                }
                unset($vehicleData[$field]);
            }
        }
        if ($extraDescription !== '') {
            $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '') . "\n\n" . $extraDescription);
        }

        $images = $vehicleData['images'] ?? null;
        unset($vehicleData['images']);

        $fillable = (new Vehicle)->getFillable();
        $vehicleData = array_intersect_key($vehicleData, array_flip($fillable));

        if (empty($vehicleData['dmr_fact_vehicle_id'])) {
            throw new \InvalidArgumentException('dmr_fact_vehicle_id is required');
        }

        $vehicle = Vehicle::create($vehicleData);

        if ($equipmentIds !== null) {
            $vehicle->equipment()->sync($equipmentIds);
        }

        if (is_array($images)) {
            $sortOrder = 0;
            foreach ($images as $file) {
                if (is_string($file)) {
                    $imagePath = str_replace('/storage/', '', parse_url($file, PHP_URL_PATH));

                    $thumbnailPath = null;
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($file, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                    } catch (\Exception $e) {
                    }

                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                } else {
                    $this->fileService->validateFile($file);
                    $uploadedUrl = $this->fileService->uploadFiles(
                        [$file],
                        'public',
                        'vehicles',
                        true,
                        false,
                        300,
                        300
                    )[0];

                    $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH));

                    $thumbnailPath = null;
                    try {
                        $thumbnailUrl = $this->fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                        $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                    } catch (\Exception $e) {
                    }

                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_path' => $imagePath,
                        'thumbnail_path' => $thumbnailPath,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        $vehicle = $vehicle->fresh(['images', 'equipment', 'dmrFactVehicle.drivmiddelLines']);
        if ($vehicle) {
            $this->ownershipTaxService->updateCalculatedOwnershipTax($vehicle);
        }

        return $vehicle;
    }

    /**
     * Fetch vehicle data from Nummerplade API
     * Accepts either registration or VIN
     */
    public function fetchVehicleDataFromNummerplade(?string $registration = null, ?string $vin = null): array
    {
        if ($registration) {
            return $this->dmrFactVehicleLookupService->lookupByRegistration($registration, true);
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

        // DMR registration payload (DmrFactVehicleLookupService) — nested fuel_economy or flat + dmr_fact_vehicle_id
        $isDmrPayload = isset($apiData['dmr_fact_vehicle_id'])
            || (isset($apiData['fuel_economy']) && is_array($apiData['fuel_economy']));

        if ($isDmrPayload) {
            $brandLabel = $apiData['brand'] ?? null;
            $brandLabel = is_array($brandLabel) ? ($brandLabel['name'] ?? null) : $brandLabel;
            if (!empty($brandLabel)) {
                $brand = Brand::firstOrCreateInsensitive(['name' => trim((string) $brandLabel)]);
                $transformed['brand_id'] = $brand->id;
            }

            $modelLabel = $apiData['model'] ?? null;
            $modelLabel = is_array($modelLabel) ? ($modelLabel['name'] ?? null) : $modelLabel;
            if (!empty($modelLabel) && !empty($transformed['brand_id'])) {
                $vehicleModel = VehicleModel::firstOrCreateInsensitive([
                    'brand_id' => $transformed['brand_id'],
                    'name' => trim((string) $modelLabel),
                ]);
                $transformed['model_id'] = $vehicleModel->id;
            }

            $variantLabel = $apiData['variant'] ?? null;
            $variantLabel = is_array($variantLabel) ? ($variantLabel['name'] ?? null) : $variantLabel;
            if (!empty($variantLabel)) {
                $variant = Variant::firstOrCreateInsensitive(['name' => trim((string) $variantLabel)]);
                $transformed['variant_id'] = $variant->id;
            }

            $primaryFuel = $apiData['fuel_economy']['primary'] ?? null;
            $lines = $apiData['fuel_economy']['lines'] ?? [];
            $firstLineFuel = is_array($lines) && isset($lines[0]['fuel_type']) ? $lines[0]['fuel_type'] : null;
            $fuelTypeObj = $apiData['fuel_type'] ?? null;
            $fuelNameFromFlat = is_array($fuelTypeObj) ? ($fuelTypeObj['name'] ?? null) : null;
            $fuelName = is_array($primaryFuel) ? ($primaryFuel['fuel_type'] ?? null) : null;
            $fuelName = $fuelName ?? $firstLineFuel ?? $fuelNameFromFlat;
            if ($fuelName) {
                $fuelType = FuelType::where('name', $fuelName)->first();
                if ($fuelType) {
                    $transformed['fuel_type_id'] = $fuelType->id;
                }
            }

            $kmPerLiter = is_array($primaryFuel) ? ($primaryFuel['motor_km_per_liter'] ?? null) : null;
            if ($kmPerLiter === null && is_array($lines) && isset($lines[0]['motor_km_per_liter'])) {
                $kmPerLiter = $lines[0]['motor_km_per_liter'];
            }
            if ($kmPerLiter === null && array_key_exists('motor_km_per_liter', $apiData)) {
                $kmPerLiter = $apiData['motor_km_per_liter'];
            }
            if ($kmPerLiter !== null) {
                $transformed['fuel_efficiency'] = $kmPerLiter;
            }

            $euronormLabel = $apiData['euronorm'] ?? null;
            $euronormLabel = is_array($euronormLabel) ? ($euronormLabel['name'] ?? null) : $euronormLabel;
            if (!empty($euronormLabel)) {
                $euronom = Euronom::firstOrCreateInsensitive(['name' => trim((string) $euronormLabel)]);
                $transformed['euronom_id'] = $euronom->id;
            }

            if (isset($apiData['registration'])) {
                $transformed['registration'] = $apiData['registration'];
            }

            if (isset($apiData['chassis_number'])) {
                $transformed['vin'] = $apiData['chassis_number'];
            }

            if (isset($apiData['first_registration_date'])) {
                $transformed['first_registration_date'] = $apiData['first_registration_date'];
            }

            if (isset($apiData['engine_power_kw'])) {
                $transformed['engine_power'] = (int) round((float) $apiData['engine_power_kw']);
            }

            if (isset($apiData['maximum_weight_kg'])) {
                $transformed['technical_total_weight'] = $apiData['maximum_weight_kg'];
            }

            $year = $apiData['model_year_effective'] ?? $apiData['model_year'] ?? null;
            if ($year !== null) {
                $modelYear = ModelYear::firstOrCreateInsensitive(['name' => (string) $year]);
                $transformed['model_year_id'] = $modelYear->id;
            }

            $titleBrand = is_array($apiData['brand'] ?? null) ? ($apiData['brand']['name'] ?? '') : (string) ($apiData['brand'] ?? '');
            $titleModel = is_array($apiData['model'] ?? null) ? ($apiData['model']['name'] ?? '') : (string) ($apiData['model'] ?? '');
            if ($titleBrand !== '' && $titleModel !== '') {
                $transformed['title'] = trim($titleBrand . ' ' . $titleModel);
            }

            return $transformed;
        }

        // Legacy Nummerplade / VIN API shape
        // Lookup brand_id from brands table
        if (isset($apiData['make']) || isset($apiData['brand'])) {
            $brandName = $apiData['make'] ?? $apiData['brand'];
            // Support string brand (legacy) or nested structure
            if (is_array($brandName)) {
                $brandName = $brandName['name'] ?? null;
            }
            if ($brandName) {
                $brand = Brand::where('name', $brandName)->first();
                if ($brand) {
                    $transformed['brand_id'] = $brand->id;
                }
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

        // Map km_driven (removed mileage column)
        if (isset($apiData['mileage'])) {
            $transformed['km_driven'] = $apiData['mileage'];
        }

        if (isset($apiData['kmDriven'])) {
            $transformed['km_driven'] = $apiData['kmDriven'];
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

        // Handle variant lookup/insertion
        if (isset($apiData['variant']) || isset($apiData['variantName'])) {
            $variantName = $apiData['variant'] ?? $apiData['variantName'];
            if (is_array($variantName)) {
                $variantName = $variantName['name'] ?? null;
            }
            if ($variantName) {
                $variant = Variant::firstOrCreateInsensitive(['name' => $variantName]);
                $transformed['variant_id'] = $variant->id;
            }
        }

        // Handle euronom lookup/insertion
        if (isset($apiData['euronorm']) || isset($apiData['euronom']) || isset($apiData['euroNorm'])) {
            $euronomName = $apiData['euronorm'] ?? $apiData['euronom'] ?? $apiData['euroNorm'];
            if (is_array($euronomName)) {
                $euronomName = $euronomName['name'] ?? null;
            }
            if ($euronomName) {
                $euronom = Euronom::firstOrCreateInsensitive(['name' => $euronomName]);
                $transformed['euronom_id'] = $euronom->id;
            }
        }

        // Map fuel_efficiency from API
        if (isset($apiData['fuelEfficiency']) || isset($apiData['fuel_efficiency'])) {
            $transformed['fuel_efficiency'] = $apiData['fuelEfficiency'] ?? $apiData['fuel_efficiency'];
        }

        // Map technical_total_weight
        if (isset($apiData['technicalTotalWeight']) || isset($apiData['technical_total_weight'])) {
            $transformed['technical_total_weight'] = $apiData['technicalTotalWeight'] ?? $apiData['technical_total_weight'];
        }

        return $transformed;
    }

    /**
     * Update a vehicle
     */
    public function updateVehicle(Vehicle $vehicle, array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $vehicleData) {
            $equipmentIds = null;
            if (isset($vehicleData['equipment_ids']) && is_array($vehicleData['equipment_ids'])) {
                $equipmentIds = $vehicleData['equipment_ids'];
                unset($vehicleData['equipment_ids']);
            } elseif (isset($vehicleData['equipment']) && is_array($vehicleData['equipment'])) {
                $equipmentIds = $vehicleData['equipment'];
                unset($vehicleData['equipment']);
            }

            $detailsFields = [
                'description', 'views_count', 'vin_location', 'vehicle_external_id', 'type_id', 'type_name',
                'registration_status', 'registration_status_updated_date', 'expire_date',
                'status_updated_date', 'total_weight', 'vehicle_weight',
                'technical_total_weight', 'coupling', 'towing_weight_brakes', 'minimum_weight',
                'gross_combination_weight', 'engine_displacement',
                'engine_cylinders', 'engine_code', 'category', 'last_inspection_date',
                'last_inspection_result', 'last_inspection_odometer', 'type_approval_code',
                'top_speed', 'doors', 'minimum_seats', 'maximum_seats', 'wheels',
                'extra_equipment', 'axles', 'drive_axles', 'wheelbase', 'leasing_period_start',
                'leasing_period_end', 'use_id', 'color_id', 'body_type_id', 'variant_id',
                'dispensations', 'permits', 'ncap_five', 'airbags', 'integrated_child_seats',
                'seat_belt_alarms', 'euronom_id', 'servicebog', 'price_type_id', 'condition_id',
                'sales_type_id', 'seller_phone', 'annual_tax', 'owners',
                'production_date', 'cover_image_index', 'fuel_consumption_wltp', 'fuel_consumption_nedc',
                'co2_emissions', 'is_import', 'is_factory_new', 'transmission_id', 'transmission_name',
                'wholesale_price', 'internal_cost_price', 'engine_type',
            ];
            foreach ($detailsFields as $field) {
                if (isset($vehicleData[$field])) {
                    if ($field === 'description') {
                        $vehicleData['description'] = trim((string) ($vehicleData['description'] ?? '') . "\n\n" . (string) $vehicleData[$field]);
                    } elseif ($field === 'condition_id') {
                        $vehicleData['condition_id'] = $vehicleData['condition_id'] ?? $vehicleData[$field];
                    } elseif ($field === 'servicebog') {
                        $vehicleData['servicebog'] = $vehicleData['servicebog'] ?? $vehicleData[$field];
                    }
                    unset($vehicleData[$field]);
                }
            }

            if ($equipmentIds !== null) {
                $vehicle->equipment()->sync($equipmentIds);
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
                        // Already a path/URL - extract relative path and try to generate thumbnail if it doesn't exist
                        // Convert URL like "http://localhost/storage/vehicles/abc.jpg" to "vehicles/abc.jpg"
                        $imagePath = str_replace('/storage/', '', parse_url($file, PHP_URL_PATH));
                        
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($file, 300, 300, 'public');
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    } else {
                        // Upload file with thumbnail generation
                        $this->fileService->validateFile($file);
                        $uploadedUrl = $this->fileService->uploadFiles(
                            [$file], 
                            'public', 
                            'vehicles',
                            true, // createThumbnails
                            false, // optimizeImages
                            300, // thumbnailWidth
                            300  // thumbnailHeight
                        )[0];
                        
                        // Extract relative path from URL (remove domain and /storage/ prefix)
                        // Convert URL like "http://localhost/storage/vehicles/abc.jpg" to "vehicles/abc.jpg"
                        $imagePath = str_replace('/storage/', '', parse_url($uploadedUrl, PHP_URL_PATH));
                        
                        // Extract thumbnail path from URL
                        $thumbnailPath = null;
                        try {
                            $thumbnailUrl = $this->fileService->createThumbnail($uploadedUrl, 300, 300, 'public');
                            $thumbnailPath = str_replace('/storage/', '', parse_url($thumbnailUrl, PHP_URL_PATH));
                        } catch (\Exception $e) {
                            // Thumbnail generation failed, continue without thumbnail
                        }
                        
                        VehicleImage::create([
                            'vehicle_id' => $vehicle->id,
                            'image_path' => $imagePath,
                            'thumbnail_path' => $thumbnailPath,
                            'sort_order' => $sortOrder++,
                        ]);
                    }
                }
                unset($vehicleData['images']);
            }

            $fillable = (new Vehicle)->getFillable();
            $vehicleData = array_intersect_key($vehicleData, array_flip($fillable));

            if (! empty($vehicleData)) {
                $vehicle->update($vehicleData);
            }

            $updatedVehicle = $vehicle->fresh(['images', 'equipment', 'dmrFactVehicle.drivmiddelLines']);

            if (! $updatedVehicle) {
                $updatedVehicle = Vehicle::with(['images', 'equipment', 'dmrFactVehicle.drivmiddelLines'])->findOrFail($vehicle->id);
            }

            $this->ownershipTaxService->updateCalculatedOwnershipTax($updatedVehicle);

            return $updatedVehicle;
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
            }, 'dmrFactVehicle.variant.model.brand', 'dealer']);

        if (! empty($filters['dealer_id'])) {
            $query->where('dealer_id', $filters['dealer_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('registration', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['brand_id'])) {
            $brandIds = is_array($filters['brand_id']) ? $filters['brand_id'] : [$filters['brand_id']];
            $dmrBrandIds = $this->resolveDmrBrandIds($brandIds);
            if (! empty($dmrBrandIds)) {
                $query->whereHas('dmrFactVehicle.variant.model', function ($q) use ($dmrBrandIds) {
                    $q->whereIn('brand_id', $dmrBrandIds);
                });
            }
        }

        if (! empty($filters['model_id'])) {
            $modelIds = is_array($filters['model_id']) ? $filters['model_id'] : [$filters['model_id']];
            $dmrModelIds = $this->resolveDmrModelIds($modelIds);
            if (! empty($dmrModelIds)) {
                $query->whereHas('dmrFactVehicle.variant', function ($q) use ($dmrModelIds) {
                    $q->whereIn('model_id', $dmrModelIds);
                });
            }
        }

        if (! empty($filters['model_year_id'])) {
            $yearIds = is_array($filters['model_year_id']) ? $filters['model_year_id'] : [$filters['model_year_id']];
            $years = ModelYear::whereIn('id', $yearIds)->pluck('name')->map(fn ($n) => (string) $n)->all();
            if (! empty($years)) {
                $query->whereHas('dmrFactVehicle', function ($q) use ($years) {
                    $q->whereIn(DB::raw('CAST(model_aar AS CHAR)'), $years);
                });
            }
        }

        if (! empty($filters['fuel_type_id'])) {
            $fuelTypeIds = is_array($filters['fuel_type_id']) ? $filters['fuel_type_id'] : [$filters['fuel_type_id']];
            $energyIds = $this->resolveDmrDriveEnergyIds($fuelTypeIds);
            if (! empty($energyIds)) {
                $query->whereHas('dmrFactVehicle.drivmiddelLines', function ($q) use ($energyIds) {
                    $q->whereIn('drive_energy_id', $energyIds);
                });
            }
        }

        if (! empty($filters['km_driven_from']) || ! empty($filters['km_driven_to'])) {
            $from = isset($filters['km_driven_from']) && $filters['km_driven_from'] !== '' ? (int) $filters['km_driven_from'] : null;
            $to = isset($filters['km_driven_to']) && $filters['km_driven_to'] !== '' ? (int) $filters['km_driven_to'] : null;
            if ($from !== null && $to !== null) {
                $query->where(function ($q) use ($from, $to) {
                    $q->whereNull('km_driven')
                        ->orWhereBetween('km_driven', [$from, $to]);
                });
            } elseif ($from !== null) {
                $query->where(function ($q) use ($from) {
                    $q->whereNull('km_driven')
                        ->orWhere('km_driven', '>=', $from);
                });
            } else {
                $query->where(function ($q) use ($to) {
                    $q->whereNull('km_driven')
                        ->orWhere('km_driven', '<=', $to);
                });
            }
        }

        if (! empty($filters['price_from']) && $filters['price_from'] > 0) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('price')
                    ->orWhere('price', '>=', $filters['price_from']);
            });
        }
        if (! empty($filters['price_to']) && $filters['price_to'] > 0) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('price')
                    ->orWhere('price', '<=', $filters['price_to']);
            });
        }

        if (! empty($filters['condition_id'])) {
            $query->where('condition_id', (int) $filters['condition_id']);
        }

        if (! empty($filters['equipment_ids']) || ! empty($filters['equipment_id'])) {
            $equipmentIds = $filters['equipment_ids'] ?? [$filters['equipment_id']];
            $equipmentIds = is_array($equipmentIds) ? array_filter(array_map('intval', $equipmentIds)) : [];
            if (! empty($equipmentIds)) {
                $query->whereHas('equipment', function ($q) use ($equipmentIds) {
                    $q->whereIn('equipments.id', $equipmentIds);
                });
            }
        }

        if (! empty($filters['year_from']) && $filters['year_from'] > 1975) {
            $query->whereHas('dmrFactVehicle', function ($q) use ($filters) {
                $q->where('model_aar', '>=', (int) $filters['year_from']);
            });
        }
        if (! empty($filters['year_to']) && $filters['year_to'] < (int) date('Y') + 2) {
            $query->whereHas('dmrFactVehicle', function ($q) use ($filters) {
                $q->where('model_aar', '<=', (int) $filters['year_to']);
            });
        }

        if (! empty($filters['body_type_id'])) {
            $bodyIds = is_array($filters['body_type_id']) ? $filters['body_type_id'] : [$filters['body_type_id']];
            $bodyIds = array_filter(array_map('intval', $bodyIds));
            if (! empty($bodyIds)) {
                $query->whereHas('dmrFactVehicle', function ($q) use ($bodyIds) {
                    $q->whereIn('body_type_id', $bodyIds);
                });
            }
        }

        if (! empty($filters['gear_type_id'])) {
            $gearIds = is_array($filters['gear_type_id']) ? $filters['gear_type_id'] : [$filters['gear_type_id']];
            $gearIds = array_filter(array_map('intval', $gearIds));
            if (! empty($gearIds)) {
                $query->whereIn('gear_type_id', $gearIds);
            }
        }

        if (! empty($filters['first_registration_year_from']) && $filters['first_registration_year_from'] > 1975) {
            $query->whereHas('dmrFactVehicle', function ($q) use ($filters) {
                $q->whereYear('foerste_registrering_dato', '>=', (int) $filters['first_registration_year_from']);
            });
        }
        if (! empty($filters['first_registration_year_to']) && $filters['first_registration_year_to'] <= (int) date('Y') + 1) {
            $query->whereHas('dmrFactVehicle', function ($q) use ($filters) {
                $q->whereYear('foerste_registrering_dato', '<=', (int) $filters['first_registration_year_to']);
            });
        }

        $this->applySorting($query, $filters['sort'] ?? 'standard');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get dealer vehicles (all statuses) with relations
     */
    public function getDealerVehicles(int $dealerId, array $filters = [], int $perPage = 15, int $page = 1)
    {
        $query = Vehicle::query()
            ->where('dealer_id', $dealerId)
            ->with([
                'images' => function ($query) {
                    $query->orderBy('sort_order');
                },
                'equipment',
                'dmrFactVehicle.variant.model.brand',
            ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('registration', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['vehicle_list_status_id'])) {
            $query->where('vehicle_list_status_id', $filters['vehicle_list_status_id']);
        }

        $this->applySorting($query, $filters['sort'] ?? 'standard');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get public dealer vehicles (published only) with filters
     * Similar to getPublicVehicles but filtered by dealer_id
     * 
     * @param int $dealerId
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublicDealerVehicles(int $dealerId, array $filters = [], int $perPage = 15, int $page = 1)
    {
        $filters['dealer_id'] = $dealerId;

        return $this->getPublicVehicles($filters, $perPage, $page);
    }

    /**
     * Apply sorting to vehicle query (DMR-backed listings).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applySorting($query, string $sort = 'standard'): void
    {
        $joins = $query->getQuery()->joins ?? [];
        $hasJoins = ! empty($joins);
        $tablePrefix = $hasJoins ? 'vehicles.' : '';

        switch ($sort) {
            case 'price_asc':
                $query->orderBy($tablePrefix . 'price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy($tablePrefix . 'price', 'desc');
                break;
            case 'date_desc':
                $query->orderBy($tablePrefix . 'created_at', 'desc');
                break;
            case 'date_asc':
                $query->orderBy($tablePrefix . 'created_at', 'asc');
                break;
            case 'year_desc':
                $query->leftJoin('dmr_fact_vehicles as dfv_y', 'vehicles.dmr_fact_vehicle_id', '=', 'dfv_y.id')
                    ->orderBy('dfv_y.model_aar', 'desc');
                if (! $hasJoins) {
                    $query->select('vehicles.*');
                }
                break;
            case 'year_asc':
                $query->leftJoin('dmr_fact_vehicles as dfv_y2', 'vehicles.dmr_fact_vehicle_id', '=', 'dfv_y2.id')
                    ->orderBy('dfv_y2.model_aar', 'asc');
                if (! $hasJoins) {
                    $query->select('vehicles.*');
                }
                break;
            case 'mileage_desc':
                $query->orderByRaw('COALESCE(' . $tablePrefix . 'km_driven, 0) DESC');
                break;
            case 'mileage_asc':
                $query->orderByRaw('COALESCE(' . $tablePrefix . 'km_driven, 0) ASC');
                break;
            case 'range_desc':
                $query->orderBy($tablePrefix . 'range_km', 'desc');
                break;
            case 'range_asc':
                $query->orderBy($tablePrefix . 'range_km', 'asc');
                break;
            case 'battery_desc':
                $query->orderBy($tablePrefix . 'battery_capacity', 'desc');
                break;
            case 'battery_asc':
                $query->orderBy($tablePrefix . 'battery_capacity', 'asc');
                break;
            case 'brand_asc':
                $query->leftJoin('dmr_fact_vehicles as dfv_b', 'vehicles.dmr_fact_vehicle_id', '=', 'dfv_b.id')
                    ->leftJoin('dmr_variants as dv_b', 'dfv_b.variant_id', '=', 'dv_b.id')
                    ->leftJoin('dmr_models as dm_b', 'dv_b.model_id', '=', 'dm_b.id')
                    ->leftJoin('dmr_brands as db_b', 'dm_b.brand_id', '=', 'db_b.id')
                    ->orderBy('db_b.name', 'asc');
                if (! $hasJoins) {
                    $query->select('vehicles.*');
                }
                break;
            case 'brand_desc':
                $query->leftJoin('dmr_fact_vehicles as dfv_b2', 'vehicles.dmr_fact_vehicle_id', '=', 'dfv_b2.id')
                    ->leftJoin('dmr_variants as dv_b2', 'dfv_b2.variant_id', '=', 'dv_b2.id')
                    ->leftJoin('dmr_models as dm_b2', 'dv_b2.model_id', '=', 'dm_b2.id')
                    ->leftJoin('dmr_brands as db_b2', 'dm_b2.brand_id', '=', 'db_b2.id')
                    ->orderBy('db_b2.name', 'desc');
                if (! $hasJoins) {
                    $query->select('vehicles.*');
                }
                break;
            case 'first_reg_desc':
                $query->leftJoin('dmr_fact_vehicles as dfv_fr', 'vehicles.dmr_fact_vehicle_id', '=', 'dfv_fr.id')
                    ->orderBy('dfv_fr.foerste_registrering_dato', 'desc');
                if (! $hasJoins) {
                    $query->select('vehicles.*');
                }
                break;
            case 'first_reg_asc':
                $query->leftJoin('dmr_fact_vehicles as dfv_fr2', 'vehicles.dmr_fact_vehicle_id', '=', 'dfv_fr2.id')
                    ->orderBy('dfv_fr2.foerste_registrering_dato', 'asc');
                if (! $hasJoins) {
                    $query->select('vehicles.*');
                }
                break;
            case 'distance_asc':
            case 'distance_desc':
                $query->orderBy($tablePrefix . 'id', 'desc');
                break;
            case 'standard':
            default:
                $query->orderBy($tablePrefix . 'id', 'desc');
                break;
        }
    }

    /**
     * Advanced filters: merged into {@see getPublicVehicles()} (DMR + slim vehicles columns).
     */
    public function getPublicVehiclesWithAdvancedFilters(array $basicFilters = [], array $advancedFilters = [], int $perPage = 15, int $page = 1)
    {
        $merged = array_merge($advancedFilters, $basicFilters);
        if (! empty($merged['mileage_from']) && empty($merged['km_driven_from'])) {
            $merged['km_driven_from'] = $merged['mileage_from'];
        }
        if (! empty($merged['mileage_to']) && empty($merged['km_driven_to'])) {
            $merged['km_driven_to'] = $merged['mileage_to'];
        }

        return $this->getPublicVehicles($merged, $perPage, $page);
    }

    /**
     * @param  array<int|string>  $brandIds
     * @return array<int>
     */
    protected function resolveDmrBrandIds(array $brandIds): array
    {
        $brandIds = array_values(array_filter(array_map('intval', $brandIds)));
        if (empty($brandIds)) {
            return [];
        }
        $dmr = DmrBrand::whereIn('id', $brandIds)->pluck('id')->all();
        if (count($dmr) === count($brandIds)) {
            return $dmr;
        }
        $names = Brand::whereIn('id', $brandIds)->pluck('name')->filter();
        if ($names->isEmpty()) {
            return [];
        }

        return DmrBrand::whereIn('name', $names)->pluck('id')->all();
    }

    /**
     * @param  array<int|string>  $modelIds
     * @return array<int>
     */
    protected function resolveDmrModelIds(array $modelIds): array
    {
        $modelIds = array_values(array_filter(array_map('intval', $modelIds)));
        if (empty($modelIds)) {
            return [];
        }
        $dmr = DmrModel::whereIn('id', $modelIds)->pluck('id')->all();
        if (count($dmr) === count($modelIds)) {
            return $dmr;
        }
        $names = VehicleModel::whereIn('id', $modelIds)->pluck('name')->filter();
        if ($names->isEmpty()) {
            return [];
        }

        return DmrModel::whereIn('name', $names)->pluck('id')->all();
    }

    /**
     * @param  array<int|string>  $fuelTypeIds
     * @return array<int>
     */
    protected function resolveDmrDriveEnergyIds(array $fuelTypeIds): array
    {
        $fuelTypeIds = array_values(array_filter(array_map('intval', $fuelTypeIds)));
        if (empty($fuelTypeIds)) {
            return [];
        }
        $dmr = DmrDriveEnergy::whereIn('id', $fuelTypeIds)->pluck('id')->all();
        if (count($dmr) === count($fuelTypeIds)) {
            return $dmr;
        }
        $names = FuelType::whereIn('id', $fuelTypeIds)->pluck('name')->filter();
        if ($names->isEmpty()) {
            return [];
        }

        return DmrDriveEnergy::whereIn('name', $names)->pluck('id')->all();
    }
}
