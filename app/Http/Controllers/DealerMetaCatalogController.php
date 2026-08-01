<?php

namespace App\Http\Controllers;

use App\Models\DealerFeedToken;
use App\Models\Vehicle;
use App\Services\DealerContextService;
use App\Services\Syndication\MetaCatalogFeedUrlService;
use App\Services\Syndication\MetaVehicleCatalogMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerMetaCatalogController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private MetaVehicleCatalogMapper $catalogMapper,
        private MetaCatalogFeedUrlService $feedUrlService,
    ) {}

    public function preview(Request $request, Vehicle $vehicle): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        if ((int) $vehicle->dealer_id !== (int) $dealer->id) {
            return $this->forbidden();
        }

        $vehicle->load($this->catalogMapper->eagerLoads());
        $preview = $this->catalogMapper->preview($vehicle);

        $token = DealerFeedToken::where('dealer_id', $dealer->id)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        return $this->success([
            'vehicle' => [
                'id' => $vehicle->id,
                'title' => $vehicle->title,
                'slug' => $vehicle->slug,
                'detail_url' => url('/vehicles/'.$vehicle->slug),
                'list_status_id' => $vehicle->list_status_id,
            ],
            'row' => $preview['row'],
            'readiness' => $preview['readiness'],
            'ready' => $preview['ready'],
            'feed_url' => $token ? $this->feedUrlService->dealerFeedUrl($token->token) : null,
            'has_feed_token' => (bool) $token,
        ]);
    }

    public function feedUrl(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $token = DealerFeedToken::where('dealer_id', $dealer->id)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        return $this->success([
            'feed_url' => $token ? $this->feedUrlService->dealerFeedUrl($token->token) : null,
            'has_feed_token' => (bool) $token,
        ]);
    }
}
