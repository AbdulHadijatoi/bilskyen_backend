<?php

namespace App\Http\Controllers;

use App\Models\DealerFeedToken;
use App\Services\Feeds\VehicleFeedBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehicleFeedController extends Controller
{
    public function __construct(
        private VehicleFeedBuilderService $feedBuilder
    ) {}

    public function json(Request $request, string $token): JsonResponse|Response
    {
        $feedToken = $this->resolveToken($token);
        if (! $feedToken) {
            return response()->json(['message' => __('messages.api.invalid_feed_token')], 401);
        }

        $feedToken->update(['last_used_at' => now()]);

        return response($this->feedBuilder->toJson($feedToken->dealer), 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function xml(Request $request, string $token): Response
    {
        $feedToken = $this->resolveToken($token);
        if (! $feedToken) {
            return response(__('messages.api.unauthorized'), 401);
        }

        $feedToken->update(['last_used_at' => now()]);

        return response($this->feedBuilder->toXml($feedToken->dealer), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    private function resolveToken(string $token): ?DealerFeedToken
    {
        return DealerFeedToken::where('token', $token)->where('is_active', true)->first();
    }
}
