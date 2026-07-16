<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Dealer;
use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\DealerSubscription;
use App\Models\Plan;
use App\Constants\VehicleListStatus;
use App\Constants\SubscriptionStatus;
use App\Helpers\FormatHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Admin Dashboard Controller
 * Provides comprehensive statistics and data for admin dashboard
 */
class AdminDashboardController extends Controller
{
    /**
     * Get dashboard statistics and data
     */
    public function index(): JsonResponse
    {
        $now = Carbon::now();
        $last7Days = $now->copy()->subDays(7);
        $last30Days = $now->copy()->subDays(30);
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // User Statistics
        $totalUsers = User::count();
        $newUsersLast7Days = User::where('created_at', '>=', $last7Days)->count();
        $newUsersLast30Days = User::where('created_at', '>=', $last30Days)->count();
        $newUsersThisMonth = User::where('created_at', '>=', $thisMonth)->count();
        $newUsersLastMonth = User::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();
        
        // Dealer Statistics
        $totalDealers = Dealer::count();
        $newDealersLast7Days = Dealer::where('created_at', '>=', $last7Days)->count();
        $newDealersLast30Days = Dealer::where('created_at', '>=', $last30Days)->count();
        $newDealersThisMonth = Dealer::where('created_at', '>=', $thisMonth)->count();
        $newDealersLastMonth = Dealer::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        // Vehicle Statistics
        $totalVehicles = Vehicle::count();
        $publishedVehicles = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)->count();
        $draftVehicles = Vehicle::where('list_status_id', VehicleListStatus::DRAFT)->count();
        $soldVehicles = Vehicle::where('list_status_id', VehicleListStatus::SOLD)->count();
        $archivedVehicles = Vehicle::where('list_status_id', VehicleListStatus::ARCHIVED)->count();
        
        $newVehiclesLast7Days = Vehicle::where('created_at', '>=', $last7Days)->count();
        $newVehiclesLast30Days = Vehicle::where('created_at', '>=', $last30Days)->count();
        $newVehiclesThisMonth = Vehicle::where('created_at', '>=', $thisMonth)->count();
        $newVehiclesLastMonth = Vehicle::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        // Lead Statistics
        $totalLeads = Lead::count();
        $newLeadsLast7Days = Lead::where('created_at', '>=', $last7Days)->count();
        $newLeadsLast30Days = Lead::where('created_at', '>=', $last30Days)->count();
        $newLeadsThisMonth = Lead::where('created_at', '>=', $thisMonth)->count();
        $newLeadsLastMonth = Lead::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        // Subscription Statistics
        $totalSubscriptions = DealerSubscription::count();
        $activeSubscriptions = DealerSubscription::where('subscription_status_id', SubscriptionStatus::ACTIVE)->count();
        $trialSubscriptions = DealerSubscription::where('subscription_status_id', SubscriptionStatus::TRIAL)->count();
        $expiredSubscriptions = DealerSubscription::where('subscription_status_id', SubscriptionStatus::EXPIRED)->count();
        
        $newSubscriptionsLast7Days = DealerSubscription::where('created_at', '>=', $last7Days)->count();
        $newSubscriptionsLast30Days = DealerSubscription::where('created_at', '>=', $last30Days)->count();
        $newSubscriptionsThisMonth = DealerSubscription::where('created_at', '>=', $thisMonth)->count();
        $newSubscriptionsLastMonth = DealerSubscription::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        // Plan Statistics
        $totalPlans = Plan::count();
        $activePlans = Plan::where('is_active', true)->count();

        // Vehicle Price Statistics
        $totalVehicleValue = Vehicle::where('list_status_id', VehicleListStatus::PUBLISHED)
            ->sum('price');
        $averageVehiclePrice = $publishedVehicles > 0 
            ? round($totalVehicleValue / $publishedVehicles, 2) 
            : 0;

