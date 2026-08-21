<?php

namespace App\Observers;

use App\Models\Dealer;
use App\Services\CityIndexService;
use App\Services\SeoService;

class DealerCityObserver
{
    public function __construct(
        private CityIndexService $cityIndexService
    ) {}

    public function saved(Dealer $dealer): void
    {
        if (! $dealer->wasRecentlyCreated && ! $dealer->wasChanged(['city', 'postcode', 'marketplace_city_id'])) {
            return;
        }

        $previousCityId = $dealer->getOriginal('marketplace_city_id');

        $city = $this->cityIndexService->resolveCityForDealer($dealer);
        if ($city) {
            $this->cityIndexService->refreshForCity($city);
        }

        if ($previousCityId && (int) $previousCityId !== (int) ($city?->id)) {
            $this->cityIndexService->refreshForCity((int) $previousCityId);
        }

        SeoService::forgetPublicCaches();
    }

    public function deleted(Dealer $dealer): void
    {
        if ($dealer->marketplace_city_id) {
            $this->cityIndexService->refreshForCity((int) $dealer->marketplace_city_id);
        }
        SeoService::forgetPublicCaches();
    }
}
