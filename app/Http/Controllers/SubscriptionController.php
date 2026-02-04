<?php

namespace App\Http\Controllers;

use App\Models\DealerSubscription;
use App\Models\Plan;
use App\Constants\SubscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function getAvailablePlans(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealers()->with('users.roles')->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        // Get dealer's role IDs
        $dealerRoleIds = $dealer->users->flatMap(function($user) {
            return $user->roles->pluck('id');
        })->unique()->toArray();

        // Get all active plans with pricing and features
        $allPlans = Plan::with([
            'features.featureValueType',
            'priceHistory' => function($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now())
                    ->orderBy('starts_at', 'desc');
            }
        ])
        ->where('is_active', true)
        ->get();

        // Filter plans by availability
        $availablePlans = $allPlans->filter(function($plan) use ($dealer, $dealerRoleIds) {
            return $plan->isAvailableToDealer($dealer->id, $dealerRoleIds);
        })->values();

        return $this->success($availablePlans);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'starts_at' => 'sometimes|date',
        ]);

        $dealer = $request->user()->dealers()->with('users.roles')->first();
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
        }

        $plan = Plan::with('availability')->findOrFail($request->plan_id);

        // Check if plan is available to dealer
        $dealerRoleIds = $dealer->users->flatMap(function($user) {
            return $user->roles->pluck('id');
        })->unique()->toArray();

        if (!$plan->isAvailableToDealer($dealer->id, $dealerRoleIds)) {
            return $this->error('This plan is not available for your dealer account', 403);
        }

        // Check for existing active subscription
        $existingActive = DealerSubscription::where('dealer_id', $dealer->id)
            ->where('subscription_status_id', SubscriptionStatus::ACTIVE)
            ->exists();

        if ($existingActive) {
            return $this->error('You already have an active subscription', 422);
        }

        DB::beginTransaction();
        try {
            // Determine start date
            $startsAt = $request->starts_at ? Carbon::parse($request->starts_at) : now();
            
            // Determine subscription status based on trial period
            $subscriptionStatusId = ($plan->trial_days && $plan->trial_days > 0) 
                ? SubscriptionStatus::TRIAL 
                : SubscriptionStatus::ACTIVE;

            // Calculate end date based on billing cycle
            $endsAt = null;
            if ($subscriptionStatusId === SubscriptionStatus::TRIAL && $plan->trial_days) {
                // Trial period ends after trial_days
                $endsAt = $startsAt->copy()->addDays($plan->trial_days);
            } else {
                // Regular subscription period
                $endsAt = $request->billing_cycle === 'yearly' 
                    ? $startsAt->copy()->addYear() 
                    : $startsAt->copy()->addMonth();
            }

            $subscription = DealerSubscription::create([
                'dealer_id' => $dealer->id,
                'plan_id' => $plan->id,
                'subscription_status_id' => $subscriptionStatusId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'auto_renew' => false,
                'created_at' => now(),
            ]);

            DB::commit();

            $subscription->load(['plan.features', 'plan.priceHistory', 'subscriptionStatus']);
            return $this->created($subscription);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create subscription: ' . $e->getMessage(), 500);
        }
    }
}

