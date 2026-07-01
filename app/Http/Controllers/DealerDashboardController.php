<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\Sale;
use App\Services\SubscriptionFeatureService;
use App\Constants\VehicleListStatus;
use App\Constants\SubscriptionStatus as SubscriptionStatusConstant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dealer Dashboard Controller
 * Provides comprehensive statistics and data for dealer dashboard
 * All data is scoped to the authenticated dealer
 */
class DealerDashboardController extends Controller
{
    public function __construct(
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    /**
     * Get dashboard statistics and data for the authenticated dealer
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $user->dealer;
        
        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $now = Carbon::now();
        $last7Days = $now->copy()->subDays(7);
        $last30Days = $now->copy()->subDays(30);
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Vehicle Statistics (scoped to dealer)
        $totalVehicles = Vehicle::where('dealer_id', $dealerId)->count();
        $publishedVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)->count();
        $draftVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::DRAFT)->count();
        $soldVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::SOLD)->count();
        $archivedVehicles = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::ARCHIVED)->count();
        
        $newVehiclesLast7Days = Vehicle::where('dealer_id', $dealerId)
            ->where('created_at', '>=', $last7Days)->count();
        $newVehiclesLast30Days = Vehicle::where('dealer_id', $dealerId)
            ->where('created_at', '>=', $last30Days)->count();
        $newVehiclesThisMonth = Vehicle::where('dealer_id', $dealerId)
            ->where('created_at', '>=', $thisMonth)->count();
        $newVehiclesLastMonth = Vehicle::where('dealer_id', $dealerId)
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        // Lead Statistics (scoped to dealer)
        $totalLeads = Lead::where('dealer_id', $dealerId)->count();
        $newLeadsLast7Days = Lead::where('dealer_id', $dealerId)
            ->where('created_at', '>=', $last7Days)->count();
        $newLeadsLast30Days = Lead::where('dealer_id', $dealerId)
            ->where('created_at', '>=', $last30Days)->count();
        $newLeadsThisMonth = Lead::where('dealer_id', $dealerId)
            ->where('created_at', '>=', $thisMonth)->count();
        $newLeadsLastMonth = Lead::where('dealer_id', $dealerId)
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        // Subscription Information (current dealer's subscription)

        $currentSubscriptionWithRelations = $this->subscriptionFeatureService->getActiveSubscription($dealer);

        $subscriptionData = [
            'has_subscription' => $currentSubscriptionWithRelations !== null,
            'plan_name' => $currentSubscriptionWithRelations?->plan?->name ?? 'No Plan',
            'status' => $currentSubscriptionWithRelations?->subscriptionStatus?->name ?? 'None',
            'is_active' => $currentSubscriptionWithRelations !== null,
        ];

        // Vehicle Price Statistics
        $totalVehicleValue = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->sum('price');
        $averageVehiclePrice = $publishedVehicles > 0 
            ? round($totalVehicleValue / $publishedVehicles, 2) 
            : 0;

        // Recent Activity - Last 10 items
        $recentVehicles = Vehicle::where('dealer_id', $dealerId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'title' => $vehicle->title,
                    'registration' => $vehicle->registration,
                    'price' => $vehicle->price,
                    'user_name' => $vehicle->user->name ?? 'N/A',
                    'status' => $vehicle->list_status_id,
                    'created_at' => $vehicle->created_at?->toISOString(),
                ];
            });

        $recentLeads = Lead::where('dealer_id', $dealerId)
            ->with(['vehicle', 'buyerUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'vehicle_id' => $lead->vehicle_id,
                    'vehicle_title' => $lead->vehicle->title ?? 'N/A',
                    'buyer_name' => $lead->buyerUser->name ?? 'N/A',
                    'stage_id' => $lead->lead_stage_id,
                    'created_at' => $lead->created_at?->toISOString(),
                ];
            });

        // Vehicle creation trend (last 30 days)
        $vehicleTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            
            $vehicleTrend[] = [
                'date' => $date->format('Y-m-d'),
                'count' => Vehicle::where('dealer_id', $dealerId)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])->count(),
            ];
        }

        // Vehicle status distribution
        $vehicleStatusDistribution = [
            ['status' => 'Published', 'count' => $publishedVehicles, 'color' => 'success'],
            ['status' => 'Draft', 'count' => $draftVehicles, 'color' => 'warning'],
            ['status' => 'Sold', 'count' => $soldVehicles, 'color' => 'info'],
            ['status' => 'Archived', 'count' => $archivedVehicles, 'color' => 'grey'],
        ];

        // Calculate growth rates
        $vehiclesGrowthRate = $newVehiclesLastMonth > 0 
            ? round((($newVehiclesThisMonth - $newVehiclesLastMonth) / $newVehiclesLastMonth) * 100, 1)
            : ($newVehiclesThisMonth > 0 ? 100 : 0);

        $leadsGrowthRate = $newLeadsLastMonth > 0 
            ? round((($newLeadsThisMonth - $newLeadsLastMonth) / $newLeadsLastMonth) * 100, 1)
            : ($newLeadsThisMonth > 0 ? 100 : 0);

        return $this->success([
            'overview' => [
                'vehicles' => [
                    'total' => $totalVehicles,
                    'published' => $publishedVehicles,
                    'draft' => $draftVehicles,
                    'sold' => $soldVehicles,
                    'archived' => $archivedVehicles,
                    'new_last_7_days' => $newVehiclesLast7Days,
                    'new_last_30_days' => $newVehiclesLast30Days,
                    'new_this_month' => $newVehiclesThisMonth,
                    'new_last_month' => $newVehiclesLastMonth,
                    'growth_rate' => $vehiclesGrowthRate,
                    'total_value' => $totalVehicleValue,
                    'average_price' => $averageVehiclePrice,
                ],
                'leads' => [
                    'total' => $totalLeads,
                    'new_last_7_days' => $newLeadsLast7Days,
                    'new_last_30_days' => $newLeadsLast30Days,
                    'new_this_month' => $newLeadsThisMonth,
                    'new_last_month' => $newLeadsLastMonth,
                    'growth_rate' => $leadsGrowthRate,
                ],
                'subscription' => $subscriptionData,
            ],
            'trends' => [
                'vehicles' => $vehicleTrend,
            ],
            'distributions' => [
                'vehicle_status' => $vehicleStatusDistribution,
            ],
            'recent' => [
                'vehicles' => $recentVehicles,
                'leads' => $recentLeads,
            ],
        ]);
    }

    /**
     * Widget: vehicles overview (panel dashboard card shape, dealer-scoped)
     */
    public function getVehiclesOverviewWidget(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $published = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->count();
        $pending = Vehicle::where('dealer_id', $dealerId)
            ->whereIn('list_status_id', [VehicleListStatus::DRAFT, VehicleListStatus::PENDING_REVIEW])
            ->count();
        $needsWork = Vehicle::where('dealer_id', $dealerId)
            ->where(function ($q) {
                $q->whereIn('list_status_id', [VehicleListStatus::DRAFT, VehicleListStatus::PENDING_REVIEW])
                    ->orWhere(function ($sub) {
                        $sub->where('list_status_id', VehicleListStatus::PUBLISHED)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now()->addDays(14));
                    });
            })
            ->count();

        $totalInventoryValue = Vehicle::where('dealer_id', $dealerId)
            ->where('list_status_id', VehicleListStatus::PUBLISHED)
            ->sum('price');
        $averageVehicleValue = $published > 0 ? round($totalInventoryValue / $published, 2) : 0;

        return response()->json([
            'totalVehicles' => Vehicle::where('dealer_id', $dealerId)->count(),
            'availableVehicles' => $published,
            'pendingVehicles' => $pending,
            'vehiclesNeedingWork' => $needsWork,
            'totalInventoryValue' => (float) $totalInventoryValue,
            'averageVehicleValue' => $averageVehicleValue,
        ]);
    }

