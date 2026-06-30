<?php

namespace App\Http\Middleware;

use App\Services\DealerContextService;
use App\Services\SubscriptionFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireDealerFeature
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private SubscriptionFeatureService $subscriptionFeatureService
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        if (! $this->subscriptionFeatureService->hasFeature($dealer, $feature)) {
            return response()->json([
                'status' => 'failed',
                'message' => __('messages.api.subscription_feature_required', ['feature' => $feature]),
                'data' => [],
                'errors' => [],
            ], 403);
        }

        return $next($request);
    }
}
