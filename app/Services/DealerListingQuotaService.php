<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\Vehicle;

class DealerListingQuotaService
{
    public function countPublishedListings(Dealer $dealer): int
    {
        return Vehicle::where('dealer_id', $dealer->id)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();
    }

    public function canPublishAnotherListing(Dealer $dealer, SubscriptionFeatureService $featureService): bool
    {
        if ($featureService->isUsageDailyPlan($dealer)) {
            return $featureService->hasActiveSubscription($dealer);
        }

        $publishedCount = $this->countPublishedListings($dealer);

        return $featureService->checkFeatureLimit($dealer, 'max_listings', $publishedCount);
    }
}
