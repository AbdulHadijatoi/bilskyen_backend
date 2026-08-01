<?php

namespace App\Observers;

use App\Models\Vehicle;
use App\Services\CityIndexService;
use Illuminate\Support\Facades\Cache;

class VehicleCityObserver
{
    public function __construct(
        private CityIndexService $cityIndexService
    ) {}

    public function saved(Vehicle $vehicle): void
    {
        $relevant = $vehicle->wasRecentlyCreated
            || $vehicle->wasChanged(['list_status_id', 'published_at', 'postcode', 'address', 'dealer_id', 'price', 'brand_id']);

        if (! $relevant) {
            return;
        }

        $city = $this->cityIndexService->resolveCityForVehicle($vehicle);
        if ($city) {
            $this->cityIndexService->refreshForCity($city);
        }

        Cache::forget('sitemap_xml');
    }

    public function deleted(Vehicle $vehicle): void
    {
        $city = $this->cityIndexService->resolveCityForVehicle($vehicle);
        if ($city) {
            $this->cityIndexService->refreshForCity($city);
        }
        Cache::forget('sitemap_xml');
    }
}
