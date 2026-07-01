<?php

namespace App\Services;

use App\Constants\BillingModel;
use App\Constants\ListingBillingPeriodStatus;
use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\ListingBillingPeriod;
use App\Models\Vehicle;
use Carbon\Carbon;
 
class ListingBillingService
{
    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    public function marketplaceTimezone(): string
    {
        return config('marketplace.timezone', 'Europe/Copenhagen');
    }

    public function onVehiclePublished(Vehicle $vehicle): void
    {
        if (! $vehicle->dealer_id) {
            return;
        }

        $dealer = $vehicle->dealer;
        if (! $dealer) {
            return;
        }

        if (! $this->subscriptionFeatureService->isUsageDailyPlan($dealer)) {
            return;
        }

        $vehicle->update([
            'listing_billing_started_at' => now(),
            'listing_billing_paused_at' => null,
        ]);
    }

    public function onVehicleUnpublished(Vehicle $vehicle): void
    {
        if (! $vehicle->listing_billing_started_at) {
            return;
        }

        $vehicle->update([
            'listing_billing_paused_at' => now(),
        ]);
    }

    /**
     * Start billing for published vehicles that were live before PAYG applied (e.g. bulk import or plan switch).
     */
    public function startBillingForPublishedVehicles(Dealer $dealer, ?Carbon $startedAt = null): int
    {
        if (! $this->subscriptionFeatureService->isUsageDailyPlan($dealer)) {
            return 0;
        }

        $startedAt = ($startedAt ?? now())->copy();

        return Vehicle::query()
            ->where('dealer_id', $dealer->id)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNull('listing_billing_started_at')
            ->update([
                'listing_billing_started_at' => $startedAt,
                'listing_billing_paused_at' => null,
            ]);
    }

    public function chargeForDate(Carbon $billingDate): int
    {
        $billingDate = $billingDate->copy()->timezone($this->marketplaceTimezone())->startOfDay();
        $charged = 0;

        $vehicles = Vehicle::query()
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereNotNull('dealer_id')
            ->whereNotNull('listing_billing_started_at')
            ->where(function ($query) use ($billingDate) {
                $query->whereNull('listing_billing_paused_at')
                    ->orWhereDate('listing_billing_paused_at', '>', $billingDate);
            })
            ->whereDate('listing_billing_started_at', '<=', $billingDate)
            ->with(['dealer'])
            ->get();

        foreach ($vehicles as $vehicle) {
            $dealer = $vehicle->dealer;
            if (! $dealer) {
                continue;
            }

            $subscription = $this->subscriptionFeatureService->getActiveSubscription($dealer);
            $plan = $subscription?->plan;

            if (! $plan || $plan->billing_model !== BillingModel::USAGE_DAILY) {
                continue;
            }

            $amountCents = (int) ($plan->price_per_listing_per_day ?? 0);
            if ($amountCents <= 0) {
                continue;
            }

            $created = ListingBillingPeriod::firstOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'billing_date' => $billingDate->toDateString(),
                ],
                [
                    'dealer_id' => $dealer->id,
                    'plan_id' => $plan->id,
                    'amount_cents' => $amountCents,
                    'status' => ListingBillingPeriodStatus::PENDING,
                    'created_at' => now(),
                ]
            );

            if ($created->wasRecentlyCreated) {
                $charged++;
            }
        }

        return $charged;
    }

    public function getUsageSummary(Dealer $dealer, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = ($from ?? now()->timezone($this->marketplaceTimezone())->startOfMonth())
            ->timezone($this->marketplaceTimezone())->startOfDay();
        $to = ($to ?? now()->timezone($this->marketplaceTimezone()))
            ->timezone($this->marketplaceTimezone())->endOfDay();

        $periods = ListingBillingPeriod::query()
            ->where('dealer_id', $dealer->id)
            ->whereBetween('billing_date', [$from->toDateString(), $to->toDateString()])
            ->with(['vehicle:id,title,registration', 'plan:id,name,price_per_listing_per_day'])
            ->orderBy('billing_date')
            ->get();

        $publishedCount = Vehicle::where('dealer_id', $dealer->id)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();

        $subscription = $this->subscriptionFeatureService->getActiveSubscription($dealer);
        $dailyRate = (int) ($subscription?->plan?->price_per_listing_per_day ?? 0);

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'published_listings' => $publishedCount,
            'daily_rate_cents' => $dailyRate,
            'estimated_monthly_cents' => $publishedCount * $dailyRate * 30,
            'total_charged_cents' => $periods->sum('amount_cents'),
            'pending_cents' => $periods->where('status', ListingBillingPeriodStatus::PENDING)->sum('amount_cents'),
            'invoiced_cents' => $periods->where('status', ListingBillingPeriodStatus::INVOICED)->sum('amount_cents'),
            'billing_periods' => $periods,
            'is_usage_plan' => $this->subscriptionFeatureService->isUsageDailyPlan($dealer),
        ];
    }
}
