<?php

namespace App\Http\Controllers;

use App\Models\DealerSyndicationSetting;
use App\Services\DealerContextService;
use App\Services\Syndication\SyndicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerSyndicationController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private SyndicationService $syndicationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $settings = DealerSyndicationSetting::where('dealer_id', $dealer->id)->get()->keyBy('provider_key');

        $providers = collect($this->syndicationService->availableProviders())->map(function ($provider) use ($settings) {
            $row = $settings->get($provider['key']);

            return array_merge($provider, [
                'name' => $provider['label'],
                'enabled' => (bool) ($row?->enabled ?? false),
                'last_sync_at' => $row?->last_sync_at?->toIso8601String(),
            ]);
        });

        return $this->success([
            'providers' => $providers->values(),
            'logs' => $this->syndicationService->recentLogs($dealer->id, 20),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $data = $request->validate([
            'providers' => 'required|array',
            'providers.*.provider_key' => 'required|string|max:64',
            'providers.*.enabled' => 'required|boolean',
        ]);

        foreach ($data['providers'] as $row) {
            DealerSyndicationSetting::updateOrCreate(
                ['dealer_id' => $dealer->id, 'provider_key' => $row['provider_key']],
                ['enabled' => $row['enabled']]
            );
        }

        return $this->success(['message' => __('messages.api.syndication_settings_saved')]);
    }

    public function syncNow(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->getCurrentDealer($request->user());
        if (! $dealer) {
            return $this->notFound(__('messages.errors.dealer_not_found'));
        }

        $count = $this->syndicationService->syncDealer($dealer);

        return $this->success(['synced' => $count]);
    }
}
