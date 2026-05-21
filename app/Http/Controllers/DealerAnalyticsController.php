<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\Dealer;
use App\Models\DealerSubscription;
use App\Models\FeaturedListing;
use App\Models\ListingViewsLog;
use App\Models\PriceHistory;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\LeadStage;
use App\Models\FeatureValueType;
use App\Constants\VehicleListStatus;
use App\Constants\SubscriptionStatus;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dealer Analytics Controller
 * Provides dealer-specific analytics
 * All data is scoped to the authenticated dealer
 */
class DealerAnalyticsController extends Controller
{
    public function __construct(
        private readonly SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    private function getDealer(Request $request): ?Dealer
    {
        $dealer = $request->user()?->dealer;

        return $dealer ?: null;
    }

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

    private function getActiveSubscription(int $dealerId): ?DealerSubscription
    {
        return DealerSubscription::where('dealer_id', $dealerId)
            ->whereIn('subscription_status_id', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
            ->with(['plan.planFeatures.feature', 'subscriptionStatus'])
            ->latest()
            ->first();
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

    private function mapLeadsByCategory($leadsQuery): array
    {
        $leadsByCategory = (clone $leadsQuery)
            ->select('lead_category_id', DB::raw('count(*) as count'))
            ->groupBy('lead_category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $category = LeadCategory::find($item->lead_category_id);

                return [$category?->name ?? 'Unknown' => $item->count];
            });

        return [
            'total' => (clone $leadsQuery)->count(),
            'by_type' => [
                'enquiry' => $leadsByCategory->get('Enquiry Form Submission', 0),
                'phone' => $leadsByCategory->get('Phone Number Revealed', 0),
                'whatsapp' => $leadsByCategory->get('WhatsApp Clicked', 0),
                'email' => $leadsByCategory->get('Email Clicked', 0),
                'test_drive' => $leadsByCategory->get('Request Test Drive', 0),
                'financing' => $leadsByCategory->get('Financing Request', 0),
            ],
        ];
    }

    public function overview(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $vehicleBase = Vehicle::where('dealer_id', $dealerId);
        $leadBase = Lead::where('dealer_id', $dealerId);

        $totalActiveVehicles = (clone $vehicleBase)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();

        $soldInPeriodQuery = (clone $vehicleBase)->where('list_status_id', VehicleListStatus::SOLD);
        $this->applyDateFilter($soldInPeriodQuery, $startDate, $endDate, 'updated_at');
        $soldVehicles = $soldInPeriodQuery->count();

        $featuredVehicles = FeaturedListing::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        })->count();

        $featuredLimit = $this->subscriptionFeatureService->getFeatureLimit(
            $dealer,
            'max_feature_listings',
            0
        );

        $leadsInPeriod = clone $leadBase;
        $this->applyDateFilter($leadsInPeriod, $startDate, $endDate);
        $leadMetrics = $this->mapLeadsByCategory($leadsInPeriod);

        // Conversion: leads in period linked to vehicles sold in period
        $totalLeadsInPeriod = $leadMetrics['total'];
        $convertedLeads = (clone $leadsInPeriod)
            ->whereHas('vehicle', function ($query) use ($startDate, $endDate) {
                $query->where('list_status_id', VehicleListStatus::SOLD);
                if ($startDate) {
                    $query->where('updated_at', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('updated_at', '<=', $endDate);
                }
            })
            ->count();
        $conversionRate = $totalLeadsInPeriod > 0
            ? round(($convertedLeads / $totalLeadsInPeriod) * 100, 2)
            : 0;

        return $this->success([
            'vehicles' => [
                'total_active' => $totalActiveVehicles,
                'sold' => $soldVehicles,
                'reserved' => 0,
                'featured_count' => $featuredVehicles,
                'featured_limit' => $featuredLimit,
            ],
            'leads' => $leadMetrics,
            'conversion_rate' => $conversionRate,
        ]);
    }

    public function leads(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $leadBase = Lead::where('dealer_id', $dealerId);
        $this->applyDateFilter($leadBase, $startDate, $endDate);

        $leadsOverTime = [];
        $periods = $this->getPeriods($startDate, $endDate);
        foreach ($periods as $period) {
            $count = Lead::where('dealer_id', $dealerId)
                ->whereBetween('created_at', [$period['start'], $period['end']])
                ->count();
            $leadsOverTime[] = [
                'date' => $period['date'],
                'count' => $count,
            ];
        }

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

        $leadsBySource = (clone $leadBase)
            ->select('source_id', DB::raw('count(*) as count'))
            ->groupBy('source_id')
            ->get()
            ->map(function ($item) {
                $source = Source::find($item->source_id);

                return [
                    'source' => $source?->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        $leadStatusBreakdown = (clone $leadBase)
            ->select('lead_stage_id', DB::raw('count(*) as count'))
            ->groupBy('lead_stage_id')
            ->get()
            ->map(function ($item) {
                $stage = LeadStage::find($item->lead_stage_id);

                return [
                    'stage' => $stage?->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        $leadsWithResponse = (clone $leadBase)
            ->whereNotNull('last_activity_at')
            ->whereColumn('last_activity_at', '!=', 'created_at')
            ->get();

        $totalResponseTime = 0;
        $responseCount = 0;
        foreach ($leadsWithResponse as $lead) {
            $totalResponseTime += $lead->created_at->diffInHours($lead->last_activity_at);
            $responseCount++;
        }
        $averageResponseTime = $responseCount > 0
            ? round($totalResponseTime / $responseCount, 2)
            : 0;

        return $this->success([
            'over_time' => $leadsOverTime,
            'by_vehicle' => $leadsByVehicle,
            'by_source' => $leadsBySource,
            'status_breakdown' => $leadStatusBreakdown,
            'average_response_time_hours' => $averageResponseTime,
        ]);
    }

    public function vehicles(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $viewsQuery = ListingViewsLog::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        });
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

        $leadsQuery = Lead::where('dealer_id', $dealerId);
        $this->applyDateFilter($leadsQuery, $startDate, $endDate);

        $vehiclesWithHighestLeads = (clone $leadsQuery)
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
                    'price' => $vehicle?->price ?? 0,
                    'lead_count' => $item->lead_count,
                ];
            });

        $soldVehiclesQuery = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::SOLD)
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
        $averageDaysOnMarket = $soldCount > 0 ? round($totalDays / $soldCount, 2) : 0;

        $priceChangesQuery = PriceHistory::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        });
        $this->applyDateFilter($priceChangesQuery, $startDate, $endDate, 'changed_at');

