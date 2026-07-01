<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\Vehicle;

class ListingExpirationService
{
    public function setExpiryOnPublish(Vehicle $vehicle, bool $isSellerListing = false): void
    {
        $days = $isSellerListing
            ? (int) config('marketplace.listing_expiry_days.seller', 90)
            : 0;

        if (! $isSellerListing && $vehicle->dealer_id) {
            $dealer = $vehicle->dealer;
            if ($dealer) {
                $subscription = app(SubscriptionFeatureService::class)->getActiveSubscription($dealer);
                if ($subscription?->plan?->billing_model === 'usage_daily') {
                    $days = (int) config('marketplace.listing_expiry_days.dealer_usage', 0);
                } else {
                    $days = (int) config('marketplace.listing_expiry_days.dealer_subscription', 0);
                }
            }
        }

        if ($days <= 0) {
            $vehicle->update(['expires_at' => null]);

            return;
        }

        $vehicle->update([
            'expires_at' => now()->addDays($days),
        ]);
    }

    public function renewListing(Vehicle $vehicle): void
    {
        $isSeller = $vehicle->dealer_id === null;

        $this->setExpiryOnPublish($vehicle, $isSeller);
    }

    public function expireStaleListings(): int
    {
        $expired = 0;

        $vehicles = Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($vehicles as $vehicle) {
            $vehicle->update(['list_status_id' => VehicleListStatus::ARCHIVED]);

            app(ListingBillingService::class)->onVehicleUnpublished($vehicle);

            $expired++;
        }

        return $expired;
    }
}
