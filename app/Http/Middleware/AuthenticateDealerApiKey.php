<?php

namespace App\Http\Middleware;

use App\Services\Dms\DealerDmsService;
use App\Services\SubscriptionFeatureService;
use Closure;
use Illuminate\Http\Request;

class AuthenticateDealerApiKey
{
    public function __construct(
        private DealerDmsService $dealerDmsService,
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');
        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : $request->header('X-Api-Key');

        if (! $token) {
            return response()->json(['message' => __('messages.api.dms_api_key_required')], 401);
        }

        $dealer = $this->dealerDmsService->resolveDealerFromApiKey($token);
        if (! $dealer) {
            return response()->json(['message' => __('messages.api.dms_api_key_invalid')], 401);
        }

        if (! $this->subscriptionFeatureService->hasFeature($dealer, 'api_access')) {
            return response()->json(['message' => __('messages.api.subscription_feature_required', ['feature' => 'api_access'])], 403);
        }

        $request->attributes->set('dms_dealer', $dealer);

        return $next($request);
    }
}