        // Recent Activity - Last 10 items
        $recentVehicles = Vehicle::with(['dealer.owner', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'title' => $vehicle->title,
                    'registration' => $vehicle->registration,
                    'price' => $vehicle->price,
                    'dealer_name' => $this->formatDealerLabel($vehicle->dealer),
                    'user_name' => $vehicle->user->name ?? 'N/A',
                    'status' => $vehicle->list_status_id,
                    'created_at' => $vehicle->created_at?->toISOString(),
                ];
            });

        $recentUsers = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'N/A',
                    'created_at' => $user->created_at?->toISOString(),
                ];
            });

        $recentDealers = Dealer::with('owner')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($dealer) {
                return [
                    'id' => $dealer->id,
                    'name' => $dealer->owner?->name,
                    'cvr' => FormatHelper::isValidPublicCvr($dealer->cvr) ? $dealer->cvr : null,
                    'cvr_pending' => ! FormatHelper::isValidPublicCvr($dealer->cvr),
                    'address' => $dealer->address,
                    'city' => $dealer->city,
                    'created_at' => $dealer->created_at?->toISOString(),
                ];
            });

        $recentLeads = Lead::with(['vehicle', 'dealer.owner', 'buyerUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($lead) {
                return [
                    'id' => $lead->id,
                    'vehicle_id' => $lead->vehicle_id,
                    'vehicle_title' => $lead->vehicle->title ?? 'N/A',
                    'dealer_cvr' => $this->formatDealerLabel($lead->dealer),
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
                'count' => Vehicle::whereBetween('created_at', [$startOfDay, $endOfDay])->count(),
            ];
        }

        // User creation trend (last 30 days)
        $userTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $startOfDay = $date->copy()->startOfDay();
            $endOfDay = $date->copy()->endOfDay();
            
            $userTrend[] = [
                'date' => $date->format('Y-m-d'),
                'count' => User::whereBetween('created_at', [$startOfDay, $endOfDay])->count(),
            ];
        }

        // Vehicle status distribution
        $vehicleStatusDistribution = [
            ['status' => 'Published', 'count' => $publishedVehicles, 'color' => 'success'],
            ['status' => 'Draft', 'count' => $draftVehicles, 'color' => 'warning'],
            ['status' => 'Sold', 'count' => $soldVehicles, 'color' => 'info'],
            ['status' => 'Archived', 'count' => $archivedVehicles, 'color' => 'grey'],
        ];

        // Subscription status distribution
        $subscriptionStatusDistribution = [
            ['status' => 'Active', 'count' => $activeSubscriptions, 'color' => 'success'],
            ['status' => 'Trial', 'count' => $trialSubscriptions, 'color' => 'primary'],
            ['status' => 'Expired', 'count' => $expiredSubscriptions, 'color' => 'error'],
        ];

        return $this->success([
            'overview' => [
                'users' => [
                    'total' => $totalUsers,
                    'new_last_7_days' => $newUsersLast7Days,
                    'new_last_30_days' => $newUsersLast30Days,
                    'new_this_month' => $newUsersThisMonth,
                    'new_last_month' => $newUsersLastMonth,
                    'growth_rate' => $newUsersLastMonth > 0 
                        ? round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100, 1)
                        : ($newUsersThisMonth > 0 ? 100 : 0),
                ],
                'dealers' => [
                    'total' => $totalDealers,
                    'new_last_7_days' => $newDealersLast7Days,
                    'new_last_30_days' => $newDealersLast30Days,
                    'new_this_month' => $newDealersThisMonth,
                    'new_last_month' => $newDealersLastMonth,
                    'growth_rate' => $newDealersLastMonth > 0 
                        ? round((($newDealersThisMonth - $newDealersLastMonth) / $newDealersLastMonth) * 100, 1)
                        : ($newDealersThisMonth > 0 ? 100 : 0),
                ],
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
                    'growth_rate' => $newVehiclesLastMonth > 0 
                        ? round((($newVehiclesThisMonth - $newVehiclesLastMonth) / $newVehiclesLastMonth) * 100, 1)
                        : ($newVehiclesThisMonth > 0 ? 100 : 0),
                    'total_value' => $totalVehicleValue,
                    'average_price' => $averageVehiclePrice,
                ],
                'leads' => [
                    'total' => $totalLeads,
                    'new_last_7_days' => $newLeadsLast7Days,
                    'new_last_30_days' => $newLeadsLast30Days,
                    'new_this_month' => $newLeadsThisMonth,
                    'new_last_month' => $newLeadsLastMonth,
                    'growth_rate' => $newLeadsLastMonth > 0 
                        ? round((($newLeadsThisMonth - $newLeadsLastMonth) / $newLeadsLastMonth) * 100, 1)
                        : ($newLeadsLastMonth > 0 ? 100 : 0),
                ],
                'subscriptions' => [
                    'total' => $totalSubscriptions,
                    'active' => $activeSubscriptions,
                    'trial' => $trialSubscriptions,
                    'expired' => $expiredSubscriptions,
                    'new_last_7_days' => $newSubscriptionsLast7Days,
                    'new_last_30_days' => $newSubscriptionsLast30Days,
                    'new_this_month' => $newSubscriptionsThisMonth,
                    'new_last_month' => $newSubscriptionsLastMonth,
                    'growth_rate' => $newSubscriptionsLastMonth > 0 
                        ? round((($newSubscriptionsThisMonth - $newSubscriptionsLastMonth) / $newSubscriptionsLastMonth) * 100, 1)
                        : ($newSubscriptionsThisMonth > 0 ? 100 : 0),
                ],
                'plans' => [
                    'total' => $totalPlans,
                    'active' => $activePlans,
                ],
            ],
            'trends' => [
                'vehicles' => $vehicleTrend,
                'users' => $userTrend,
            ],
            'distributions' => [
                'vehicle_status' => $vehicleStatusDistribution,
                'subscription_status' => $subscriptionStatusDistribution,
            ],
            'recent' => [
                'vehicles' => $recentVehicles,
                'users' => $recentUsers,
                'dealers' => $recentDealers,
                'leads' => $recentLeads,
            ],
        ]);
    }

    /**
     * Human-readable dealer label for admin dashboard lists (never expose PENDING-* CVRs).
     */
    private function formatDealerLabel(?Dealer $dealer): string
    {
        if (! $dealer) {
            return 'N/A';
        }

        $name = trim((string) ($dealer->owner?->name ?? ''));
        if ($name !== '' && ! preg_match('/^0+$/', $name) && ! preg_match('/^\d+$/', $name)) {
            return $name;
        }

        if (FormatHelper::isValidPublicCvr($dealer->cvr)) {
            return (string) $dealer->cvr;
        }

        return __('messages.common.pending_cvr');
    }
}
