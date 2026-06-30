<?php

namespace App\Http\Controllers;

use App\Constants\ApiStatusCode;
use App\Constants\SubscriptionChangeRequestStatus;
use App\Constants\SubscriptionStatus;
use App\Models\DealerSubscription;
use App\Models\DealerSubscriptionChangeRequest;
use App\Models\Plan;
use App\Services\AuditLogService;
use App\Services\DealerContextService;
use App\Services\ListingBillingService;
use App\Services\SubscriptionFeatureService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Subscription Controller for Dealer
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService,
        private ListingBillingService $listingBillingService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;

        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $subscription = $this->subscriptionFeatureService->getActiveSubscription($dealer);

        if (!$subscription) {
            return $this->notFound(__('messages.api.no_active_subscription'));
        }

        return $this->success($subscription->load('plan', 'subscriptionStatus'));
    }

    public function getPendingChangeRequest(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $pending = DealerSubscriptionChangeRequest::query()
            ->where('dealer_id', $dealer->id)
            ->where('status', SubscriptionChangeRequestStatus::PENDING)
            ->with('requestedPlan')
            ->latest('id')
            ->first();

        return $this->success($pending);
    }

    public function cancelChangeRequest(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $pending = DealerSubscriptionChangeRequest::query()
            ->where('dealer_id', $dealer->id)
            ->where('status', SubscriptionChangeRequestStatus::PENDING)
            ->latest('id')
            ->first();

        if (!$pending) {
            return $this->notFound(__('messages.api.subscription_change_cancel_none'));
        }

        $pending->update([
            'status' => SubscriptionChangeRequestStatus::CANCELLED,
            'reviewed_at' => now(),
        ]);

        return $this->success($pending->fresh()->load('requestedPlan'));
    }

    public function getFeatures(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;

        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $subscription = $dealer->subscriptions()->latest()->first();

        if (!$subscription) {
            return $this->success([]);
        }

        // Use SubscriptionFeatureService to get processed features (key-value pairs)
        $features = $this->subscriptionFeatureService->getFeatures($dealer);

        return $this->success($features);
    }

    public function getHistory(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;

        if (!$dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $subscriptions = $dealer->subscriptions()
            ->with('plan', 'subscriptionStatus')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 15));

        return $this->paginated($subscriptions);
    }

    public function getAvailablePlans(Request $request): JsonResponse
    {
        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Load owner with roles
        $dealer->load('owner.roles');

        // Get dealer's role IDs (from owner + staff)
        $dealerRoleIds = collect();

        // Add owner's roles (dealer himself is the owner)
        if ($dealer->owner) {
            $dealerRoleIds = $dealerRoleIds->merge($dealer->owner->roles->pluck('id'));
        }

        // Add staff members' roles
        $dealerRoleIds = $dealerRoleIds->merge(
            $dealer->staff()->with('user.roles')->get()->flatMap(function ($staff) {
                return $staff->user->roles->pluck('id');
            })
        )->unique()->toArray();

        // Get all active plans with pricing and features
        $allPlans = Plan::with([
            'features.featureValueType',
            'priceHistory' => function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now())
                    ->orderBy('starts_at', 'desc');
            },
        ])
            ->where('is_active', true)
            ->get();

        // Filter plans by availability
        $availablePlans = $allPlans->filter(function ($plan) use ($dealer, $dealerRoleIds) {
            return $plan->isAvailableToDealer($dealer->id, $dealerRoleIds);
        })->values();

        return $this->success($availablePlans);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly,usage_daily',
            'starts_at' => 'sometimes|date',
        ]);

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);

        // Load owner with roles
        $dealer->load('owner.roles');

        $plan = Plan::with('availability')->findOrFail($request->plan_id);

        if ($plan->billing_model === \App\Constants\BillingModel::USAGE_DAILY && $request->billing_cycle !== 'usage_daily') {
            return $this->validationError(
                ['billing_cycle' => [__('messages.api.usage_plan_requires_usage_daily_cycle')]],
                __('messages.api.usage_plan_requires_usage_daily_cycle')
            );
        }

        if ($plan->billing_model === \App\Constants\BillingModel::SUBSCRIPTION && $request->billing_cycle === 'usage_daily') {
            return $this->validationError(
                ['billing_cycle' => [__('messages.api.subscription_plan_invalid_billing_cycle')]],
                __('messages.api.subscription_plan_invalid_billing_cycle')
            );
        }

        // Check if plan is available to dealer (from owner + staff)
        $dealerRoleIds = collect();

        if ($dealer->owner) {
            $dealerRoleIds = $dealerRoleIds->merge($dealer->owner->roles->pluck('id'));
        }
        $dealerRoleIds = $dealerRoleIds->merge(
            $dealer->staff()->with('user.roles')->get()->flatMap(function ($staff) {
                return $staff->user->roles->pluck('id');
            })
        )->unique()->toArray();

        if (!$plan->isAvailableToDealer($dealer->id, $dealerRoleIds)) {
            return $this->error(__('messages.api.subscription_plan_not_available_dealer'), [], 403);
        }

        $activeSub = $this->subscriptionFeatureService->getActiveSubscription($dealer);

        if ($activeSub && (int) $activeSub->plan_id === (int) $plan->id) {
            return $this->validationError(
                [],
                __('messages.api.subscription_change_already_on_plan')
            );
        }

        try {
            $changeRequest = DB::transaction(function () use ($request, $dealer, $plan) {
                $hasPending = DealerSubscriptionChangeRequest::query()
                    ->where('dealer_id', $dealer->id)
                    ->where('status', SubscriptionChangeRequestStatus::PENDING)
                    ->lockForUpdate()
                    ->exists();

                if ($hasPending) {
                    throw new \RuntimeException('pending_exists');
                }

                return DealerSubscriptionChangeRequest::create([
                    'dealer_id' => $dealer->id,
                    'requested_plan_id' => $plan->id,
                    'billing_cycle' => $request->billing_cycle,
                    'starts_at' => $request->starts_at ? Carbon::parse($request->starts_at) : null,
                    'status' => SubscriptionChangeRequestStatus::PENDING,
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'pending_exists') {
                return $this->error(
                    __('messages.api.subscription_change_request_pending_exists'),
                    [],
                    ApiStatusCode::CONFLICT
                );
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('Dealer subscription change request failed', [
                'dealer_id' => $dealer->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                __('messages.api.subscription_create_failed', ['message' => $e->getMessage()]),
                [],
                500
            );
        }

        try {
            $this->auditLogService->logCreate(
                $user,
                'DealerSubscriptionChangeRequest',
                $changeRequest->id,
                [
                    'dealer_id' => $dealer->id,
                    'requested_plan_id' => $plan->id,
                    'billing_cycle' => $request->billing_cycle,
                    'status' => SubscriptionChangeRequestStatus::PENDING,
                ],
                $request,
                'Dealer',
                $dealer->id,
                "Subscription change request submitted: Plan ID {$plan->id} ({$request->billing_cycle})",
                ['dealer', 'subscription', 'change_request', 'pending']
            );
        } catch (\Exception $e) {
            Log::warning('Failed to create audit log for subscription change request', [
                'change_request_id' => $changeRequest->id,
                'error' => $e->getMessage(),
            ]);
        }

        $changeRequest->load('requestedPlan');

        return $this->created(
            [
                'pending_change_request' => $changeRequest,
            ],
            __('messages.api.subscription_change_request_submitted')
        );
    }

    public function getUsage(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        return $this->success(
            $this->listingBillingService->getUsageSummary($dealer)
        );
    }
}
