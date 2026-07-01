<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\Dealer;
use App\Models\User;
use App\Models\DealerSubscription;
use App\Models\Plan;
use App\Models\PlanPriceHistory;
use App\Models\FeaturedListing;
use App\Models\ListingViewsLog;
use App\Models\Source;
use App\Models\AuditLog;
use App\Constants\LeadCategory as LeadCategoryIds;
use App\Constants\VehicleListStatus;
use App\Constants\SubscriptionStatus;
use App\Services\Analytics\AnalyticsReportingService;
use App\Support\AnalyticsDateRange;
use App\Http\Controllers\Concerns\ExportsAnalyticsCsv;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Admin Analytics Controller
 * Provides comprehensive system-wide analytics
 */
class AdminAnalyticsController extends Controller
{
    use ExportsAnalyticsCsv;

    public function __construct(
        private readonly AnalyticsReportingService $reportingService,
    ) {}

    private function getDateRange(?string $dateRange): array
    {
        $now = Carbon::now();

        return match ($dateRange) {
            '7d' => [$now->copy()->subDays(7), $now],
            '30d' => [$now->copy()->subDays(30), $now],
            '3m' => [$now->copy()->subMonths(3), $now],
            '1y' => [$now->copy()->subYear(), $now],
            'all' => [null, null],
            default => [$now->copy()->subDays(30), $now],
        };
    }

    private function applyDateFilter($query, ?Carbon $startDate, ?Carbon $endDate, string $column = 'created_at')
    {
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }
        if ($endDate) {
            $query->where($column, '<=', $endDate);
        }

