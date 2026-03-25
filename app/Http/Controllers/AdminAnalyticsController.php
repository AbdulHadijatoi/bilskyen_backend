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
use App\Models\PriceHistory;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\AuditLog;
use App\Constants\VehicleListStatus;
use App\Constants\SubscriptionStatus;
use App\Constants\LeadStage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Admin Analytics Controller
 * Provides comprehensive system-wide analytics
 */
class AdminAnalyticsController extends Controller
{
    /**
     * Get date range from request
     */
    private function getDateRange(?string $dateRange): array
    {
        $now = Carbon::now();
        
        return match ($dateRange) {
            '7d' => [$now->copy()->subDays(7), $now],
            '30d' => [$now->copy()->subDays(30), $now],
            '3m' => [$now->copy()->subMonths(3), $now],
            '1y' => [$now->copy()->subYear(), $now],
            'all' => [null, null],
            default => [$now->copy()->subDays(30), $now], // Default to 30 days
        };
    }

    /**
     * Apply date filter to query
     */
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

    /**
     * Get overview analytics - Key metrics dashboard
     */
    public function overview(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Vehicle metrics
        $vehicleQuery = Vehicle::query();
        if ($startDate) {
            $vehicleQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $vehicleQuery->where('created_at', '<=', $endDate);
        }

        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('vehicle_list_status_id', VehicleListStatus::PUBLISHED)->count();
        $soldVehicles = Vehicle::where('vehicle_list_status_id', VehicleListStatus::SOLD)->count();
        $featuredVehicles = FeaturedListing::count();

        // Dealer metrics
        $totalDealers = Dealer::count();
        $activeDealers = Dealer::whereHas('subscriptions', function ($q) {
            $q->where('subscription_status_id', SubscriptionStatus::ACTIVE);
        })->count();

        // Lead metrics by category
        $leadsByCategory = Lead::select('lead_category_id', DB::raw('count(*) as count'))
            ->groupBy('lead_category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $category = LeadCategory::find($item->lead_category_id);
                return [$category?->name ?? 'Unknown' => $item->count];
            });

        $totalLeads = Lead::count();
        $enquiryLeads = $leadsByCategory->get('Enquiry Form Submission', 0);
        $phoneLeads = $leadsByCategory->get('Phone Number Revealed', 0);
        $whatsappLeads = $leadsByCategory->get('WhatsApp Clicked', 0);
        $emailLeads = $leadsByCategory->get('Email Clicked', 0);
        $testDriveLeads = $leadsByCategory->get('Request Test Drive', 0);
        $financingLeads = $leadsByCategory->get('Financing Request', 0);

        // Conversion rate (sold vehicles / total vehicles)
        $conversionRate = $totalVehicles > 0 
            ? round(($soldVehicles / $totalVehicles) * 100, 2) 
            : 0;

