<?php

namespace App\Services\Analytics;

use App\Constants\PaymentStatus;
use App\Constants\SubscriptionStatus;
use App\Constants\VehicleListStatus;
use App\Models\AiUsageLog;
use App\Models\AnalyticsDailyDealer;
use App\Models\AnalyticsDailyPlatform;
use App\Models\Dealer;
use App\Models\DealerInvoice;
use App\Models\DealerSubscription;
use App\Models\Enquiry;
use App\Models\Lead;
use App\Models\ListingBillingPeriod;
use App\Models\ListingViewsLog;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Support\AnalyticsLeadMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsAggregationService
{
    public function aggregateDay(Carbon $day): void
    {
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();
        $dateString = $day->toDateString();

        $this->aggregatePlatformDay($dateString, $start, $end);
        $this->aggregateDealerDays($dateString, $start, $end);
    }

    private function aggregatePlatformDay(string $dateString, Carbon $start, Carbon $end): void
    {
        $views = ListingViewsLog::whereBetween('viewed_at', [$start, $end])->count();
        $enquiries = Enquiry::whereBetween('created_at', [$start, $end])->count();
        $leads = Lead::whereBetween('created_at', [$start, $end])->count();
        $leadsWon = AnalyticsLeadMetrics::countWonInPeriod(null, $start, $end);

        $vehiclesPublished = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)
            ->whereBetween('published_at', [$start, $end])
            ->count();
        $vehiclesSold = Vehicle::where('list_status_id', VehicleListStatus::SOLD)
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $activeDealers = DealerSubscription::where('subscription_status_id', SubscriptionStatus::ACTIVE)
            ->where('starts_at', '<=', $end)
            ->where(function ($q) use ($end) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $end);
            })
            ->distinct('dealer_id')
            ->count('dealer_id');

        $newDealers = Dealer::whereBetween('created_at', [$start, $end])->count();

        $paymentsSucceeded = Payment::where('status', PaymentStatus::SUCCEEDED)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $paymentsFailed = Payment::where('status', PaymentStatus::FAILED)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $paymentVolume = (int) Payment::where('status', PaymentStatus::SUCCEEDED)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount_cents');

        $aiRequests = AiUsageLog::where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        AnalyticsDailyPlatform::updateOrCreate(
            ['date' => $dateString],
            [
                'views_count' => $views,
                'enquiries_count' => $enquiries,
                'leads_count' => $leads,
                'leads_won_count' => $leadsWon,
                'vehicles_published' => $vehiclesPublished,
                'vehicles_sold' => $vehiclesSold,
                'active_dealers' => $activeDealers,
                'new_dealers' => $newDealers,
                'payments_succeeded' => $paymentsSucceeded,
                'payments_failed' => $paymentsFailed,
                'payment_volume_cents' => $paymentVolume,
                'ai_requests' => $aiRequests,
            ]
        );
    }

    private function aggregateDealerDays(string $dateString, Carbon $start, Carbon $end): void
    {
        $dealerIds = collect()
            ->merge(
                ListingViewsLog::query()
                    ->whereBetween('viewed_at', [$start, $end])
                    ->join('vehicles', 'listing_views_log.vehicle_id', '=', 'vehicles.id')
                    ->pluck('vehicles.dealer_id')
            )
            ->merge(Lead::whereBetween('created_at', [$start, $end])->pluck('dealer_id'))
            ->merge(
                Enquiry::whereBetween('created_at', [$start, $end])
                    ->whereHas('vehicle')
                    ->with('vehicle:id,dealer_id')
                    ->get()
                    ->pluck('vehicle.dealer_id')
            )
            ->merge(Vehicle::whereBetween('published_at', [$start, $end])->pluck('dealer_id'))
            ->filter()
            ->unique()
            ->values();

        foreach ($dealerIds as $dealerId) {
            $views = ListingViewsLog::whereBetween('viewed_at', [$start, $end])
                ->whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId))
                ->count();

            $enquiries = Enquiry::whereBetween('created_at', [$start, $end])
                ->whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId))
                ->count();

            $leads = Lead::where('dealer_id', $dealerId)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $leadsWon = AnalyticsLeadMetrics::countWonInPeriod((int) $dealerId, $start, $end);

            $vehiclesPublished = Vehicle::where('dealer_id', $dealerId)
                ->whereBetween('published_at', [$start, $end])
                ->count();

            $vehiclesSold = Vehicle::where('dealer_id', $dealerId)
                ->where('list_status_id', VehicleListStatus::SOLD)
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            $paygCents = (int) ListingBillingPeriod::where('dealer_id', $dealerId)
                ->whereBetween('billing_date', [$start->toDateString(), $end->toDateString()])
                ->sum('amount_cents');

            $paymentCents = (int) Payment::where('dealer_id', $dealerId)
                ->where('status', PaymentStatus::SUCCEEDED)
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount_cents');

            AnalyticsDailyDealer::updateOrCreate(
                ['dealer_id' => $dealerId, 'date' => $dateString],
                [
                    'views_count' => $views,
                    'enquiries_count' => $enquiries,
                    'leads_count' => $leads,
                    'leads_won_count' => $leadsWon,
                    'vehicles_published' => $vehiclesPublished,
                    'vehicles_sold' => $vehiclesSold,
                    'payg_cents' => $paygCents,
                    'payment_cents' => $paymentCents,
                ]
            );
        }
    }

    public function backfill(int $days = 90): int
    {
        $count = 0;
        for ($i = $days; $i >= 1; $i--) {
            $this->aggregateDay(Carbon::now()->subDays($i));
            $count++;
        }

        return $count;
    }
}
