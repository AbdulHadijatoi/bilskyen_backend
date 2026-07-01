<?php

namespace App\Http\Controllers;

use App\Models\DealerFeedToken;
use App\Services\DealerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerFeedController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $tokens = DealerFeedToken::where('dealer_id', $dealer->id)->orderByDesc('id')->get();
        $baseUrl = rtrim(config('app.url'), '/');

        return $this->success([
            'tokens' => $tokens,
            'feed_urls' => $tokens->map(fn (DealerFeedToken $t) => [
                'name' => $t->name,
                'json' => "{$baseUrl}/api/v1/feeds/{$t->token}/vehicles.json",
                'xml' => "{$baseUrl}/api/v1/feeds/{$t->token}/vehicles.xml",
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $data = $request->validate(['name' => 'nullable|string|max:255']);
        $token = DealerFeedToken::create([
            'dealer_id' => $dealer->id,
            'name' => $data['name'] ?? 'Default',
            'token' => DealerFeedToken::generateToken(),
        ]);

        return $this->created($token);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        DealerFeedToken::where('dealer_id', $dealer->id)->where('id', $id)->delete();

        return $this->noContent();
    }
}
