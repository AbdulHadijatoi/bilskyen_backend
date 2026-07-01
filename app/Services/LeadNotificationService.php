<?php

namespace App\Services;

use App\Mail\NewLeadNotificationMail;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LeadNotificationService
{
    public function __construct(
        private MailService $mailService,
        private PlatformSettingService $platformSettingService,
    ) {}

    public function notifyNewLead(Lead $lead): void
    {
        $lead->loadMissing(['vehicle', 'dealer.owner', 'assignedUser', 'buyerUser', 'leadStage']);

        $enabled = $this->platformSettingService->get('crm', 'email_on_new_lead', true);
        if ($enabled === false || $enabled === 'false' || $enabled === '0') {
            return;
        }

        $recipients = collect();

        if ($lead->assignedUser?->email) {
            $recipients->push($lead->assignedUser);
        }

        if ($lead->dealer?->owner?->email) {
            $owner = $lead->dealer->owner;
            if (! $recipients->contains(fn (User $u) => $u->id === $owner->id)) {
                $recipients->push($owner);
            }
        }

        foreach ($recipients as $user) {
            try {
                $this->mailService->sendMailable(
                    $user->email,
                    new NewLeadNotificationMail($lead, $user),
                    ['lead_id' => $lead->id]
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to send new lead notification', [
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
