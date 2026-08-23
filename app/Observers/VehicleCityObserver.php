<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Services\CityIndexService;
use App\Services\RelatedVehiclesService;
use App\Services\SeoService;

class VehicleCityObserver
{
    public function __construct(
        private CityIndexService $cityIndexService
    ) {}

    public function saved(Vehicle $vehicle): void
    {
        $relevant = $vehicle->wasRecentlyCreated
            || $vehicle->wasChanged([
                'list_status_id',
                'published_at',
                'postcode',
                'address',
                'dealer_id',
                'price',
                'brand_id',
                'model_id',
                'fuel_type_id',
                'body_type_id',
            ]);

        if (! $relevant) {
            return;
        }

        RelatedVehiclesService::bumpCacheGeneration();

        $city = $this->cityIndexService->resolveCityForVehicle($vehicle);
        if ($city) {
            $this->cityIndexService->refreshForCity($city);
        }

        SeoService::forgetPublicCaches();
    }

    public function deleted(Vehicle $vehicle): void
    {
        RelatedVehiclesService::bumpCacheGeneration();
        $city = $this->cityIndexService->resolveCityForVehicle($vehicle);
        if ($city) {
            $this->cityIndexService->refreshForCity($city);
        }
        SeoService::forgetPublicCaches();
    }
}
