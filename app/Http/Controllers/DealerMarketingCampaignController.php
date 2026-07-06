<?php

namespace App\Http\Controllers;

use App\Models\DealerMarketingCampaign;
use App\Services\DealerContextService;
use App\Services\Marketing\DealerMarketingCampaignService;
use App\Services\Marketing\MarketingAutomationService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerMarketingCampaignController extends Controller
{
    public function __construct(
        private DealerContextService $dealerContextService,
        private DealerMarketingCampaignService $campaignService,
        private MarketingAutomationService $marketingAutomationService,
        private SubscriptionFeatureService $subscriptionFeatureService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        return $this->success($this->campaignService->listForDealer($dealer));
    }

    public function store(Request $request): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'sometimes|string|in:email,retargeting',
            'audience' => 'sometimes|string|in:all_leads,stale_leads,vehicle_viewers',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:10000',
            'scheduled_at' => 'nullable|date',
        ]);

        if (($data['type'] ?? 'email') === 'retargeting') {
            if ($response = $this->requireRetargeting($dealer)) {
                return $response;
            }
        }

        $campaign = $this->campaignService->create($dealer, $request->user(), $data);

        return $this->created($campaign);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $campaign = $this->findCampaign($dealer->id, $id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:email,retargeting',
            'audience' => 'sometimes|string|in:all_leads,stale_leads,vehicle_viewers',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:10000',
            'scheduled_at' => 'nullable|date',
        ]);

        if (($data['type'] ?? $campaign->type) === 'retargeting') {
            if ($response = $this->requireRetargeting($dealer)) {
                return $response;
            }
        }

        try {
            return $this->success($this->campaignService->update($campaign, $data));
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $campaign = $this->findCampaign($dealer->id, $id);

        if ($campaign->type === 'retargeting') {
            if ($response = $this->requireRetargeting($dealer)) {
                return $response;
            }
        }

        try {
            $queued = $this->campaignService->sendNow($campaign, $this->marketingAutomationService);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), [], 422);
        }

        return $this->success([
            'queued' => $queued,
            'campaign' => $campaign->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $dealer = $this->dealerContextService->requireDealer($request->user());
        $campaign = $this->findCampaign($dealer->id, $id);

        if ($campaign->status === 'sent') {
            return $this->error(__('messages.api.campaign_delete_sent_forbidden'), [], 422);
        }

        $campaign->delete();

        return $this->noContent();
    }

    private function findCampaign(int $dealerId, int $id): DealerMarketingCampaign
    {
        return DealerMarketingCampaign::where('dealer_id', $dealerId)->findOrFail($id);
    }

    private function requireRetargeting($dealer): ?JsonResponse
    {
        if (! $this->subscriptionFeatureService->hasFeature($dealer, 'retargeting')) {
            return $this->error(
                __('messages.api.subscription_feature_required', ['feature' => 'retargeting']),
                [],
                403
            );
        }

        return null;
    }
}
