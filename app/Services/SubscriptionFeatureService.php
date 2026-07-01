<?php

namespace App\Services;

use App\Constants\BillingModel;
use App\Constants\ListingBillingPeriodStatus;
use App\Constants\SubscriptionStatus;
use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\DealerPlanOverride;
use App\Models\DealerSubscription;
use App\Models\ListingBillingPeriod;
use App\Models\Plan;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionFeatureService
{
    public function getActiveSubscription(Dealer $dealer): ?DealerSubscription
    {
        $now = now();

        return $dealer->subscriptions()
            ->with('plan')
            ->whereIn('subscription_status_id', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
            ->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $now);
            })
            ->orderByDesc('created_at')
            ->first();
    }

    public function hasActiveSubscription(Dealer $dealer): bool
    {
        return $this->getActiveSubscription($dealer) !== null;
    }

    public function isUsageDailyPlan(Dealer $dealer): bool
    {
        $subscription = $this->getActiveSubscription($dealer);

        return $subscription?->plan?->billing_model === BillingModel::USAGE_DAILY;
    }

    public function getFeatures(Dealer $dealer): array
    {
        $cacheKey = "dealer_features_{$dealer->id}";

        return Cache::remember($cacheKey, 60, function () use ($dealer) {
            $subscription = $this->getActiveSubscription($dealer);

            if (! $subscription || ! $subscription->plan) {
                return [];
            }

            $planFeatures = $subscription->plan->planFeatures()
                ->with('feature.featureValueType')
                ->get();

            $features = [];
            foreach ($planFeatures as $planFeature) {
                if ($planFeature->feature) {
                    $features[$planFeature->feature->key] = $planFeature->value;
                }
            }

            $overrides = DealerPlanOverride::query()
                ->where('dealer_id', $dealer->id)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->with('feature')
                ->get();

            foreach ($overrides as $override) {
                if ($override->feature) {
                    $features[$override->feature->key] = $override->override_value;
                }
            }

            return $features;
        });
    }

    public function getFeature(Dealer $dealer, string $key, $default = null)
    {
        $features = $this->getFeatures($dealer);

        return $features[$key] ?? $default;
    }

    public function hasFeature(Dealer $dealer, string $key): bool
    {
        $value = $this->getFeature($dealer, $key, 'false');

        if (is_bool($value)) {
            return $value;
        }

        return strtolower((string) $value) === 'true' || $value === '1';
    }

    public function getFeatureLimit(Dealer $dealer, string $key, int $default = 0): int
    {
        $value = $this->getFeature($dealer, $key, $default);

        return (int) $value;
    }

    public function checkFeatureLimit(Dealer $dealer, string $key, int $currentCount): bool
    {
        if ($this->isUsageDailyPlan($dealer) && $key === 'max_listings') {
            return $this->hasActiveSubscription($dealer);
        }

        $limit = $this->getFeatureLimit($dealer, $key, 0);

        if ($limit === 0) {
            return false;
        }

        if ($limit >= 9999) {
            return true;
        }

        return $currentCount < $limit;
    }

    public function clearCache(Dealer $dealer): void
    {
        Cache::forget("dealer_features_{$dealer->id}");
    }
}
