<?php

namespace App\Observers;

use App\Models\Lead;
use App\Services\LeadNotificationService;

class LeadObserver
{
    public function __construct(
        private LeadNotificationService $leadNotificationService,
        private \App\Services\LeadAutoAssignService $leadAutoAssignService,
    ) {}

    public function created(Lead $lead): void
    {
        $this->leadAutoAssignService->assignIfEnabled($lead);
        $lead->refresh();
        $this->leadNotificationService->notifyNewLead($lead);
        app(\App\Services\Marketing\MarketingAutomationService::class)->createWhatsAppFollowUpTask($lead);
    }
}