        return $query;
    }

    private function mapLeadsByCategory($leadsQuery): array
    {
        $countsById = (clone $leadsQuery)
            ->select('lead_category_id', DB::raw('count(*) as count'))
            ->groupBy('lead_category_id')
            ->pluck('count', 'lead_category_id');

        return [
            'total' => (clone $leadsQuery)->count(),
            'by_type' => [
                'enquiry' => (int) $countsById->get(LeadCategoryIds::ENQUIRY_FORM_SUBMISSION, 0),
                'phone' => (int) $countsById->get(LeadCategoryIds::PHONE_NUMBER_REVEALED, 0),
                'whatsapp' => (int) $countsById->get(LeadCategoryIds::WHATSAPP_CLICKED, 0),
                'email' => (int) $countsById->get(LeadCategoryIds::EMAIL_CLICKED, 0),
                'test_drive' => (int) $countsById->get(LeadCategoryIds::REQUEST_TEST_DRIVE, 0),
                'financing' => (int) $countsById->get(LeadCategoryIds::FINANCING_REQUEST, 0),
            ],
        ];
    }

    public function overview(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $vehicleQuery = Vehicle::query();
        $this->applyDateFilter($vehicleQuery, $startDate, $endDate);

        $totalVehicles = (clone $vehicleQuery)->count();
        $activeVehicles = (clone $vehicleQuery)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();
        $soldVehicles = (clone $vehicleQuery)
            ->where('list_status_id', VehicleListStatus::SOLD)
            ->count();

        $featuredQuery = FeaturedListing::query();
        if ($startDate || $endDate) {
            $featuredQuery->whereHas('vehicle', function ($q) use ($startDate, $endDate) {
                $this->applyDateFilter($q, $startDate, $endDate);
            });
        }
        $featuredVehicles = $featuredQuery->count();

        $dealerQuery = Dealer::query();
        $this->applyDateFilter($dealerQuery, $startDate, $endDate);
        $totalDealers = (clone $dealerQuery)->count();

        $activeDealers = Dealer::whereHas('subscriptions', function ($q) {
            $q->where('subscription_status_id', SubscriptionStatus::ACTIVE);
        })->count();

        $leadQuery = Lead::query();
        $this->applyDateFilter($leadQuery, $startDate, $endDate);
        $leadMetrics = $this->mapLeadsByCategory($leadQuery);

        $conversionRate = $leadMetrics['total'] > 0
            ? round(
                (clone $leadQuery)->whereHas('vehicle', function ($query) {
                    $query->where('list_status_id', VehicleListStatus::SOLD);
                })->count() / $leadMetrics['total'] * 100,
                2
            )
            : 0;

        $sellersQuery = User::whereHas('vehicles', function ($q) use ($startDate, $endDate) {
            $this->applyDateFilter($q, $startDate, $endDate);
        });
        $totalSellers = $sellersQuery->distinct()->count('id');

        return $this->success([
            'vehicles' => [
                'total_listed' => $totalVehicles,
                'active' => $activeVehicles,
                'sold' => $soldVehicles,
                'featured_count' => $featuredVehicles,
            ],
            'dealers' => [
                'total' => $totalDealers,
                'active' => $activeDealers,
            ],
            'sellers' => [
                'total' => $totalSellers,
            ],
            'leads' => $leadMetrics,
            'conversion_rate' => $conversionRate,
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $planPrices = PlanPriceHistory::where(function ($q) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
        })
            ->get()
            ->groupBy('plan_id')
            ->map(fn ($prices) => $prices->first());

        $revenueByPlan = [];
        $totalRevenue = 0;
        $monthlyRecurringRevenue = 0;

        $plans = Plan::with('dealerSubscriptions')->get();
        foreach ($plans as $plan) {
            $currentPrice = $planPrices->get($plan->id);
            if (!$currentPrice) {
                continue;
            }

            $activeSubscriptions = $plan->dealerSubscriptions()
                ->where('subscription_status_id', SubscriptionStatus::ACTIVE)
                ->count();

            $planRevenue = 0;
            if ($currentPrice->billing_cycle === 'monthly') {
                $planRevenue = $activeSubscriptions * $currentPrice->price;
                $monthlyRecurringRevenue += $planRevenue;
            } else {
                $planRevenue = ($activeSubscriptions * $currentPrice->price) / 12;
                $monthlyRecurringRevenue += $planRevenue;
            }

            $totalRevenue += $planRevenue * 12;

            $revenueByPlan[] = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'active_subscriptions' => $activeSubscriptions,
                'price' => $currentPrice->price,
                'billing_cycle' => $currentPrice->billing_cycle,
                'revenue' => $planRevenue,
            ];
        }

        $churnedQuery = DealerSubscription::whereIn('subscription_status_id', [
            SubscriptionStatus::EXPIRED,
            SubscriptionStatus::CANCELED,
        ]);
        $this->applyDateFilter($churnedQuery, $startDate, $endDate, 'updated_at');
        $churnedSubscriptions = $churnedQuery->count();

        $newSubsQuery = DealerSubscription::query();
        $this->applyDateFilter($newSubsQuery, $startDate, $endDate);
        $newSubscriptionsInPeriod = $newSubsQuery->count();

        $activeSubscriptions = DealerSubscription::where('subscription_status_id', SubscriptionStatus::ACTIVE)->count();

        return $this->success([
            'total_subscription_revenue' => $totalRevenue,
            'monthly_recurring_revenue' => round($monthlyRecurringRevenue, 2),
            'revenue_by_plan' => $revenueByPlan,
            'subscriptions' => [
                'active' => $activeSubscriptions,
                'churned' => $churnedSubscriptions,
                'new_in_period' => $newSubscriptionsInPeriod,
            ],
        ]);
    }

    public function dealers(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $topDealersByListings = Dealer::withCount(['vehicles' => function ($query) use ($startDate, $endDate) {
            $this->applyDateFilter($query, $startDate, $endDate);
        }])
            ->orderBy('vehicles_count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($dealer) => [
                'dealer_id' => $dealer->id,
                'cvr' => $dealer->cvr,
                'city' => $dealer->city,
                'listings_count' => $dealer->vehicles_count,
            ]);

        $topDealersByLeads = Dealer::withCount(['leads' => function ($query) use ($startDate, $endDate) {
            $this->applyDateFilter($query, $startDate, $endDate);
        }])
            ->orderBy('leads_count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($dealer) => [
                'dealer_id' => $dealer->id,
                'cvr' => $dealer->cvr,
                'city' => $dealer->city,
                'leads_count' => $dealer->leads_count,
            ]);

        $topDealersBySold = Dealer::withCount(['vehicles' => function ($query) use ($startDate, $endDate) {
            $query->where('list_status_id', VehicleListStatus::SOLD);
            $this->applyDateFilter($query, $startDate, $endDate, 'updated_at');
        }])
            ->orderBy('vehicles_count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($dealer) => [
                'dealer_id' => $dealer->id,
                'cvr' => $dealer->cvr,
                'city' => $dealer->city,
                'sold_count' => $dealer->vehicles_count,
            ]);

        $dealersWithResponseTime = Dealer::with(['leads' => function ($query) use ($startDate, $endDate) {
            $query->whereNotNull('last_activity_at')
                ->whereColumn('last_activity_at', '!=', 'created_at')
                ->select('dealer_id', 'created_at', 'last_activity_at');
            $this->applyDateFilter($query, $startDate, $endDate);
        }])->get();

        $dealerResponseTimes = [];
        foreach ($dealersWithResponseTime as $dealer) {
            if ($dealer->leads->isEmpty()) {
                continue;
            }

            $totalResponseTime = 0;
            $count = 0;
            foreach ($dealer->leads as $lead) {
                $totalResponseTime += $lead->created_at->diffInHours($lead->last_activity_at);
                $count++;
            }

            if ($count > 0) {
                $dealerResponseTimes[] = [
                    'dealer_id' => $dealer->id,
                    'cvr' => $dealer->cvr,
                    'average_response_time_hours' => round($totalResponseTime / $count, 2),
                ];
            }
        }

        usort($dealerResponseTimes, fn ($a, $b) => $a['average_response_time_hours'] <=> $b['average_response_time_hours']);

        $activityTrend = [];
        $periods = $this->getPeriods($startDate, $endDate);
        foreach ($periods as $period) {
            $count = Dealer::whereBetween('created_at', [$period['start'], $period['end']])->count();
            $activityTrend[] = [
                'date' => $period['date'],
                'count' => $count,
            ];
        }

        return $this->success([
            'top_by_listings' => $topDealersByListings,
            'top_by_leads' => $topDealersByLeads,
            'top_by_sold' => $topDealersBySold,
            'average_response_times' => array_slice($dealerResponseTimes, 0, 10),
            'activity_trend' => $activityTrend,
        ]);
    }

    public function vehicles(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $categoryQuery = DB::table('vehicles as v')
            ->leftJoin('dmr_fact_vehicles as dfv', 'v.dmr_fact_vehicle_id', '=', 'dfv.id')
            ->leftJoin('dmr_body_types as dbt', 'dfv.body_type_id', '=', 'dbt.id')
            ->whereNull('v.deleted_at');
        if ($startDate) {
            $categoryQuery->where('v.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $categoryQuery->where('v.created_at', '<=', $endDate);
        }
        $vehiclesByCategory = $categoryQuery
            ->select(DB::raw('COALESCE(dbt.name, "Unknown") as category_name'), DB::raw('count(*) as count'))
            ->groupBy('dbt.id', 'dbt.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'category' => $item->category_name,
                'count' => $item->count,
            ]);

        $fuelQuery = DB::table('vehicles as v')
            ->leftJoin('dmr_fact_vehicles as dfv', 'v.dmr_fact_vehicle_id', '=', 'dfv.id')
            ->leftJoin('dmr_bridge_vehicle_drivmiddel as dbvd', function ($join) {
                $join->on('dfv.id', '=', 'dbvd.vehicle_id')
                    ->where('dbvd.drivmiddel_primaer', '=', 1);
            })
            ->leftJoin('dmr_drive_energies as dde', 'dbvd.drive_energy_id', '=', 'dde.id')
            ->whereNull('v.deleted_at');
        if ($startDate) {
            $fuelQuery->where('v.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $fuelQuery->where('v.created_at', '<=', $endDate);
        }
        $vehiclesByFuelType = $fuelQuery
            ->select(DB::raw('COALESCE(dde.name, "Unknown") as fuel_name'), DB::raw('count(*) as count'))
            ->groupBy('dde.id', 'dde.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'fuel_type' => $item->fuel_name,
                'count' => $item->count,
            ]);

        $priceRanges = [
            ['min' => 0, 'max' => 100000, 'label' => '0-100k'],
            ['min' => 100000, 'max' => 200000, 'label' => '100k-200k'],
            ['min' => 200000, 'max' => 300000, 'label' => '200k-300k'],
            ['min' => 300000, 'max' => 500000, 'label' => '300k-500k'],
            ['min' => 500000, 'max' => null, 'label' => '500k+'],
        ];

        $vehiclesByPriceRange = [];
        foreach ($priceRanges as $range) {
            $query = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED);
            if ($range['min'] !== null) {
                $query->where('price', '>=', $range['min']);
            }
            if ($range['max'] !== null) {
                $query->where('price', '<', $range['max']);
            }
            $this->applyDateFilter($query, $startDate, $endDate);
            $vehiclesByPriceRange[] = [
                'range' => $range['label'],
                'count' => $query->count(),
            ];
        }

        $soldVehiclesQuery = Vehicle::where('list_status_id', VehicleListStatus::SOLD)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at');
        $this->applyDateFilter($soldVehiclesQuery, $startDate, $endDate, 'updated_at');
        $soldVehicles = $soldVehiclesQuery->get();

        $totalDays = 0;
        $soldCount = 0;
        foreach ($soldVehicles as $vehicle) {
            $totalDays += $vehicle->created_at->diffInDays($vehicle->updated_at);
            $soldCount++;
        }
        $averageDaysToSell = $soldCount > 0 ? round($totalDays / $soldCount, 2) : 0;

        $viewsQuery = ListingViewsLog::query();
        $this->applyDateFilter($viewsQuery, $startDate, $endDate, 'viewed_at');

        $mostViewedVehicles = (clone $viewsQuery)
            ->select('vehicle_id', DB::raw('count(*) as view_count'))
            ->groupBy('vehicle_id')
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $vehicle = Vehicle::find($item->vehicle_id);

                return [
                    'vehicle_id' => $item->vehicle_id,
                    'title' => $vehicle?->title ?? 'Unknown',
                    'registration' => $vehicle?->registration ?? 'N/A',
                    'price' => $vehicle?->price ?? 0,
                    'view_count' => $item->view_count,
                ];
            });

        return $this->success([
            'by_category' => $vehiclesByCategory,
            'by_fuel_type' => $vehiclesByFuelType,
            'by_price_range' => $vehiclesByPriceRange,
            'average_days_to_sell' => $averageDaysToSell,
            'most_viewed' => $mostViewedVehicles,
        ]);
    }

    public function leads(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $leadBase = Lead::query();
        $this->applyDateFilter($leadBase, $startDate, $endDate);

        $leadsOverTime = [];
        $periods = $this->getPeriods($startDate, $endDate);
        foreach ($periods as $period) {
            $count = Lead::whereBetween('created_at', [$period['start'], $period['end']])->count();
            $leadsOverTime[] = [
                'date' => $period['date'],
                'count' => $count,
            ];
        }

        $leadsBySource = (clone $leadBase)
            ->select('source_id', DB::raw('count(*) as count'))
            ->groupBy('source_id')
            ->get()
            ->map(function ($item) {
                $source = Source::find($item->source_id);

                return [
                    'source_id' => $item->source_id,
                    'source' => $source?->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        $leadsByVehicle = (clone $leadBase)
            ->select('vehicle_id', DB::raw('count(*) as lead_count'))
            ->groupBy('vehicle_id')
            ->orderBy('lead_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $vehicle = Vehicle::find($item->vehicle_id);

                return [
                    'vehicle_id' => $item->vehicle_id,
                    'title' => $vehicle?->title ?? 'Unknown',
                    'registration' => $vehicle?->registration ?? 'N/A',
                    'lead_count' => $item->lead_count,
                ];
            });

        $totalLeads = (clone $leadBase)->count();
        $leadsThatConverted = (clone $leadBase)
            ->whereHas('vehicle', function ($query) {
                $query->where('list_status_id', VehicleListStatus::SOLD);
            })
            ->count();
        $conversionRate = $totalLeads > 0
            ? round(($leadsThatConverted / $totalLeads) * 100, 2)
            : 0;

        $unansweredLeads = Lead::query();
        $this->applyDateFilter($unansweredLeads, $startDate, $endDate);
        $unansweredCount = $unansweredLeads
            ->where(function ($q) {
                $q->whereNull('last_activity_at')
                    ->orWhereColumn('last_activity_at', 'created_at');
            })
            ->count();

        return $this->success([
            'over_time' => $leadsOverTime,
            'by_source' => $leadsBySource,
            'by_vehicle' => $leadsByVehicle,
            'conversion_rate' => $conversionRate,
            'unanswered_count' => $unansweredCount,
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $loginActivity = [];
        $periods = $this->getPeriods($startDate, $endDate);
        foreach ($periods as $period) {
            $count = AuditLog::where('action', 'login')
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->count();
            $loginActivity[] = [
                'date' => $period['date'],
                'count' => $count,
            ];
        }

        $listingTrends = [];
        foreach ($periods as $period) {
            $count = Vehicle::whereBetween('created_at', [$period['start'], $period['end']])->count();
            $listingTrends[] = [
                'date' => $period['date'],
                'count' => $count,
            ];
        }

        $featureUsageQuery = AuditLog::select('action', DB::raw('count(*) as count'));
        if ($startDate) {
            $featureUsageQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $featureUsageQuery->where('created_at', '<=', $endDate);
        }
        $featureUsage = $featureUsageQuery
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'action' => $item->action,
                'count' => $item->count,
            ]);

        return $this->success([
            'login_activity' => $loginActivity,
            'listing_creation_trends' => $listingTrends,
            'feature_usage' => $featureUsage,
        ]);
    }

    public function funnel(Request $request): JsonResponse
    {
        [$startDate, $endDate] = AnalyticsDateRange::resolve($request->get('date_range', '30d'));

        return $this->success(
            $this->reportingService->funnel(null, $startDate, $endDate, $request->boolean('compare'))
        );
    }

    public function cohort(): JsonResponse
    {
        return $this->success($this->reportingService->cohortAnalysis());
    }

    public function integrations(Request $request): JsonResponse
    {
        [$startDate, $endDate] = AnalyticsDateRange::resolve($request->get('date_range', '30d'));

        return $this->success($this->reportingService->platformIntegrations($startDate, $endDate));
    }

    public function trends(Request $request): JsonResponse
    {
        [$startDate, $endDate] = AnalyticsDateRange::resolve($request->get('date_range', '30d'));

        return $this->success($this->reportingService->dailyTrends(null, $startDate, $endDate));
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $report = $request->get('report', 'funnel');
        [$startDate, $endDate] = AnalyticsDateRange::resolve($request->get('date_range', '30d'));

        if ($report === 'cohort') {
            $cohorts = $this->reportingService->cohortAnalysis()['cohorts'];
            $rows = [['cohort_month', 'signups', 'still_active', 'retention_rate']];
            foreach ($cohorts as $row) {
                $rows[] = [$row['cohort_month'], $row['signups'], $row['still_active'], $row['retention_rate']];
            }

            return $this->csvDownload($rows, 'platform-cohort-'.now()->format('Y-m-d').'.csv');
        }

        $rows = $this->reportingService->exportDealerRows(0, $report, $startDate, $endDate);

        return $this->csvDownload($rows, "platform-analytics-{$report}-".now()->format('Y-m-d').'.csv');
    }

    private function getPeriods(?Carbon $startDate, ?Carbon $endDate): array
    {
        if (!$startDate || !$endDate) {
            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now();
        }

        $daysDiff = $startDate->diffInDays($endDate);

        if ($daysDiff <= 7) {
            $periods = [];
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $periods[] = [
                    'date' => $current->format('Y-m-d'),
                    'start' => $current->copy()->startOfDay(),
                    'end' => $current->copy()->endOfDay(),
                ];
                $current->addDay();
            }

            return $periods;
        }

        if ($daysDiff <= 90) {
            $periods = [];
            $current = $startDate->copy()->startOfWeek();
            while ($current <= $endDate) {
                $periods[] = [
                    'date' => $current->format('Y-m-d'),
                    'start' => $current->copy()->startOfWeek(),
                    'end' => $current->copy()->endOfWeek(),
                ];
                $current->addWeek();
            }

            return $periods;
        }

        $periods = [];
        $current = $startDate->copy()->startOfMonth();
        while ($current <= $endDate) {
            $periods[] = [
                'date' => $current->format('Y-m'),
                'start' => $current->copy()->startOfMonth(),
                'end' => $current->copy()->endOfMonth(),
            ];
            $current->addMonth();
        }

        return $periods;
    }
}
