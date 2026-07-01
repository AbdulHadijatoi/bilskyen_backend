<?php

namespace App\Http\Controllers;

use App\Models\DealerApiKey;
use App\Models\DealerWebhookDelivery;
use App\Models\DealerWebhookEndpoint;
use App\Services\DealerContextService;
use App\Services\Dms\DealerDmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerDmsController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private DealerDmsService $dealerDmsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        return $this->success([
            'api_keys' => DealerApiKey::where('dealer_id', $dealer->id)->orderByDesc('id')->get(),
            'webhooks' => DealerWebhookEndpoint::where('dealer_id', $dealer->id)->orderByDesc('id')->get(),
            'recent_deliveries' => DealerWebhookDelivery::whereIn(
                'webhook_endpoint_id',
                DealerWebhookEndpoint::where('dealer_id', $dealer->id)->pluck('id')
            )->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function createApiKey(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $data = $request->validate(['name' => 'required|string|max:255']);
        $result = $this->dealerDmsService->createApiKey($dealer, $data['name']);

        return $this->created([
            'key' => $result['key'],
            'plain_key' => $result['plain_key'],
        ]);
    }

    public function deleteApiKey(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        DealerApiKey::where('dealer_id', $dealer->id)->where('id', $id)->delete();

        return $this->noContent();
    }

    public function createWebhook(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $data = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:vehicle.published,vehicle.updated,vehicle.unpublished',
        ]);

        $webhook = $this->dealerDmsService->createWebhook($dealer, $data['url'], $data['events']);

        return $this->created($webhook);
    }

    public function deleteWebhook(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        DealerWebhookEndpoint::where('dealer_id', $dealer->id)->where('id', $id)->delete();

        return $this->noContent();
    }
}
