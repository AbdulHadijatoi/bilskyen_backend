<?php

namespace App\Observers;

use App\Models\Enquiry;
use App\Services\Marketing\AbandonedEnquiryService;
use App\Services\Marketing\MarketingAutomationService;
use App\Services\TradeIn\TradeInService;

class EnquiryObserver
{
    public function __construct(
        private MarketingAutomationService $marketingAutomationService,
        private AbandonedEnquiryService $abandonedEnquiryService,
        private TradeInService $tradeInService,
    ) {}

    public function created(Enquiry $enquiry): void
    {
        $enquiry->loadMissing('vehicle');

        if (! app()->runningInConsole() && request()->hasSession()) {
            $this->abandonedEnquiryService->markRecovered(request(), $enquiry);
        }

        if ($enquiry->type === 'Trade-In' && $enquiry->lead_id) {
            $this->tradeInService->createFromExchangeEnquiry($enquiry, $this->parseTradeInFields($enquiry));
        }

        $this->marketingAutomationService->scheduleEnquiryFollowUps($enquiry);
    }

    private function parseTradeInFields(Enquiry $enquiry): array
    {
        $message = (string) $enquiry->message;
        $fields = ['licence_plate' => null, 'kilometers' => null, 'expected_price' => null, 'message' => $message];

        if (preg_match('/Licence plate:\s*(.+)/i', $message, $m)) {
            $fields['licence_plate'] = trim($m[1]);
        }
        if (preg_match('/Kilometres:\s*(\d+)/i', $message, $m)) {
            $fields['kilometers'] = (int) $m[1];
        }
        if (preg_match('/Expected price.*:\s*(.+)/i', $message, $m)) {
            $fields['expected_price'] = trim($m[1]);
        }

        return $fields;
    }
}
