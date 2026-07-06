<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Services\MailService;
use App\Services\PageContentService;
use App\Services\PublicPlansService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealerMarketingController extends Controller
{
    public function __construct(
        private PageContentService $pageContentService,
        private PublicPlansService $publicPlansService,
        private SeoService $seoService,
        private MailService $mailService,
    ) {}

    public function dealerLanding(): View
    {
        $content = $this->pageContentService->getHomePageContent('dealer-marketing');

        return view('marketing.dealer.landing', array_merge($this->dealerMarketingLayout(), [
            'content' => $content,
            'panelUrl' => config('payments.panel_url'),
            'activeNav' => 'home',
            'seo' => $this->seoService->getForPage('static', 'dealer-marketing'),
        ]));
    }

    public function dealerPricing(): View
    {
        $content = $this->pageContentService->getHomePageContent('dealer-pricing');
        $plans = $this->publicPlansService->getPublicPlans();
        $subscriptionPlans = array_values(array_filter($plans, fn ($plan) => ! ($plan['is_usage_plan'] ?? false)));
        $paygPlans = array_values(array_filter($plans, fn ($plan) => ($plan['is_usage_plan'] ?? false)));
        $faqItems = $this->publicPlansService->parseFaqJson($content['pricing_faq_json'] ?? null);

        if ($faqItems === []) {
            $faqItems = $this->publicPlansService->getDefaultFaqItems();
        }

        return view('marketing.dealer.pricing', array_merge($this->dealerMarketingLayout(), [
            'content' => $content,
            'subscriptionPlans' => $subscriptionPlans,
            'paygPlans' => $paygPlans,
            'maxYearlySavingsPercent' => $this->publicPlansService->maxYearlySavingsPercent($plans),
            'faqItems' => $faqItems,
            'panelUrl' => config('payments.panel_url'),
            'activeNav' => 'pricing',
            'showEnterpriseCard' => filter_var($content['show_enterprise_card'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'seo' => $this->seoService->getForPage('static', 'dealer-pricing'),
        ]));
    }

    public function dealerResources(): View
    {
        $content = $this->pageContentService->getHomePageContent('dealer-marketing');

        return view('marketing.dealer.resources', array_merge($this->dealerMarketingLayout(), [
            'content' => $content,
            'panelUrl' => config('payments.panel_url'),
            'activeNav' => 'resources',
            'seo' => $this->seoService->getForPage('static', 'dealer-resources'),
        ]));
    }

    public function dealerContact(): View
    {
        $content = $this->pageContentService->getHomePageContent('dealer-marketing');
        $contactContent = $this->pageContentService->getHomePageContent('contact');

        return view('marketing.dealer.contact', array_merge($this->dealerMarketingLayout(), [
            'content' => $content,
            'contactContent' => $contactContent,
            'panelUrl' => config('payments.panel_url'),
            'activeNav' => 'contact',
            'seo' => $this->seoService->getForPage('static', 'dealer-contact'),
        ]));
    }

    public function submitDealerContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'subject' => 'required|in:enterprise,pricing,onboarding,support,general',
            'message' => 'required|string|max:5000',
        ]);

        $contactContent = $this->pageContentService->getHomePageContent('contact');
        $recipientEmail = $contactContent['contact_email'] ?? 'info@bilskyen.dk';

        $subjectLabel = match ($validated['subject']) {
            'enterprise' => __('messages.dealer_marketing.contact.subject_enterprise'),
            'pricing' => __('messages.dealer_marketing.contact.subject_pricing'),
            'onboarding' => __('messages.dealer_marketing.contact.subject_onboarding'),
            'support' => __('messages.dealer_marketing.contact.subject_support'),
            default => __('messages.dealer_marketing.contact.subject_general'),
        };

        $sent = $this->mailService->sendMailable(
            $recipientEmail,
            new ContactMessageMail(
                senderName: $validated['name'],
                senderEmail: $validated['email'],
                subjectLabel: '[Dealer] ' . $subjectLabel,
                senderMessage: $validated['message'],
            ),
            [
                'mail_type' => 'dealer_contact_message',
                'sender_email' => $validated['email'],
            ],
            false
        );

        if (! $sent) {
            return back()
                ->withInput()
                ->with('error', __('messages.pages.contact.send_error'));
        }

        return redirect()
            ->route('for-dealers.contact')
            ->with('success', __('messages.pages.contact.send_success'));
    }

    public function staffLanding(): View
    {
        $content = $this->pageContentService->getHomePageContent('staff-marketing');

        return view('marketing.staff.landing', array_merge($this->staffMarketingLayout(), [
            'content' => $content,
            'panelUrl' => config('payments.panel_url'),
            'activeNav' => 'home',
            'seo' => $this->seoService->getForPage('static', 'staff-marketing'),
        ]));
    }

    public function staffResources(): View
    {
        $content = $this->pageContentService->getHomePageContent('staff-marketing');

        return view('marketing.staff.resources', array_merge($this->staffMarketingLayout(), [
            'content' => $content,
            'panelUrl' => config('payments.panel_url'),
            'activeNav' => 'resources',
            'seo' => $this->seoService->getForPage('static', 'staff-resources'),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function dealerMarketingLayout(): array
    {
        return [
            'navComponent' => 'components.marketing.dealer-navbar',
            'footerComponent' => 'components.marketing.marketing-footer',
            'variant' => 'dealer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function staffMarketingLayout(): array
    {
        return [
            'navComponent' => 'components.marketing.staff-navbar',
            'footerComponent' => 'components.marketing.marketing-footer',
            'variant' => 'staff',
        ];
    }
}
