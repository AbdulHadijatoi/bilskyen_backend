<?php

namespace App\Observers;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use App\Services\Dms\DealerDmsService;

class VehicleDmsWebhookObserver
{
    public function __construct(
        private DealerDmsService $dealerDmsService,
    ) {}

    public function saved(Vehicle $vehicle): void
    {
        if (! $vehicle->dealer_id) {
            return;
        }

        if ($vehicle->wasChanged('list_status_id') || $vehicle->wasRecentlyCreated) {
            if ($vehicle->list_status_id === VehicleListStatus::PUBLISHED) {
                $this->dealerDmsService->dispatchVehicleEvent($vehicle, 'vehicle.published');
            } elseif ($vehicle->getOriginal('list_status_id') === VehicleListStatus::PUBLISHED) {
                $this->dealerDmsService->dispatchVehicleEvent($vehicle, 'vehicle.unpublished');
            }
        } elseif ($vehicle->list_status_id === VehicleListStatus::PUBLISHED
            && $vehicle->wasChanged(['price', 'title', 'description'])) {
            $this->dealerDmsService->dispatchVehicleEvent($vehicle, 'vehicle.updated');
        }
    }
}
