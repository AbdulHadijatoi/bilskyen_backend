<?php

namespace App\Services;

use App\Constants\SubscriptionStatus;
use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\DealerSubscription;
use App\Models\Vehicle;

class SubscriptionLifecycleService
{
    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    public function expireDueSubscriptions(): int
    {
        $count = 0;

        $subscriptions = DealerSubscription::query()
            ->whereIn('subscription_status_id', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->update(['subscription_status_id' => SubscriptionStatus::EXPIRED]);
            $this->handleSubscriptionInactive($subscription->dealer);
            $this->subscriptionFeatureService->clearCache($subscription->dealer);
            $count++;
        }

        return $count;
    }

    public function handleSubscriptionInactive(Dealer $dealer): void
    {
        $billingService = app(ListingBillingService::class);

        $vehicles = Vehicle::where('dealer_id', $dealer->id)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->get();

        foreach ($vehicles as $vehicle) {
            $billingService->onVehicleUnpublished($vehicle);
        }

        Vehicle::where('dealer_id', $dealer->id)->update([
            'list_status_id' => VehicleListStatus::ARCHIVED,
        ]);
    }
}
