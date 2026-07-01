<?php

namespace App\Services\Analytics;

use App\Constants\LeadCategory as LeadCategoryIds;
use App\Constants\LeadStage;
use App\Constants\PaymentStatus;
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
use App\Models\PriceHistory;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\AnalyticsDateRange;
use App\Support\AnalyticsLeadMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsReportingService
{
    /**
     * @return array<string, mixed>
     */
    public function funnel(?int $dealerId, ?Carbon $startDate, ?Carbon $endDate, bool $withComparison = false): array
    {
        $current = $this->buildFunnelMetrics($dealerId, $startDate, $endDate);
        $result = [
            'current' => $current,
            'rates' => $this->funnelRates($current),
        ];

        if ($withComparison && $startDate && $endDate) {
            [$prevStart, $prevEnd] = AnalyticsDateRange::previousPeriod($startDate, $endDate);
            $previous = $this->buildFunnelMetrics($dealerId, $prevStart, $prevEnd);
            $result['previous'] = $previous;
            $result['previous_rates'] = $this->funnelRates($previous);
        }

        return $result;
    }

    /**
     * @return array{views: int, enquiries: int, leads: int, won: int}
     */
    private function buildFunnelMetrics(?int $dealerId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $viewsQuery = ListingViewsLog::query();
        if ($dealerId) {
            $viewsQuery->whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId));
        }
        AnalyticsDateRange::apply($viewsQuery, $startDate, $endDate, 'viewed_at');
        $views = $viewsQuery->count();

        $enquiriesQuery = Enquiry::query();
        if ($dealerId) {
            $enquiriesQuery->whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId));
        }
        AnalyticsDateRange::apply($enquiriesQuery, $startDate, $endDate);
        $enquiries = $enquiriesQuery->count();

        $leadsQuery = Lead::query();
        if ($dealerId) {
            $leadsQuery->where('dealer_id', $dealerId);
        }
        AnalyticsDateRange::apply($leadsQuery, $startDate, $endDate);
        $leads = $leadsQuery->count();

        $won = AnalyticsLeadMetrics::countWonInPeriod($dealerId, $startDate, $endDate);

        return compact('views', 'enquiries', 'leads', 'won');
    }

    /**
     * @param  array{views: int, enquiries: int, leads: int, won: int}  $metrics
     * @return array<string, float>
     */
    private function funnelRates(array $metrics): array
    {
        $pct = fn (int $num, int $den) => $den > 0 ? round(($num / $den) * 100, 2) : 0.0;

        return [
            'view_to_enquiry' => $pct($metrics['enquiries'], $metrics['views']),
            'enquiry_to_lead' => $pct($metrics['leads'], max(1, $metrics['enquiries'])),
            'lead_to_won' => $pct($metrics['won'], max(1, $metrics['leads'])),
            'view_to_won' => $pct($metrics['won'], max(1, $metrics['views'])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assigneePerformance(int $dealerId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $leadBase = Lead::where('dealer_id', $dealerId);
        AnalyticsDateRange::apply($leadBase, $startDate, $endDate);

        $rows = (clone $leadBase)
            ->select(
                'assigned_user_id',
                DB::raw('count(*) as total_leads'),
                DB::raw('sum(case when lead_stage_id = '.LeadStage::WON.' then 1 else 0 end) as won_leads'),
                DB::raw('sum(case when first_contacted_at is not null then 1 else 0 end) as contacted_leads')
            )
            ->groupBy('assigned_user_id')
            ->get();

        $assignees = [];
        foreach ($rows as $row) {
            $user = $row->assigned_user_id ? User::find($row->assigned_user_id) : null;
            $contactTimes = Lead::where('dealer_id', $dealerId)
                ->where('assigned_user_id', $row->assigned_user_id)
                ->whereNotNull('first_contacted_at');
            AnalyticsDateRange::apply($contactTimes, $startDate, $endDate);

            $totalHours = 0;
            $contactCount = 0;
            foreach ($contactTimes->get() as $lead) {
                $totalHours += $lead->created_at->diffInHours($lead->first_contacted_at);
                $contactCount++;
            }

            $assignees[] = [
                'user_id' => $row->assigned_user_id,
                'name' => $user?->name ?? __('messages.analytics.unassigned'),
                'total_leads' => (int) $row->total_leads,
                'won_leads' => (int) $row->won_leads,
                'contacted_leads' => (int) $row->contacted_leads,
                'win_rate' => $row->total_leads > 0
                    ? round(($row->won_leads / $row->total_leads) * 100, 2)
                    : 0,
                'avg_time_to_contact_hours' => $contactCount > 0
                    ? round($totalHours / $contactCount, 2)
                    : null,
            ];
        }

        usort($assignees, fn ($a, $b) => $b['total_leads'] <=> $a['total_leads']);

        return ['assignees' => $assignees];
    }

    /**
     * @return array<string, mixed>
     */
    public function stockMetrics(int $dealerId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $published = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();

        $soldQuery = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::SOLD);
        AnalyticsDateRange::apply($soldQuery, $startDate, $endDate, 'updated_at');
        $soldInPeriod = $soldQuery->count();

        $listedInPeriod = Vehicle::where('dealer_id', $dealerId);
        AnalyticsDateRange::apply($listedInPeriod, $startDate, $endDate);
        $newListings = $listedInPeriod->count();

        $soldRate = $newListings > 0 ? round(($soldInPeriod / $newListings) * 100, 2) : 0;

        $agingBuckets = [
            ['label' => '0-30', 'min' => 0, 'max' => 30],
            ['label' => '31-60', 'min' => 31, 'max' => 60],
            ['label' => '61-90', 'min' => 61, 'max' => 90],
            ['label' => '90+', 'min' => 91, 'max' => null],
        ];

        $inventoryAging = [];
        $now = Carbon::now();
        foreach ($agingBuckets as $bucket) {
            $query = Vehicle::where('dealer_id', $dealerId)
                ->where('list_status_id', VehicleListStatus::PUBLISHED)
                ->whereNotNull('published_at');

            if ($bucket['max'] !== null) {
                $query->where('published_at', '>=', $now->copy()->subDays($bucket['max']))
                    ->where('published_at', '<=', $now->copy()->subDays($bucket['min']));
            } else {
                $query->where('published_at', '<=', $now->copy()->subDays($bucket['min']));
            }

            $inventoryAging[] = [
                'bucket' => $bucket['label'],
                'count' => $query->count(),
            ];
        }

        $priceDropsQuery = PriceHistory::whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId))
            ->whereColumn('new_price', '<', 'old_price');
        AnalyticsDateRange::apply($priceDropsQuery, $startDate, $endDate, 'changed_at');
        $priceDrops = $priceDropsQuery->count();

        $avgDaysOnMarket = 0;
        $soldVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::SOLD)
            ->whereNotNull('published_at');
        AnalyticsDateRange::apply($soldVehicles, $startDate, $endDate, 'updated_at');
        $soldList = $soldVehicles->get();
        if ($soldList->isNotEmpty()) {
            $totalDays = $soldList->sum(fn ($v) => $v->published_at?->diffInDays($v->updated_at) ?? 0);
            $avgDaysOnMarket = round($totalDays / $soldList->count(), 1);
        }

        return [
            'published_inventory' => $published,
            'sold_in_period' => $soldInPeriod,
            'new_listings_in_period' => $newListings,
            'sold_rate_percent' => $soldRate,
            'inventory_aging' => $inventoryAging,
            'price_drops_in_period' => $priceDrops,
            'average_days_on_market' => $avgDaysOnMarket,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function paygBurn(int $dealerId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $usageQuery = ListingBillingPeriod::where('dealer_id', $dealerId);
        if ($startDate) {
            $usageQuery->where('billing_date', '>=', $startDate->toDateString());
        }
        if ($endDate) {
            $usageQuery->where('billing_date', '<=', $endDate->toDateString());
        }

        $pendingCents = (int) (clone $usageQuery)->where('status', 'pending')->sum('amount_cents');
        $invoicedCents = (int) DealerInvoice::where('dealer_id', $dealerId)
            ->when($startDate, fn ($q) => $q->where('period_start', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('period_end', '<=', $endDate))
            ->sum('total_cents');

        $paidQuery = Payment::where('dealer_id', $dealerId)
            ->where('status', PaymentStatus::SUCCEEDED);
        AnalyticsDateRange::apply($paidQuery, $startDate, $endDate);
        $paidCents = (int) $paidQuery->sum('amount_cents');

        return [
            'pending_usage_cents' => $pendingCents,
            'invoiced_cents' => $invoicedCents,
            'paid_cents' => $paidCents,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dailyTrends(?int $dealerId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        if (! $startDate || ! $endDate) {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        if ($dealerId) {
            $rows = AnalyticsDailyDealer::where('dealer_id', $dealerId)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('date')
                ->get();
        } else {
            $rows = AnalyticsDailyPlatform::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('date')
                ->get();
        }

        return [
            'series' => $rows->map(fn ($row) => [
                'date' => $row->date->format('Y-m-d'),
                'views' => $row->views_count,
                'enquiries' => $row->enquiries_count,
                'leads' => $row->leads_count,
                'won' => $row->leads_won_count,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cohortAnalysis(): array
    {
        $cohorts = Dealer::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as cohort_month"),
            DB::raw('count(*) as signups')
        )
            ->groupBy('cohort_month')
            ->orderBy('cohort_month')
            ->get()
            ->map(function ($row) {
                $monthStart = Carbon::createFromFormat('Y-m', $row->cohort_month)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();

                $stillActive = DealerSubscription::where('subscription_status_id', \App\Constants\SubscriptionStatus::ACTIVE)
                    ->whereHas('dealer', fn ($q) => $q->whereBetween('created_at', [$monthStart, $monthEnd]))
                    ->distinct('dealer_id')
                    ->count('dealer_id');

                return [
                    'cohort_month' => $row->cohort_month,
                    'signups' => (int) $row->signups,
                    'still_active' => $stillActive,
                    'retention_rate' => $row->signups > 0
                        ? round(($stillActive / $row->signups) * 100, 2)
                        : 0,
                ];
            });

        return ['cohorts' => $cohorts];
    }

    /**
     * @return array<string, mixed>
     */
    public function platformIntegrations(?Carbon $startDate, ?Carbon $endDate): array
    {
        $paymentsBase = Payment::query();
        AnalyticsDateRange::apply($paymentsBase, $startDate, $endDate);

        $succeeded = (clone $paymentsBase)->where('status', PaymentStatus::SUCCEEDED)->count();
        $failed = (clone $paymentsBase)->where('status', PaymentStatus::FAILED)->count();
        $total = $succeeded + $failed;
        $volume = (int) (clone $paymentsBase)->where('status', PaymentStatus::SUCCEEDED)->sum('amount_cents');

        $aiBase = AiUsageLog::query();
        AnalyticsDateRange::apply($aiBase, $startDate, $endDate, 'created_at');

        $aiSuccess = (clone $aiBase)->where('status', 'success')->count();
        $aiFailed = (clone $aiBase)->where('status', 'failed')->count();
        $aiTokens = (int) (clone $aiBase)->where('status', 'success')
            ->selectRaw('COALESCE(SUM(prompt_tokens + completion_tokens), 0) as total')
            ->value('total');

        $byProvider = (clone $aiBase)->where('status', 'success')
            ->select('provider', DB::raw('count(*) as count'))
            ->groupBy('provider')
            ->get()
            ->map(fn ($r) => ['provider' => $r->provider, 'count' => (int) $r->count]);

        return [
            'payments' => [
                'succeeded' => $succeeded,
                'failed' => $failed,
                'success_rate' => $total > 0 ? round(($succeeded / $total) * 100, 2) : 0,
                'volume_cents' => $volume,
            ],
            'ai' => [
                'requests_succeeded' => $aiSuccess,
                'requests_failed' => $aiFailed,
                'tokens_used' => $aiTokens,
                'by_provider' => $byProvider,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function marketingBreakdown(int $dealerId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $leadBase = Lead::where('dealer_id', $dealerId);
        AnalyticsDateRange::apply($leadBase, $startDate, $endDate);

        $countsById = (clone $leadBase)
            ->select('lead_category_id', DB::raw('count(*) as count'))
            ->groupBy('lead_category_id')
            ->pluck('count', 'lead_category_id');

        return [
            'by_channel' => [
                ['channel' => 'enquiry_form', 'count' => (int) $countsById->get(LeadCategoryIds::ENQUIRY_FORM_SUBMISSION, 0)],
                ['channel' => 'phone', 'count' => (int) $countsById->get(LeadCategoryIds::PHONE_NUMBER_REVEALED, 0)],
                ['channel' => 'whatsapp', 'count' => (int) $countsById->get(LeadCategoryIds::WHATSAPP_CLICKED, 0)],
                ['channel' => 'email', 'count' => (int) $countsById->get(LeadCategoryIds::EMAIL_CLICKED, 0)],
                ['channel' => 'test_drive', 'count' => (int) $countsById->get(LeadCategoryIds::REQUEST_TEST_DRIVE, 0)],
                ['channel' => 'financing', 'count' => (int) $countsById->get(LeadCategoryIds::FINANCING_REQUEST, 0)],
            ],
        ];
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    public function exportDealerRows(int $dealerId, string $report, ?Carbon $startDate, ?Carbon $endDate): array
    {
        return match ($report) {
            'funnel' => $this->exportFunnelRows($this->funnel($dealerId, $startDate, $endDate)),
            'assignees' => $this->exportAssigneeRows($this->assigneePerformance($dealerId, $startDate, $endDate)),
            'stock' => $this->exportStockRows($this->stockMetrics($dealerId, $startDate, $endDate)),
            default => $this->exportFunnelRows($this->funnel($dealerId, $startDate, $endDate)),
        };
    }

    /**
     * @param  array<string, mixed>  $funnel
     * @return list<list<string|int|float>>
     */
    private function exportFunnelRows(array $funnel): array
    {
        $rows = [['metric', 'value']];
        foreach ($funnel['current'] as $key => $value) {
            $rows[] = [$key, $value];
        }
        foreach ($funnel['rates'] as $key => $value) {
            $rows[] = [$key.'_rate', $value];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|int|float|null>>
     */
    private function exportAssigneeRows(array $data): array
    {
        $rows = [['name', 'total_leads', 'won_leads', 'win_rate', 'avg_time_to_contact_hours']];
        foreach ($data['assignees'] as $row) {
            $rows[] = [
                $row['name'],
                $row['total_leads'],
                $row['won_leads'],
                $row['win_rate'],
                $row['avg_time_to_contact_hours'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<list<string|int|float>>
     */
    private function exportStockRows(array $data): array
    {
        $rows = [['metric', 'value']];
        foreach (['published_inventory', 'sold_in_period', 'new_listings_in_period', 'sold_rate_percent', 'price_drops_in_period', 'average_days_on_market'] as $key) {
            $rows[] = [$key, $data[$key] ?? 0];
        }

        return $rows;
    }
}
