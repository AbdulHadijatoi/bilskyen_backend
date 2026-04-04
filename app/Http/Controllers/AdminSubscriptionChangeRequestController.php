<?php

namespace App\Http\Controllers;

use App\Constants\ApiStatusCode;
use App\Constants\SubscriptionChangeRequestStatus;
use App\Mail\SubscriptionChangeRequestApprovedMail;
use App\Mail\SubscriptionChangeRequestRejectedMail;
use App\Models\DealerSubscriptionChangeRequest;
use App\Models\Plan;
use App\Services\DealerSubscriptionApplicationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AdminSubscriptionChangeRequestController extends Controller
{
    public function __construct(
        private DealerSubscriptionApplicationService $dealerSubscriptionApplicationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $statusValues = array_merge(SubscriptionChangeRequestStatus::values(), ['all']);
        $request->validate([
            'status' => ['sometimes', Rule::in($statusValues)],
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $statusFilter = $request->get('status', SubscriptionChangeRequestStatus::PENDING);

        $query = DealerSubscriptionChangeRequest::with([
            'dealer.owner',
            'requestedPlan',
            'reviewedByUser',
        ])
            ->orderByDesc('id');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $paginator = $query->paginate($request->get('limit', 15));

        $paginator->getCollection()->transform(function (DealerSubscriptionChangeRequest $row) {
            $arr = $row->toArray();
            $arr['current_subscription'] = $this->currentActiveTrialSubscriptionPayload($row->dealer_id);
            return $arr;
        });

        return $this->paginated($paginator);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $subscription = null;
            $features = [];
            $mailChangeRequestId = null;

            DB::transaction(function () use ($request, $id, &$subscription, &$features, &$mailChangeRequestId) {
                /** @var DealerSubscriptionChangeRequest|null $changeRequest */
                $changeRequest = DealerSubscriptionChangeRequest::lockForUpdate()->find($id);

                if (!$changeRequest) {
                    throw new \RuntimeException('not_found');
                }

                if (!$changeRequest->isPending()) {
                    throw new \RuntimeException('not_pending');
                }

                $dealer = $changeRequest->dealer()->lockForUpdate()->first();
                $dealer->load('owner.roles');

                $plan = Plan::with('availability')->findOrFail($changeRequest->requested_plan_id);

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
                    throw new \RuntimeException('plan_not_available');
                }

                $admin = $request->user();
                $startsAt = $changeRequest->starts_at
                    ? Carbon::parse($changeRequest->starts_at)
                    : now();

                $result = $this->dealerSubscriptionApplicationService->applyPlanToDealer(
                    $dealer,
                    $plan,
                    $changeRequest->billing_cycle,
                    $startsAt,
                    $admin,
                    $request,
                    "Subscription canceled: admin approved change request #{$changeRequest->id}",
                    "Subscription created: admin approved change request #{$changeRequest->id} ({$changeRequest->billing_cycle})",
                );

                $subscription = $result['subscription'];
                $features = $result['subscription_features'];

                $changeRequest->update([
                    'status' => SubscriptionChangeRequestStatus::APPROVED,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => null,
                ]);

                $mailChangeRequestId = $changeRequest->id;
            });

            if ($mailChangeRequestId !== null) {
                $forMail = DealerSubscriptionChangeRequest::with(['requestedPlan', 'dealer.owner'])
                    ->find($mailChangeRequestId);
                if ($forMail) {
                    $this->sendApprovedMail($forMail);
                }
            }
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'not_found') {
                return $this->notFound(__('messages.api.subscription_change_request_not_found'));
            }
            if ($e->getMessage() === 'not_pending') {
                return $this->error(__('messages.api.subscription_change_request_not_pending'), [], ApiStatusCode::BAD_REQUEST);
            }
            if ($e->getMessage() === 'plan_not_available') {
                return $this->error(__('messages.api.subscription_dealer_plan_not_allowed'), [], ApiStatusCode::FORBIDDEN);
            }
            throw $e;
        } catch (\Exception $e) {
            Log::error('Admin subscription change approve failed', ['id' => $id, 'error' => $e->getMessage()]);

            return $this->error(
                __('messages.api.subscription_change_approve_failed', ['message' => $e->getMessage()]),
                [],
                ApiStatusCode::INTERNAL_SERVER_ERROR
            );
        }

        $out = $subscription->load(['plan.features', 'plan.priceHistory', 'subscriptionStatus'])->toArray();
        $out['subscription_features'] = $features;

        return $this->success($out, ApiStatusCode::OK, __('messages.api.operation_completed_successfully'));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'sometimes|string|max:2000',
        ]);

        $changeRequest = DealerSubscriptionChangeRequest::with(['dealer.owner', 'requestedPlan'])->find($id);

        if (!$changeRequest) {
            return $this->notFound(__('messages.api.subscription_change_request_not_found'));
        }

        if (!$changeRequest->isPending()) {
            return $this->error(__('messages.api.subscription_change_request_not_pending'), [], ApiStatusCode::BAD_REQUEST);
        }

        $admin = $request->user();

        $changeRequest->update([
            'status' => SubscriptionChangeRequestStatus::REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $changeRequest->refresh();
        $this->sendRejectedMail($changeRequest);

        return $this->success($changeRequest->load(['dealer.owner', 'requestedPlan', 'reviewedByUser']));
    }

    private function currentActiveTrialSubscriptionPayload(int $dealerId): ?array
    {
        $sub = \App\Models\DealerSubscription::query()
            ->where('dealer_id', $dealerId)
            ->whereIn('subscription_status_id', [\App\Constants\SubscriptionStatus::ACTIVE, \App\Constants\SubscriptionStatus::TRIAL])
            ->with('plan')
            ->orderByDesc('created_at')
            ->first();

        if (!$sub) {
            return null;
        }

        return [
            'id' => $sub->id,
            'plan_id' => $sub->plan_id,
            'plan_name' => $sub->plan?->name,
            'subscription_status_id' => $sub->subscription_status_id,
        ];
    }

    private function sendApprovedMail(DealerSubscriptionChangeRequest $changeRequest): void
    {
        $owner = $changeRequest->dealer?->owner;
        if (!$owner?->email) {
            return;
        }

        try {
            Mail::to($owner->email)->send(new SubscriptionChangeRequestApprovedMail($changeRequest));
        } catch (\Exception $e) {
            Log::warning('Failed to send subscription change approved email', [
                'change_request_id' => $changeRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendRejectedMail(DealerSubscriptionChangeRequest $changeRequest): void
    {
        $owner = $changeRequest->dealer?->owner;
        if (!$owner?->email) {
            return;
        }

        try {
            Mail::to($owner->email)->send(new SubscriptionChangeRequestRejectedMail($changeRequest));
        } catch (\Exception $e) {
            Log::warning('Failed to send subscription change rejected email', [
                'change_request_id' => $changeRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
