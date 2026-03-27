<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\DealerSubscription;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\FeaturedListing;
use App\Models\ListingViewsLog;
use App\Models\PriceHistory;
use App\Models\Source;
use App\Models\LeadCategory;
use App\Models\LeadStage;
use App\Constants\VehicleListStatus;
use App\Constants\SubscriptionStatus;
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
    /**
     * Get the authenticated dealer
     */
    private function getDealer(Request $request)
    {
        $user = $request->user();
        $dealer = $user->dealer;
        
        if (!$dealer) {
            return null;
        }
        
        return $dealer;
    }

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

    /**
     * Get overview analytics - Key metrics for dealer
     */
    public function overview(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Vehicle metrics
        $totalActiveVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();
        
        $soldVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::SOLD)
            ->count();
        
        // Reserved vehicles (if there's a reserved status, otherwise use a different logic)
        $reservedVehicles = 0; // Placeholder - adjust based on your status constants
        
        $featuredVehicles = FeaturedListing::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        })->count();

        // Get featured vehicle limit from subscription
        $currentSubscription = DealerSubscription::where('dealer_id', $dealerId)
            ->where('subscription_status_id', SubscriptionStatus::ACTIVE)
            ->with('plan.planFeatures')
            ->first();
        
        $featuredLimit = 0;
        if ($currentSubscription && $currentSubscription->plan) {
            $featuredFeature = $currentSubscription->plan->planFeatures()
                ->whereHas('feature', function ($query) {
                    $query->where('key', 'featured_listings');
                })
                ->first();
            $featuredLimit = $featuredFeature ? (int)$featuredFeature->value : 0;
        }

        // Lead metrics by category
        $leadsByCategory = Lead::where('dealer_id', $dealerId)
            ->select('lead_category_id', DB::raw('count(*) as count'))
            ->groupBy('lead_category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                $category = LeadCategory::find($item->lead_category_id);
                return [$category?->name ?? 'Unknown' => $item->count];
            });

        $totalLeads = Lead::where('dealer_id', $dealerId)->count();
        $enquiryLeads = $leadsByCategory->get('Enquiry Form Submission', 0);
        $phoneLeads = $leadsByCategory->get('Phone Number Revealed', 0);
        $whatsappLeads = $leadsByCategory->get('WhatsApp Clicked', 0);
        $emailLeads = $leadsByCategory->get('Email Clicked', 0);
        $testDriveLeads = $leadsByCategory->get('Request Test Drive', 0);
        $financingLeads = $leadsByCategory->get('Financing Request', 0);

        // Conversion rate (sold vehicles / total vehicles)
        $totalVehicles = Vehicle::where('dealer_id', $dealerId)->count();
        $conversionRate = $totalVehicles > 0 
            ? round(($soldVehicles / $totalVehicles) * 100, 2) 
            : 0;

        return $this->success([
            'vehicles' => [
                'total_active' => $totalActiveVehicles,
                'sold' => $soldVehicles,
                'reserved' => $reservedVehicles,
                'featured_count' => $featuredVehicles,
                'featured_limit' => $featuredLimit,
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
     * Get lead analytics
     */
    public function leads(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Leads over time
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

        // Leads by vehicle
        $leadsByVehicle = Lead::where('dealer_id', $dealerId)
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

        // Leads by source
        $leadsBySource = Lead::where('dealer_id', $dealerId)
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

        // Lead status breakdown
        $leadStatusBreakdown = Lead::where('dealer_id', $dealerId)
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

        // Average response time
        $leadsWithResponse = Lead::where('dealer_id', $dealerId)
            ->whereNotNull('last_activity_at')
            ->whereNotNull('created_at')
            ->get();

        $totalResponseTime = 0;
        $count = 0;
        foreach ($leadsWithResponse as $lead) {
            $responseTime = $lead->created_at->diffInHours($lead->last_activity_at);
            $totalResponseTime += $responseTime;
            $count++;
        }
        $averageResponseTime = $count > 0 ? round($totalResponseTime / $count, 2) : 0;

        return $this->success([
            'over_time' => $leadsOverTime,
            'by_vehicle' => $leadsByVehicle,
            'by_source' => $leadsBySource,
            'status_breakdown' => $leadStatusBreakdown,
            'average_response_time_hours' => $averageResponseTime,
        ]);
    }

    /**
     * Get vehicle performance analytics
     */
    public function vehicles(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Most viewed vehicles
        $mostViewedVehicles = ListingViewsLog::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        })
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

        // Vehicles with highest leads
        $vehiclesWithHighestLeads = Lead::where('dealer_id', $dealerId)
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

        // Average days on market
        $soldVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::SOLD)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->get();

        $totalDays = 0;
        $count = 0;
        foreach ($soldVehicles as $vehicle) {
            $daysOnMarket = $vehicle->created_at->diffInDays($vehicle->updated_at);
            $totalDays += $daysOnMarket;
            $count++;
        }
        $averageDaysOnMarket = $count > 0 ? round($totalDays / $count, 2) : 0;

        // Price change history
        $priceChanges = PriceHistory::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        })
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

    /**
     * Get marketing analytics (featured listings performance)
     */
    public function marketing(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $dealerId = $dealer->id;
        $dateRange = $request->get('date_range', '30d');
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        // Featured vehicles
        $featuredVehicleIds = FeaturedListing::whereHas('vehicle', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        })->pluck('vehicle_id')->toArray();

        // Non-featured vehicles
        $nonFeaturedVehicles = Vehicle::where('dealer_id', $dealerId)
            ->whereNotIn('id', $featuredVehicleIds)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();

        $featuredVehicles = count($featuredVehicleIds);

        // Views for featured listings
        $featuredViews = ListingViewsLog::whereIn('vehicle_id', $featuredVehicleIds)
            ->count();

        // Views for non-featured listings
        $nonFeaturedViews = ListingViewsLog::whereHas('vehicle', function ($query) use ($dealerId, $featuredVehicleIds) {
            $query->where('dealer_id', $dealerId)
                ->whereNotIn('id', $featuredVehicleIds);
        })->count();

        // Leads from featured listings
        $leadsFromFeatured = Lead::where('dealer_id', $dealerId)
            ->whereIn('vehicle_id', $featuredVehicleIds)
            ->count();

        // Leads from non-featured listings
        $leadsFromNonFeatured = Lead::where('dealer_id', $dealerId)
            ->whereNotIn('vehicle_id', $featuredVehicleIds)
            ->count();

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

    /**
     * Get subscription usage analytics
     */
    public function subscription(Request $request): JsonResponse
    {
        $dealer = $this->getDealer($request);
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $dealerId = $dealer->id;

        // Get current subscription
        $currentSubscription = DealerSubscription::where('dealer_id', $dealerId)
            ->with(['plan.planFeatures.feature', 'subscriptionStatus'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$currentSubscription) {
            return $this->success([
                'plan_name' => 'No Plan',
                'status' => 'None',
                'renewal_date' => null,
                'features' => [],
            ]);
        }

        $plan = $currentSubscription->plan;
        $planFeatures = $plan->planFeatures()->with('feature')->get();

        // Get feature usage
        $featureUsage = [];
        foreach ($planFeatures as $planFeature) {
            $feature = $planFeature->feature;
            if (!$feature) {
                continue;
            }

            $limit = (int)$planFeature->value;
            $used = 0;

            // Calculate usage based on feature key
            switch ($feature->key) {
                case 'max_listings':
                    $used = Vehicle::where('dealer_id', $dealerId)->count();
                    break;
                case 'featured_listings':
                    $used = FeaturedListing::whereHas('vehicle', function ($query) use ($dealerId) {
                        $query->where('dealer_id', $dealerId);
                    })->count();
                    break;
                case 'max_images_per_listing':
                    // This would require checking vehicle images, skip for now
                    break;
                default:
                    // Other features
                    break;
            }

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
