<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\LeadNotificationService;

class LeadObserver
{
    public function __construct(
        private LeadNotificationService $leadNotificationService,
    ) {}

    public function created(Lead $lead): void
    {
        $this->leadNotificationService->notifyNewLead($lead);
        app(\App\Services\Marketing\MarketingAutomationService::class)->createWhatsAppFollowUpTask($lead);
    }
}
