<?php

namespace App\Http\Controllers;

use App\Models\DealerFeedToken;
use App\Services\Feeds\VehicleFeedBuilderService;
use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class VehicleFeedController extends Controller
{
    public function __construct(
        private VehicleFeedBuilderService $feedBuilder,
        private PlatformSettingService $platformSettingService,
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

    public function csv(Request $request, string $token): Response
    {
        $feedToken = $this->resolveToken($token);
        if (! $feedToken) {
            return response(__('messages.api.unauthorized'), 401);
        }

        $feedToken->update(['last_used_at' => now()]);

        $csv = $this->feedBuilder->toFacebookCsv($feedToken->dealer);
        Storage::disk('local')->put(
            "feeds/dealer-{$feedToken->dealer_id}/facebook_catalog.csv",
            $csv
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="facebook_catalog.csv"',
        ]);
    }

    public function platformCsv(Request $request, string $token): Response
    {
        $expected = (string) $this->platformSettingService->get('marketing', 'meta_catalog_feed_token', '');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            return response(__('messages.api.unauthorized'), 401);
        }

        $csv = $this->feedBuilder->toPlatformFacebookCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="platform_facebook_catalog.csv"',
        ]);
    }

    private function resolveToken(string $token): ?DealerFeedToken
    {
        return DealerFeedToken::with('dealer')->where('token', $token)->where('is_active', true)->first();
    }
}
