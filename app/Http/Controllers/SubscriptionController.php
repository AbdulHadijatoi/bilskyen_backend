<?php

namespace App\Http\Controllers;

use App\Models\DealerSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Subscription Controller for Dealer
 */
class SubscriptionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $subscription = $dealer->subscriptions()->latest()->first();

        if (!$subscription) {
            return $this->notFound('No active subscription found');
        }

        return $this->success($subscription->load('plan', 'subscriptionStatus'));
    }

    public function getFeatures(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $subscription = $dealer->subscriptions()->latest()->first();

        if (!$subscription) {
            return $this->success([]);
        }

        $features = $subscription->plan->features ?? [];

        return $this->success($features);
    }

    public function getHistory(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $subscriptions = $dealer->subscriptions()
            ->with('plan', 'subscriptionStatus')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 15));

        return $this->paginated($subscriptions);
    }
}

