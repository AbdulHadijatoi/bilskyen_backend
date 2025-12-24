<?php

namespace App\Http\Controllers;

use App\Models\DealerSubscription;
use App\Constants\SubscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * Admin Subscription Controller
 */
class AdminSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subscriptions = DealerSubscription::with(['dealer', 'plan', 'subscriptionStatus'])
            ->paginate($request->get('limit', 15));

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
        ]);

        $subscription = DealerSubscription::create($request->only([
            'dealer_id',
            'plan_id',
            'subscription_status_id',
            'starts_at',
            'ends_at',
            'auto_renew'
        ]));

        return $this->created($subscription);
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
}

