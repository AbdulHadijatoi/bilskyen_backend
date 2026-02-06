<?php

namespace App\Http\Controllers;

use App\Models\DealerSubscription;
use App\Models\Plan;
use App\Constants\SubscriptionStatus;
use App\Services\AuditLogService;
use App\Services\DealerContextService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Subscription Controller for Dealer
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private AuditLogService $auditLogService,
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}
    public function show(Request $request): JsonResponse
    {
        $dealer = $request->user()->dealer;
        
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
        $dealer = $request->user()->dealer;
        
        if (!$dealer) {
            return $this->notFound('Dealer not found');
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
            $dealer->staff()->with('user.roles')->get()->flatMap(function($staff) {
                return $staff->user->roles->pluck('id');
            })
        )->unique()->toArray();

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

        $user = $request->user();
        $dealer = $this->dealerContextService->requireDealer($user);
        
        // Load owner with roles
        $dealer->load('owner.roles');

        $plan = Plan::with('availability')->findOrFail($request->plan_id);

        // Check if plan is available to dealer (from owner + staff)
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
            return $this->error('This plan is not available for your dealer account', 403);
        }

        DB::beginTransaction();
        try {
            // Cancel existing active or trial subscriptions before creating new one
            $existingSubscriptions = DealerSubscription::where('dealer_id', $dealer->id)
                ->whereIn('subscription_status_id', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
                ->get();

            if ($existingSubscriptions->isNotEmpty()) {
                foreach ($existingSubscriptions as $existingSubscription) {
                    // Get payload before update
                    $payloadBefore = [
                        'subscription_status_id' => $existingSubscription->subscription_status_id,
                    ];
                    
                    $existingSubscription->update([
                        'subscription_status_id' => SubscriptionStatus::CANCELED
                    ]);

                    // Audit log for cancellation
                    try {
                        $this->auditLogService->logUpdate(
                            $user,
                            'DealerSubscription',
                            $existingSubscription->id,
                            $payloadBefore,
                            [
                                'subscription_status_id' => SubscriptionStatus::CANCELED,
                            ],
                            $request,
                            'Dealer',
                            $dealer->id,
                            "Subscription canceled due to plan change: Plan ID {$plan->id}",
                            ['dealer', 'subscription', 'cancel', 'change']
                        );
                    } catch (\Exception $e) {
                        Log::warning('Failed to create audit log for subscription cancellation', [
                            'subscription_id' => $existingSubscription->id,
                            'dealer_id' => $dealer->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

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

            // Audit log
            try {
                $user = $request->user();
                $this->auditLogService->logCreate(
                    $user,
                    'DealerSubscription',
                    $subscription->id,
                    [
                        'plan_id' => $plan->id,
                        'subscription_status_id' => $subscriptionStatusId,
                        'billing_cycle' => $request->billing_cycle,
                        'starts_at' => $startsAt->toIso8601String(),
                        'ends_at' => $endsAt ? $endsAt->toIso8601String() : null,
                    ],
                    $request,
                    'Dealer',
                    $dealer->id,
                    "Subscription created: Plan ID {$plan->id} ({$request->billing_cycle})",
                    ['dealer', 'subscription', 'create', 'purchase']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to create audit log for subscription creation', [
                    'subscription_id' => $subscription->id,
                    'dealer_id' => $dealer->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Clear feature cache for this dealer since subscription changed
            $this->subscriptionFeatureService->clearCache($dealer);
            
            // Get updated subscription features
            $subscriptionFeatures = $this->subscriptionFeatureService->getFeatures($dealer);
            
            $subscription->load(['plan.features', 'plan.priceHistory', 'subscriptionStatus']);
            
            // Add subscription features to response
            $responseData = $subscription->toArray();
            $responseData['subscription_features'] = $subscriptionFeatures;
            
            return $this->created($responseData);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to create subscription: ' . $e->getMessage(), 500);
        }
    }
}