    /**
     * Widget: sales overview (dealer-scoped via vehicle)
     */
    public function getSalesOverviewWidget(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $dealerId = $dealer->id;
        $salesQuery = fn () => Sale::whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId));

        $totalSales = $salesQuery()->count();
        $totalRevenue = (float) $salesQuery()->sum('sale_price');
        $averageSaleValue = $totalSales > 0 ? round($totalRevenue / $totalSales, 2) : 0;

        $salesThisMonth = $salesQuery()->where('sale_date', '>=', now()->startOfMonth())->count();
        $salesThisQuarter = $salesQuery()->where('sale_date', '>=', now()->startOfQuarter())->count();
        $salesLast7Days = $salesQuery()->where('sale_date', '>=', now()->subDays(7))->count();
        $salesLast24Hours = $salesQuery()->where('sale_date', '>=', now()->subDay())->count();

        $averageDaysToSell = Sale::whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId))
            ->selectRaw('AVG(DATEDIFF(sale_date, (SELECT published_at FROM vehicles WHERE vehicles.id = sales.vehicle_id))) as avg_days')
            ->value('avg_days') ?? 0;

        $topSellingMonth = Sale::whereHas('vehicle', fn ($q) => $q->where('dealer_id', $dealerId))
            ->selectRaw('DATE_FORMAT(sale_date, "%M %Y") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderByDesc('count')
            ->first();

        return response()->json([
            'totalSales' => $totalSales,
            'totalRevenue' => round($totalRevenue, 2),
            'averageSaleValue' => $averageSaleValue,
            'salesThisMonth' => $salesThisMonth,
            'salesThisQuarter' => $salesThisQuarter,
            'salesLast7Days' => $salesLast7Days,
            'salesLast24Hours' => $salesLast24Hours,
            'averageDaysToSell' => round((float) $averageDaysToSell, 2),
            'topSellingMonth' => $topSellingMonth?->month,
        ]);
    }
}
