<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Lead;
use App\Models\DealerSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin Analytics Controller
 */
class AdminAnalyticsController extends Controller
{
    public function vehicles(Request $request): JsonResponse
    {
        // TODO: Implement vehicle analytics
        $stats = [
            'total' => Vehicle::count(),
            'published' => Vehicle::where('vehicle_list_status_id', \App\Constants\VehicleListStatus::PUBLISHED)->count(),
            'draft' => Vehicle::where('vehicle_list_status_id', \App\Constants\VehicleListStatus::DRAFT)->count(),
        ];

        return $this->success($stats);
    }

    public function leads(Request $request): JsonResponse
    {
        // TODO: Implement lead analytics
        $stats = [
            'total' => Lead::count(),
        ];

        return $this->success($stats);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        // TODO: Implement subscription analytics
        $stats = [
            'total' => DealerSubscription::count(),
            'active' => DealerSubscription::where('subscription_status_id', \App\Constants\SubscriptionStatus::ACTIVE)->count(),
        ];

        return $this->success($stats);
    }
}

