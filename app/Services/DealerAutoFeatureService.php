<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\FeaturedListing;
use App\Models\ListingHealthScore;
use App\Models\Vehicle;

class DealerAutoFeatureService
{
    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService,
        private DealerFeaturedListingService $featuredListingService,
    ) {}

    public function syncDealerFeaturedListings(Dealer $dealer): int
    {
        if (! $this->subscriptionFeatureService->hasFeature($dealer, 'auto_feature_listings')) {
            return 0;
        }

        $limit = $this->subscriptionFeatureService->getFeatureLimit($dealer, 'max_feature_listings', 0);
        if ($limit <= 0) {
            return 0;
        }

        $publishedId = VehicleListStatus::nameToId('published');
        if ($publishedId === null) {
            return 0;
        }

        $candidates = Vehicle::query()
            ->where('dealer_id', $dealer->id)
            ->where('list_status_id', $publishedId)
            ->with('featuredListing')
            ->get()
            ->sortByDesc(function (Vehicle $vehicle) {
                $score = ListingHealthScore::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->value('score');

                return $score ?? 0;
            })
            ->values();

        $targetIds = $candidates->take($limit)->pluck('id')->all();
        $currentFeatured = FeaturedListing::query()
            ->whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealer->id))
            ->pluck('vehicle_id')
            ->all();

        $changed = 0;

        foreach ($currentFeatured as $vehicleId) {
            if (! in_array($vehicleId, $targetIds, true)) {
                FeaturedListing::query()->where('vehicle_id', $vehicleId)->delete();
                $changed++;
            }
        }

        $sortOrder = (int) (FeaturedListing::max('sort_order') ?? 0);
        foreach ($targetIds as $vehicleId) {
            if (in_array($vehicleId, $currentFeatured, true)) {
                continue;
            }

            $vehicle = $candidates->firstWhere('id', $vehicleId);
            if (! $vehicle || ! $this->featuredListingService->canFeatureVehicle($vehicle)) {
                continue;
            }

            $sortOrder++;
            FeaturedListing::create([
                'vehicle_id' => $vehicleId,
                'sort_order' => $sortOrder,
            ]);
            $changed++;
        }

        return $changed;
    }
}
