<?php

namespace App\Console\Commands;

use App\Constants\BillingModel;
use App\Constants\SubscriptionStatus;
use App\Models\DealerSubscription;
use App\Services\ListingBillingService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Console\Command;

class ActivateScheduledSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:activate-scheduled';

    protected $description = 'Activate subscriptions that were scheduled for a future start date';

    public function handle(
        SubscriptionFeatureService $featureService,
        ListingBillingService $listingBillingService,
    ): int {
        $count = 0;

        $subscriptions = DealerSubscription::query()
            ->where('subscription_status_id', SubscriptionStatus::SCHEDULED)
            ->where('starts_at', '<=', now())
            ->with(['plan', 'dealer'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $plan = $subscription->plan;
            $newStatus = ($plan && $plan->trial_days > 0)
                ? SubscriptionStatus::TRIAL
                : SubscriptionStatus::ACTIVE;

            $subscription->update(['subscription_status_id' => $newStatus]);
            $featureService->clearCache($subscription->dealer);

            if ($plan?->billing_model === BillingModel::USAGE_DAILY && $subscription->dealer) {
                $listingBillingService->startBillingForPublishedVehicles(
                    $subscription->dealer,
                    $subscription->starts_at
                );
            }

            $count++;
        }

        $this->info("Activated {$count} scheduled subscription(s).");

        return self::SUCCESS;
    }
}
