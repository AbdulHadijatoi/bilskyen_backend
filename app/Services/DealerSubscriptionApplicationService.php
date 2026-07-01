<?php

namespace App\Services;

use App\Constants\BillingModel;
use App\Models\Dealer;
use App\Models\DealerSubscription;
use App\Models\Plan;
use App\Models\User;
use App\Constants\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class DealerSubscriptionApplicationService
{
    public function __construct(
        private AuditLogService $auditLogService,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private ListingBillingService $listingBillingService,
    ) {}

    /**
     * @return array{subscription: DealerSubscription, subscription_features: array}
     */
    public function applyPlanToDealer(
        Dealer $dealer,
        Plan $plan,
        string $billingCycle,
        ?Carbon $startsAt,
        User $actorUser,
        ?Request $request,
        string $cancelAuditNote,
        string $createAuditNote,
    ): array {
        $startsAt = $startsAt ?? now();
        $isUsagePlan = $plan->billing_model === BillingModel::USAGE_DAILY;
        $isFutureStart = $startsAt->isFuture();

        return DB::transaction(function () use (
            $dealer,
            $plan,
            $billingCycle,
            $startsAt,
            $actorUser,
            $request,
            $cancelAuditNote,
            $createAuditNote,
            $isUsagePlan,
            $isFutureStart
        ) {
            $existingSubscriptions = DealerSubscription::where('dealer_id', $dealer->id)
                ->whereIn('subscription_status_id', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
                ->get();

            foreach ($existingSubscriptions as $existingSubscription) {
                $payloadBefore = [
                    'subscription_status_id' => $existingSubscription->subscription_status_id,
                ];

                $existingSubscription->update([
                    'subscription_status_id' => SubscriptionStatus::CANCELED,
                ]);

                try {
                    $this->auditLogService->logUpdate(
                        $actorUser,
                        'DealerSubscription',
                        $existingSubscription->id,
                        $payloadBefore,
                        ['subscription_status_id' => SubscriptionStatus::CANCELED],
                        $request,
                        'Dealer',
                        $dealer->id,
                        $cancelAuditNote,
                        ['dealer', 'subscription', 'cancel', 'change']
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to create audit log for subscription cancellation', [
                        'subscription_id' => $existingSubscription->id,
                        'dealer_id' => $dealer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($isFutureStart) {
                $subscriptionStatusId = SubscriptionStatus::SCHEDULED;
                $endsAt = null;
            } elseif ($isUsagePlan) {
                $subscriptionStatusId = SubscriptionStatus::ACTIVE;
                $endsAt = null;
                $billingCycle = BillingModel::USAGE_DAILY;
            } else {
                $subscriptionStatusId = ($plan->trial_days && $plan->trial_days > 0)
                    ? SubscriptionStatus::TRIAL
                    : SubscriptionStatus::ACTIVE;

                if ($subscriptionStatusId === SubscriptionStatus::TRIAL && $plan->trial_days) {
                    $endsAt = $startsAt->copy()->addDays($plan->trial_days);
                } else {
                    $endsAt = $billingCycle === 'yearly'
                        ? $startsAt->copy()->addYear()
                        : $startsAt->copy()->addMonth();
                }
            }

            $subscription = DealerSubscription::create([
                'dealer_id' => $dealer->id,
                'plan_id' => $plan->id,
                'subscription_status_id' => $subscriptionStatusId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'auto_renew' => false,
                'billing_cycle' => $billingCycle,
                'created_at' => now(),
            ]);

            try {
                $this->auditLogService->logCreate(
                    $actorUser,
                    'DealerSubscription',
                    $subscription->id,
                    [
                        'plan_id' => $plan->id,
                        'subscription_status_id' => $subscriptionStatusId,
                        'billing_cycle' => $billingCycle,
                        'starts_at' => $startsAt->toIso8601String(),
                        'ends_at' => $endsAt ? $endsAt->toIso8601String() : null,
                    ],
                    $request,
                    'Dealer',
                    $dealer->id,
                    $createAuditNote,
                    ['dealer', 'subscription', 'create', 'purchase']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create audit log for subscription creation', [
                    'subscription_id' => $subscription->id,
                    'dealer_id' => $dealer->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->subscriptionFeatureService->clearCache($dealer);

            if ($isUsagePlan && ! $isFutureStart) {
                $this->listingBillingService->startBillingForPublishedVehicles($dealer, $startsAt);
            }

            $subscriptionFeatures = $this->subscriptionFeatureService->getFeatures($dealer);
            $subscription->load(['plan.features', 'plan.priceHistory', 'subscriptionStatus']);

            return [
                'subscription' => $subscription,
                'subscription_features' => $subscriptionFeatures,
            ];
        });
    }
}
