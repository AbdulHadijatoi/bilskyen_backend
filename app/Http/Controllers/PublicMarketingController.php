<?php

namespace App\Http\Controllers;

use App\Services\Marketing\AbandonedEnquiryService;
use App\Services\Marketing\ListingFunnelService;
use App\Services\Marketing\MarketingAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMarketingController extends Controller
{
    public function __construct(
        private MarketingAutomationService $marketingAutomationService,
        private AbandonedEnquiryService $abandonedEnquiryService,
        private ListingFunnelService $listingFunnelService,
    ) {}

    public function logConsent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
            'consent_type' => 'required|string|max:64',
            'granted' => 'required|boolean',
            'dealer_id' => 'nullable|integer|exists:dealers,id',
        ]);

        $this->marketingAutomationService->logConsent(
            $data['email'],
            $data['consent_type'],
            (bool) $data['granted'],
            $request,
            $data['dealer_id'] ?? null
        );

        return $this->success(['message' => __('messages.api.consent_logged')]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
            'dealer_id' => 'nullable|integer|exists:dealers,id',
        ]);

        $this->marketingAutomationService->unsubscribe($data['email'], $data['dealer_id'] ?? null);

        return $this->success(['message' => __('messages.api.unsubscribed')]);
    }

    public function trackAbandoned(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'dealer_id' => 'nullable|integer|exists:dealers,id',
            'name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:2000',
        ]);

        $this->abandonedEnquiryService->trackProgress($request, $data);

        return $this->success(['tracked' => true]);
    }

    public function trackFunnel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'event_name' => 'required|string|in:'.implode(',', ListingFunnelService::CLIENT_EVENTS),
            'meta' => 'nullable|array',
            'meta.cta' => 'nullable|string|max:40',
            'meta.form' => 'nullable|string|max:40',
        ]);

        $this->listingFunnelService->record(
            $request,
            isset($data['vehicle_id']) ? (int) $data['vehicle_id'] : null,
            $data['event_name'],
            $data['meta'] ?? []
        );

        return $this->success(['tracked' => true]);
    }
}