        $priceChanges = $priceChangesQuery
            ->with('vehicle')
            ->orderBy('changed_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'vehicle_id' => $item->vehicle_id,
                    'title' => $item->vehicle?->title ?? 'Unknown',
                    'registration' => $item->vehicle?->registration ?? 'N/A',
                    'old_price' => $item->old_price,
                    'new_price' => $item->new_price,
                    'price_change' => $item->new_price - $item->old_price,
                    'changed_at' => $item->changed_at->toISOString(),
                ];
            });

        return $this->success([
            'most_viewed' => $mostViewedVehicles,
            'highest_leads' => $vehiclesWithHighestLeads,
            'average_days_on_market' => $averageDaysOnMarket,
            'price_change_history' => $priceChanges,
        ]);
    }

    public function marketing(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $featuredVehicleIds = FeaturedListing::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        })->pluck('vehicle_id')->toArray();

        $nonFeaturedVehicles = Vehicle::where('dealer_id', $dealerId)
            ->whereNotIn('id', $featuredVehicleIds ?: [0])
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();

        $featuredVehicles = count($featuredVehicleIds);

        $featuredViewsQuery = ListingViewsLog::whereIn('vehicle_id', $featuredVehicleIds ?: [0]);
        $this->applyDateFilter($featuredViewsQuery, $startDate, $endDate, 'viewed_at');
        $featuredViews = $featuredVehicleIds ? $featuredViewsQuery->count() : 0;

        $nonFeaturedViewsQuery = ListingViewsLog::whereHas('vehicle', function ($query) use ($dealerId, $featuredVehicleIds) {
            $query->where('dealer_id', $dealerId)
                ->whereNotIn('id', $featuredVehicleIds ?: [0]);
        });
        $this->applyDateFilter($nonFeaturedViewsQuery, $startDate, $endDate, 'viewed_at');
        $nonFeaturedViews = $nonFeaturedViewsQuery->count();

        $featuredLeadsQuery = Lead::where('dealer_id', $dealerId)
            ->whereIn('vehicle_id', $featuredVehicleIds ?: [0]);
        $this->applyDateFilter($featuredLeadsQuery, $startDate, $endDate);
        $leadsFromFeatured = $featuredVehicleIds ? $featuredLeadsQuery->count() : 0;

        $nonFeaturedLeadsQuery = Lead::where('dealer_id', $dealerId)
            ->whereNotIn('vehicle_id', $featuredVehicleIds ?: [0]);
        $this->applyDateFilter($nonFeaturedLeadsQuery, $startDate, $endDate);
        $leadsFromNonFeatured = $nonFeaturedLeadsQuery->count();

        return $this->success([
            'featured_vs_non_featured' => [
                'featured_vehicles' => $featuredVehicles,
                'non_featured_vehicles' => $nonFeaturedVehicles,
                'featured_views' => $featuredViews,
                'non_featured_views' => $nonFeaturedViews,
                'featured_leads' => $leadsFromFeatured,
                'non_featured_leads' => $leadsFromNonFeatured,
            ],
        ]);
    }

    public function subscription(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $currentSubscription = $this->getActiveSubscription($dealerId);

        if (!$currentSubscription || !$currentSubscription->plan) {
            return $this->success([
                'plan_name' => 'No Plan',
                'status' => 'None',
                'renewal_date' => null,
                'features' => [],
            ]);
        }

        $plan = $currentSubscription->plan;
        $planFeatures = $plan->planFeatures()->with('feature')->get();

        $featureUsage = [];
        foreach ($planFeatures as $planFeature) {
            $feature = $planFeature->feature;
            if (!$feature || $feature->feature_value_type_id === FeatureValueType::BOOLEAN) {
                continue;
            }

            $limit = (int) $planFeature->value;
            $used = match ($feature->key) {
                'max_listings' => Vehicle::where('dealer_id', $dealerId)->count(),
                'max_feature_listings' => FeaturedListing::whereHas('vehicle', function ($query) use ($dealerId) {
                    $query->where('dealer_id', $dealerId);
                })->count(),
                default => 0,
            };

            $featureUsage[] = [
                'feature_key' => $feature->key,
                'feature_name' => $feature->description ?? $feature->key,
                'limit' => $limit,
                'used' => $used,
                'usage_percentage' => $limit > 0 ? round(($used / $limit) * 100, 2) : 0,
            ];
        }

        return $this->success([
            'plan_name' => $plan->name,
            'status' => $currentSubscription->subscriptionStatus?->name ?? 'Unknown',
            'renewal_date' => $currentSubscription->ends_at?->toISOString(),
            'features' => $featureUsage,
        ]);
    }
}