        // Total sellers (users who created vehicles)
        $totalSellers = User::whereHas('vehicles')->distinct()->count();

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
            'leads' => [
                'total' => $totalLeads,
                'by_type' => [
                    'enquiry' => $enquiryLeads,
                    'phone' => $phoneLeads,
                    'whatsapp' => $whatsappLeads,
                    'email' => $emailLeads,
                    'test_drive' => $testDriveLeads,
                    'financing' => $financingLeads,
                ],
            ],
            'conversion_rate' => $conversionRate,
        ]);
    }

    /**
     * Get revenue analytics
     */
    public function revenue(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Get current plan prices
        $planPrices = PlanPriceHistory::whereNull('ends_at')
            ->orWhere('ends_at', '>', now())
            ->get()
            ->groupBy('plan_id')
            ->map(function ($prices) {
                return $prices->first(); // Get current price
            });

        // Calculate revenue by plan
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

            // Calculate revenue based on billing cycle
            $planRevenue = 0;
            if ($currentPrice->billing_cycle === 'monthly') {
                $planRevenue = $activeSubscriptions * $currentPrice->price;
                $monthlyRecurringRevenue += $planRevenue;
            } else {
                // Yearly - divide by 12 for monthly equivalent
                $planRevenue = ($activeSubscriptions * $currentPrice->price) / 12;
                $monthlyRecurringRevenue += $planRevenue;
            }

            $totalRevenue += $planRevenue * 12; // Annual revenue

            $revenueByPlan[] = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'active_subscriptions' => $activeSubscriptions,
                'price' => $currentPrice->price,
                'billing_cycle' => $currentPrice->billing_cycle,
                'revenue' => $planRevenue,
            ];
        }

        // Churned subscriptions (expired or canceled)
        $churnedSubscriptions = DealerSubscription::whereIn('subscription_status_id', [
            SubscriptionStatus::EXPIRED,
            SubscriptionStatus::CANCELED,
        ])->count();

        $activeSubscriptions = DealerSubscription::where('subscription_status_id', SubscriptionStatus::ACTIVE)->count();

        return $this->success([
            'total_subscription_revenue' => $totalRevenue,
            'monthly_recurring_revenue' => round($monthlyRecurringRevenue, 2),
            'revenue_by_plan' => $revenueByPlan,
            'subscriptions' => [
                'active' => $activeSubscriptions,
                'churned' => $churnedSubscriptions,
            ],
        ]);
    }

    /**
     * Get dealer performance analytics
     */
    public function dealers(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Top dealers by listings
        $topDealersByListings = Dealer::withCount(['vehicles' => function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        }])
            ->orderBy('vehicles_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($dealer) {
                return [
                    'dealer_id' => $dealer->id,
                    'cvr' => $dealer->cvr,
                    'city' => $dealer->city,
                    'listings_count' => $dealer->vehicles_count,
                ];
            });

        // Top dealers by leads
        $topDealersByLeads = Dealer::withCount(['leads' => function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        }])
            ->orderBy('leads_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($dealer) {
                return [
                    'dealer_id' => $dealer->id,
                    'cvr' => $dealer->cvr,
                    'city' => $dealer->city,
                    'leads_count' => $dealer->leads_count,
                ];
            });

        // Top dealers by sold vehicles
        $topDealersBySold = Dealer::withCount(['vehicles' => function ($query) use ($startDate, $endDate) {
            $query->where('vehicle_list_status_id', VehicleListStatus::SOLD);
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
        }])
            ->orderBy('vehicles_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($dealer) {
                return [
                    'dealer_id' => $dealer->id,
                    'cvr' => $dealer->cvr,
                    'city' => $dealer->city,
                    'sold_count' => $dealer->vehicles_count,
                ];
            });

        // Average lead response time per dealer
        $dealersWithResponseTime = Dealer::with(['leads' => function ($query) {
            $query->whereNotNull('last_activity_at')
                ->whereNotNull('created_at')
                ->select('dealer_id', 'created_at', 'last_activity_at');
        }])->get();

        $dealerResponseTimes = [];
        foreach ($dealersWithResponseTime as $dealer) {
            if ($dealer->leads->isEmpty()) {
                continue;
            }

            $totalResponseTime = 0;
            $count = 0;
            foreach ($dealer->leads as $lead) {
                if ($lead->last_activity_at && $lead->created_at) {
                    $responseTime = $lead->created_at->diffInHours($lead->last_activity_at);
                    $totalResponseTime += $responseTime;
                    $count++;
                }
            }

            if ($count > 0) {
                $dealerResponseTimes[] = [
                    'dealer_id' => $dealer->id,
                    'cvr' => $dealer->cvr,
                    'average_response_time_hours' => round($totalResponseTime / $count, 2),
                ];
            }
        }

        usort($dealerResponseTimes, function ($a, $b) {
            return $a['average_response_time_hours'] <=> $b['average_response_time_hours'];
        });

        // Dealer activity trend (new dealers over time)
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

    /**
     * Get vehicle analytics
     */
    public function vehicles(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $vehicleQuery = Vehicle::query();
        if ($startDate) {
            $vehicleQuery->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $vehicleQuery->where('created_at', '<=', $endDate);
        }

        // Vehicles by DMR body type (replaces legacy category_id on vehicles)
        $categoryQuery = DB::table('vehicles as v')
            ->join('dmr_fact_vehicles as dfv', 'v.dmr_fact_vehicle_id', '=', 'dfv.id')
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

        // Primary drivmiddel line → drive energy name (replaces legacy fuel_type_id)
        $fuelQuery = DB::table('vehicles as v')
            ->join('dmr_fact_vehicles as dfv', 'v.dmr_fact_vehicle_id', '=', 'dfv.id')
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

        // Vehicles by price range
        $priceRanges = [
            ['min' => 0, 'max' => 100000, 'label' => '0-100k'],
            ['min' => 100000, 'max' => 200000, 'label' => '100k-200k'],
            ['min' => 200000, 'max' => 300000, 'label' => '200k-300k'],
            ['min' => 300000, 'max' => 500000, 'label' => '300k-500k'],
            ['min' => 500000, 'max' => null, 'label' => '500k+'],
        ];

        $vehiclesByPriceRange = [];
        foreach ($priceRanges as $range) {
            $query = Vehicle::where('vehicle_list_status_id', VehicleListStatus::PUBLISHED);
            if ($range['min'] !== null) {
                $query->where('price', '>=', $range['min']);
            }
            if ($range['max'] !== null) {
                $query->where('price', '<', $range['max']);
            }
            $count = $query->count();
            $vehiclesByPriceRange[] = [
                'range' => $range['label'],
                'count' => $count,
            ];
        }

        // Average days to sell
        $soldVehicles = Vehicle::where('vehicle_list_status_id', VehicleListStatus::SOLD)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get();

        $totalDays = 0;
        $count = 0;
        foreach ($soldVehicles as $vehicle) {
            $daysToSell = $vehicle->created_at->diffInDays($vehicle->updated_at);
            $totalDays += $daysToSell;
            $count++;
        }
        $averageDaysToSell = $count > 0 ? round($totalDays / $count, 2) : 0;

        // Most viewed vehicles
        $mostViewedVehicles = ListingViewsLog::select('vehicle_id', DB::raw('count(*) as view_count'))
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

    /**
     * Get lead analytics
     */
    public function leads(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Leads over time
        $leadsOverTime = [];
        $periods = $this->getPeriods($startDate, $endDate);
        foreach ($periods as $period) {
            $count = Lead::whereBetween('created_at', [$period['start'], $period['end']])->count();
            $leadsOverTime[] = [
                'date' => $period['date'],
                'count' => $count,
            ];
        }

        // Leads by source
        $leadsBySource = Lead::select('source_id', DB::raw('count(*) as count'))
            ->groupBy('source_id')
            ->get()
            ->map(function ($item) {
                $source = Source::find($item->source_id);
                return [
                    'source' => $source?->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        // Leads by vehicle (top vehicles with most leads)
        $leadsByVehicle = Lead::select('vehicle_id', DB::raw('count(*) as lead_count'))
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

        // Lead to sale conversion
        $totalLeads = Lead::count();
        $leadsThatConverted = Lead::whereHas('vehicle', function ($query) {
            $query->where('vehicle_list_status_id', VehicleListStatus::SOLD);
        })->count();
        $conversionRate = $totalLeads > 0 
            ? round(($leadsThatConverted / $totalLeads) * 100, 2) 
            : 0;

        // Unanswered leads (leads with no activity after creation)
        $unansweredLeads = Lead::whereNull('last_activity_at')
            ->orWhereColumn('last_activity_at', 'created_at')
            ->count();

        return $this->success([
            'over_time' => $leadsOverTime,
            'by_source' => $leadsBySource,
            'by_vehicle' => $leadsByVehicle,
            'conversion_rate' => $conversionRate,
            'unanswered_count' => $unansweredLeads,
        ]);
    }

    /**
     * Get user activity analytics
     */
    public function activity(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Login activity (from audit logs)
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

        // Listing creation trends
        $listingTrends = [];
        foreach ($periods as $period) {
            $count = Vehicle::whereBetween('created_at', [$period['start'], $period['end']])->count();
            $listingTrends[] = [
                'date' => $period['date'],
                'count' => $count,
            ];
        }

        // Feature usage statistics (from audit logs)
        $featureUsage = AuditLog::select('action', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate ?? Carbon::minValue(), $endDate ?? Carbon::maxValue()])
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'action' => $item->action,
                    'count' => $item->count,
                ];
            });

        return $this->success([
            'login_activity' => $loginActivity,
            'listing_creation_trends' => $listingTrends,
            'feature_usage' => $featureUsage,
        ]);
    }

    /**
     * Get periods for trend charts
     */
    private function getPeriods(?Carbon $startDate, ?Carbon $endDate): array
    {
        if (!$startDate || !$endDate) {
            // Default to last 30 days
            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now();
        }

        $daysDiff = $startDate->diffInDays($endDate);
        
        if ($daysDiff <= 7) {
            // Daily
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
        } elseif ($daysDiff <= 90) {
            // Weekly
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
        } else {
            // Monthly
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
}
