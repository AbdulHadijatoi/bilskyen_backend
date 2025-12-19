<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Contact;
use App\Services\FileService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    public function __construct(
        private FileService $fileService,
        private NotificationService $notificationService
    ) {}

    /**
     * Create a vehicle
     */
    public function createVehicle(array $vehicleData): Vehicle
    {
        return DB::transaction(function () use ($vehicleData) {
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

