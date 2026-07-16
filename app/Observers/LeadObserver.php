<?php

namespace App\Observers;

use App\Models\Lead;

class LeadObserver
{
    public function __construct(
        private \App\Services\LeadAutoAssignService $leadAutoAssignService,
    ) {}

    public function created(Lead $lead): void
    {
        $this->leadAutoAssignService->assignIfEnabled($lead);
        $lead->refresh();
        app(\App\Services\Marketing\MarketingAutomationService::class)->createWhatsAppFollowUpTask($lead);
    }
}
