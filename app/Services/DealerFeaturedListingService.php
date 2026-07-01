<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\FeaturedListing;
use App\Models\Vehicle;

class DealerFeaturedListingService
{
    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    public function countFeaturedListingsForDealer(int $dealerId): int
    {
        return FeaturedListing::query()
            ->whereHas('vehicle', fn ($query) => $query->where('dealer_id', $dealerId))
            ->count();
    }

    public function canFeatureVehicle(Vehicle $vehicle): bool
    {
        if (! $vehicle->dealer_id) {
            return true;
        }

        $dealer = $vehicle->dealer;
        if (! $dealer) {
            return true;
        }

        $limit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_feature_listings', 0);
        if ($limit <= 0) {
            return false;
        }

        if ($limit >= 9999) {
            return true;
        }

        $used = $this->countFeaturedListingsForDealer($dealer->id);

        return $used < $limit;
    }
}
