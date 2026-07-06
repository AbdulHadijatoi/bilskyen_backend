<?php

namespace App\Http\Controllers;

use App\Services\PageContentService;
use App\Services\PublicPlansService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPlansController extends Controller
{
    public function __construct(
        private PublicPlansService $publicPlansService,
        private PageContentService $pageContentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $request->get('locale', app()->getLocale());
        $plans = $this->publicPlansService->getPublicPlans($locale);

        return $this->success([
            'plans' => $plans,
            'max_yearly_savings_percent' => $this->publicPlansService->maxYearlySavingsPercent($plans),
        ]);
    }

    public function pricingFaq(Request $request): JsonResponse
    {
        $content = $this->pageContentService->getHomePageContent('dealer-pricing');
        $faqItems = $this->publicPlansService->parseFaqJson($content['pricing_faq_json'] ?? null);

        if ($faqItems === []) {
            $faqItems = $this->publicPlansService->getDefaultFaqItems();
        }

        return $this->success([
            'items' => $faqItems,
            'header_title' => $content['pricing_faq_title'] ?? __('messages.dealer_marketing.pricing.faq_title'),
            'footnote' => $content['pricing_footnote'] ?? __('messages.dealer_marketing.pricing.footnote'),
        ]);
    }
}
