<?php

namespace App\Observers;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;
use App\Services\Syndication\SyndicationService;

class VehicleSyndicationObserver
{
    public function __construct(
        private SyndicationService $syndicationService
    ) {}

    public function saved(Vehicle $vehicle): void
    {
        if (! $vehicle->dealer_id) {
            return;
        }

        if ($vehicle->wasChanged('list_status_id') || $vehicle->wasRecentlyCreated) {
            if ($vehicle->list_status_id === VehicleListStatus::PUBLISHED) {
                $this->syndicationService->syncVehicle($vehicle, 'publish');
            } elseif ($vehicle->getOriginal('list_status_id') === VehicleListStatus::PUBLISHED) {
                $this->syndicationService->syncVehicle($vehicle, 'unpublish');
            }
        } elseif ($vehicle->list_status_id === VehicleListStatus::PUBLISHED && $vehicle->wasChanged(['price', 'title', 'description'])) {
            $this->syndicationService->syncVehicle($vehicle, 'update');
        }
    }
}
