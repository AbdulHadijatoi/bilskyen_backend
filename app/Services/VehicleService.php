<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Contact;
use App\Models\FuelType;
use App\Models\Transmission;
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

            // Handle file uploads if present
            if (isset($vehicleData['images']) && is_array($vehicleData['images'])) {
                $uploadedFiles = [];
                foreach ($vehicleData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a URL
                        $uploadedFiles[] = $file;
                    } else {
                        // Upload file
                        $this->fileService->validateFile($file);
                        $uploadedFiles[] = $this->fileService->uploadFiles([$file], 'public', 'vehicles')[0];
                    }
                }
                $vehicleData['images'] = $uploadedFiles;
            }

            return Vehicle::create($vehicleData);
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

        if (isset($apiData['make'])) {
            // Store make in appropriate field (may need to create make/model tables)
            $transformed['make'] = $apiData['make'];
        }

        if (isset($apiData['model'])) {
            $transformed['model'] = $apiData['model'];
        }

        if (isset($apiData['year'])) {
            $transformed['year'] = $apiData['year'];
        }

        if (isset($apiData['fuelType'])) {
            // Lookup fuel_type_id from fuel_types table
            $fuelType = FuelType::where('name', $apiData['fuelType'])->first();
            if ($fuelType) {
                $transformed['fuel_type_id'] = $fuelType->id;
            }
        }

        if (isset($apiData['transmission'])) {
            // Lookup transmission_id from transmissions table
            $transmission = Transmission::where('name', $apiData['transmission'])->first();
            if ($transmission) {
                $transformed['transmission_id'] = $transmission->id;
            }
        }

        if (isset($apiData['bodyType'])) {
            $transformed['body_type'] = $apiData['bodyType'];
        }

        if (isset($apiData['mileage'])) {
            $transformed['mileage'] = $apiData['mileage'];
        }

        if (isset($apiData['equipment'])) {
            $transformed['equipment'] = is_array($apiData['equipment']) 
                ? $apiData['equipment'] 
                : json_decode($apiData['equipment'], true);
        }

        if (isset($apiData['specs'])) {
            $transformed['specs'] = is_array($apiData['specs']) 
                ? $apiData['specs'] 
                : json_decode($apiData['specs'], true);
        }

        // Store registration and VIN for future reference
        if (isset($apiData['registration'])) {
            $transformed['registration'] = $apiData['registration'];
        }

        if (isset($apiData['vin'])) {
            $transformed['vin'] = $apiData['vin'];
        }

        // Extract flags if available
        if (isset($apiData['hasCarplay'])) {
            $transformed['has_carplay'] = (bool) $apiData['hasCarplay'];
        }

        if (isset($apiData['hasAdaptiveCruise'])) {
            $transformed['has_adaptive_cruise'] = (bool) $apiData['hasAdaptiveCruise'];
        }

        if (isset($apiData['isElectric'])) {
            $transformed['is_electric'] = (bool) $apiData['isElectric'];
        }

        return $transformed;
    }

    /**
     * Update a vehicle
     */
    public function updateVehicle(Vehicle $vehicle, array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicle, $vehicleData) {
            // Delete old images if new ones are provided
            if (isset($vehicleData['images']) && is_array($vehicleData['images'])) {
                $oldImages = $vehicle->images ?? [];
                if (!empty($oldImages)) {
                    $this->fileService->deleteFiles($oldImages);
                }

                // Handle new file uploads
                $uploadedFiles = [];
                foreach ($vehicleData['images'] as $file) {
                    if (is_string($file)) {
                        // Already a URL
                        $uploadedFiles[] = $file;
                    } else {
                        // Upload file
                        $this->fileService->validateFile($file);
                        $uploadedFiles[] = $this->fileService->uploadFiles([$file], 'public', 'vehicles')[0];
                    }
                }
                $vehicleData['images'] = $uploadedFiles;
            }

            // Update vehicle
            $vehicle->update($vehicleData);

            // Check if notifications need to be created
            $purchasesCount = $vehicle->purchases()->count();
            $salesCount = $vehicle->sales()->count();

            if ($purchasesCount === 0 && $salesCount === 0) {
                return $vehicle;
            }

            $isLastWasPurchase = $purchasesCount > 0 && $purchasesCount > $salesCount;
            $isLastWasSale = $salesCount > 0 && $salesCount >= $purchasesCount;

            if ($isLastWasPurchase) {
                $purchase = Purchase::where('vehicle_id', $vehicle->id)
                    ->orderBy('purchase_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($purchase) {
                    $contact = $purchase->contact;
                    if ($contact) {
                        $this->notificationService->createPurchaseNotifications(
                            $purchase,
                            $vehicle,
                            $contact,
                            true // clear existing notifications
                        );
                    }
                }
            }

            if ($isLastWasSale) {
                $sale = Sale::where('vehicle_id', $vehicle->id)
                    ->orderBy('sale_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($sale) {
                    $contact = $sale->contact;
                    if ($contact) {
                        $this->notificationService->createSaleNotifications(
                            $sale,
                            $vehicle,
                            $contact,
                            true // clear existing notifications
                        );
                    }
                }
            }

            return $vehicle->fresh();
        });
    }

    /**
     * Delete a vehicle
     */
    public function deleteVehicle(Vehicle $vehicle): void
    {
        DB::transaction(function () use ($vehicle) {
            // Delete vehicle images
            $images = $vehicle->images ?? [];
            if (!empty($images)) {
                $this->fileService->deleteFiles($images);
            }

            // Delete vehicle
            $vehicle->delete();
        });
    }
}

