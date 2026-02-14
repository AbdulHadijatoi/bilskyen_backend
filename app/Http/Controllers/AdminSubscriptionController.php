<?php

namespace App\Http\Controllers;

use App\Models\DealerSubscription;
use App\Models\Dealer;
use App\Models\Plan;
use App\Models\PlanPriceHistory;
use App\Constants\SubscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

/**
 * Admin Subscription Controller
 */
class AdminSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DealerSubscription::with(['dealer', 'plan', 'subscriptionStatus']);

        // Apply filters
        if ($request->has('dealer_id')) {
            $query->where('dealer_id', $request->dealer_id);
        }

        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        if ($request->has('subscription_status_id')) {
            $query->where('subscription_status_id', $request->subscription_status_id);
        }

        $subscriptions = $query->paginate($request->get('limit', 15));

        return $this->paginated($subscriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'dealer_id' => 'required|exists:dealers,id',
            'plan_id' => 'required|exists:plans,id',
            'subscription_status_id' => ['required', Rule::in(SubscriptionStatus::values())],
            'starts_at' => 'required|date',
            'ends_at' => 'sometimes|date|after:starts_at',
            'auto_renew' => 'sometimes|boolean',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
        ]);

        $dealer = Dealer::with('owner.roles')->findOrFail($request->dealer_id);
        $plan = Plan::with('availability')->findOrFail($request->plan_id);

        // Check if dealer is allowed to subscribe to this plan (from owner + staff)
        $dealerRoleIds = collect();
        
        // Add owner's roles (dealer himself is the owner)
        if ($dealer->owner) {
            $dealerRoleIds = $dealerRoleIds->merge($dealer->owner->roles->pluck('id'));
        }
        $dealerRoleIds = $dealerRoleIds->merge(
            $dealer->staff()->with('user.roles')->get()->flatMap(function($staff) {
                return $staff->user->roles->pluck('id');
            })
        )->unique()->toArray();

        if (!$plan->isAvailableToDealer($dealer->id, $dealerRoleIds)) {
            return $this->error('This dealer is not allowed to subscribe to this plan', 403);
        }

        DB::beginTransaction();
        try {
            // Override any existing subscriptions for this dealer
            DealerSubscription::where('dealer_id', $request->dealer_id)->delete();

            // Auto-calculate ends_at if not provided and billing_cycle is set
            $endsAt = $request->ends_at;
            if (!$endsAt && $request->has('billing_cycle')) {
                $startsAt = \Carbon\Carbon::parse($request->starts_at);
                $endsAt = $request->billing_cycle === 'yearly' 
                    ? $startsAt->copy()->addYear() 
                    : $startsAt->copy()->addMonth();
            }

            $subscription = DealerSubscription::create([
                'dealer_id' => $request->dealer_id,
                'plan_id' => $request->plan_id,
                'subscription_status_id' => $request->subscription_status_id,
                'starts_at' => $request->starts_at,
                'ends_at' => $endsAt,
                'auto_renew' => $request->auto_renew ?? false,
                'created_at' => now(),
            ]);

            DB::commit();

            $subscription->load(['dealer', 'plan', 'subscriptionStatus']);
            return $this->created($subscription);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create subscription: ' . $e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $subscription = DealerSubscription::with(['dealer', 'plan.features', 'plan.priceHistory', 'subscriptionStatus'])
            ->findOrFail($id);

        return $this->success($subscription);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'subscription_status_id' => ['sometimes', Rule::in(SubscriptionStatus::values())],
            'starts_at' => 'sometimes|date',
            'ends_at' => 'sometimes|date|after:starts_at',
            'auto_renew' => 'sometimes|boolean',
        ]);

        $subscription = DealerSubscription::findOrFail($id);

        DB::beginTransaction();
        try {
            $subscription->update($request->only([
                'subscription_status_id',
                'starts_at',
                'ends_at',
                'auto_renew'
            ]));

            DB::commit();

            $subscription->load(['dealer', 'plan', 'subscriptionStatus']);
            return $this->success($subscription);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update subscription: ' . $e->getMessage(), 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(SubscriptionStatus::values())],
        ]);

        $subscription = DealerSubscription::findOrFail($id);
        $subscription->subscription_status_id = $request->status;
        $subscription->save();

        return $this->success($subscription);
    }

    public function cancel(int $id): JsonResponse
    {
        $subscription = DealerSubscription::findOrFail($id);

        DB::beginTransaction();
        try {
            $subscription->update([
                'subscription_status_id' => SubscriptionStatus::CANCELED,
            ]);

            DB::commit();

            $subscription->load(['dealer', 'plan', 'subscriptionStatus']);
            return $this->success($subscription);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    public function renew(int $id): JsonResponse
    {
        $subscription = DealerSubscription::findOrFail($id);

        if ($subscription->subscription_status_id !== SubscriptionStatus::EXPIRED) {
            return $this->error('Only expired subscriptions can be renewed', 422);
        }

        DB::beginTransaction();
        try {
            // Calculate new subscription period based on original billing cycle
            $plan = Plan::with('priceHistory')->findOrFail($subscription->plan_id);
            $currentPricing = $plan->priceHistory()
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>', now())
                ->first();

            $billingCycle = $currentPricing->billing_cycle ?? 'monthly';
            $startsAt = now();
            $endsAt = $billingCycle === 'yearly' ? $startsAt->copy()->addYear() : $startsAt->copy()->addMonth();

            $subscription->update([
                'subscription_status_id' => SubscriptionStatus::ACTIVE,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            DB::commit();

            $subscription->load(['dealer', 'plan', 'subscriptionStatus']);
            return $this->success($subscription);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to renew subscription: ' . $e->getMessage(), 500);
        }
    }

    public function getDealerSubscriptions(int $dealerId): JsonResponse
    {
        $subscriptions = DealerSubscription::with(['plan', 'subscriptionStatus'])
            ->where('dealer_id', $dealerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($subscriptions);
    }
}

