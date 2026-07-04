<?php

namespace App\Services\Marketing;

use App\Mail\AbandonedEnquiryMail;
use App\Mail\DealerCampaignMail;
use App\Mail\EnquiryFollowUpMail;
use App\Models\Enquiry;
use App\Models\Lead;
use App\Models\LeadTask;
use App\Models\MarketingConsentLog;
use App\Models\MarketingEmailQueue;
use App\Models\MarketingUnsubscribe;
use App\Services\MailService;
use App\Services\PlatformSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketingAutomationService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
        private MailService $mailService,
    ) {}

    public function logConsent(string $email, string $consentType, bool $granted, ?Request $request = null, ?int $dealerId = null): void
    {
        MarketingConsentLog::create([
            'email' => strtolower(trim($email)),
            'dealer_id' => $dealerId,
            'consent_type' => $consentType,
            'granted' => $granted,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'source' => 'web',
        ]);
    }

    public function isUnsubscribed(string $email, ?int $dealerId = null): bool
    {
        $email = strtolower(trim($email));

        return MarketingUnsubscribe::where('email', $email)
            ->where(function ($q) use ($dealerId) {
                $q->whereNull('dealer_id');
                if ($dealerId) {
                    $q->orWhere('dealer_id', $dealerId);
                }
            })
            ->exists();
    }

    public function unsubscribe(string $email, ?int $dealerId = null): void
    {
        MarketingUnsubscribe::updateOrCreate(
            ['email' => strtolower(trim($email)), 'dealer_id' => $dealerId],
            ['unsubscribed_at' => now()]
        );
    }

    public function scheduleEnquiryFollowUps(Enquiry $enquiry): void
    {
        $enabled = $this->platformSettingService->get('marketing', 'enquiry_sequence_enabled', true);
        if ($enabled === false || $enabled === 'false') {
            return;
        }

        if (! $enquiry->email) {
            return;
        }

        if ($this->isUnsubscribed($enquiry->email, $enquiry->vehicle?->dealer_id)) {
            return;
        }

        $day1Hours = (int) $this->platformSettingService->get('marketing', 'enquiry_day1_hours', 24);
        $day3Days = (int) $this->platformSettingService->get('marketing', 'enquiry_day3_days', 3);

        foreach ([
            ['step_key' => 'day_1', 'hours' => $day1Hours],
            ['step_key' => 'day_3', 'days' => $day3Days],
        ] as $step) {
            $scheduledAt = isset($step['hours'])
                ? now()->addHours($step['hours'])
                : now()->addDays($step['days']);

            MarketingEmailQueue::create([
                'enquiry_id' => $enquiry->id,
                'lead_id' => $enquiry->lead_id,
                'dealer_id' => $enquiry->vehicle?->dealer_id,
                'recipient_email' => $enquiry->email,
                'sequence_key' => 'enquiry_follow_up',
                'step_key' => $step['step_key'],
                'status' => 'pending',
                'scheduled_at' => $scheduledAt,
            ]);
        }
    }

    public function createWhatsAppFollowUpTask(Lead $lead): void
    {
        $enabled = $this->platformSettingService->get('marketing', 'whatsapp_auto_task', true);
        if ($enabled === false || $enabled === 'false') {
            return;
        }

        $lead->loadMissing('source');
        if (! str_contains(strtolower($lead->source?->name ?? ''), 'whatsapp')) {
            return;
        }

        LeadTask::firstOrCreate(
            [
                'lead_id' => $lead->id,
                'title' => __('messages.marketing.whatsapp_follow_up_task'),
            ],
            [
                'assigned_user_id' => $lead->assigned_user_id,
                'due_at' => now()->addHours(4),
                'priority' => 'high',
            ]
        );
    }

    public function processDueEmails(int $limit = 50): int
    {
        $processed = 0;
        $items = MarketingEmailQueue::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($items as $item) {
            try {
                if ($this->isUnsubscribed($item->recipient_email, $item->dealer_id)) {
                    $item->update(['status' => 'skipped', 'sent_at' => now()]);
                    continue;
                }

                $enquiry = $item->enquiry;
                if (! $enquiry && in_array($item->sequence_key, ['dealer_campaign', 'dealer_retargeting'], true)) {
                    $meta = $item->meta ?? [];
                    $this->mailService->sendMailable(
                        $item->recipient_email,
                        new DealerCampaignMail(
                            (string) ($meta['subject'] ?? ''),
                            (string) ($meta['body'] ?? ''),
                            (string) ($meta['campaign_name'] ?? 'Campaign'),
                        ),
                        ['campaign_id' => $meta['campaign_id'] ?? null]
                    );
                    $item->update(['status' => 'sent', 'sent_at' => now()]);
                    $processed++;
                    continue;
                }

                if (! $enquiry && $item->sequence_key === 'abandoned_enquiry') {
                    $this->mailService->sendMailable(
                        $item->recipient_email,
                        new AbandonedEnquiryMail($item->meta ?? []),
                        ['step' => $item->step_key]
                    );
                    $item->update(['status' => 'sent', 'sent_at' => now()]);
                    $processed++;
                    continue;
                }

                if (! $enquiry) {
                    $item->update(['status' => 'failed', 'error_message' => 'Enquiry missing']);
                    continue;
                }

                $this->mailService->sendMailable(
                    $item->recipient_email,
                    new EnquiryFollowUpMail($enquiry, $item->step_key),
                    ['enquiry_id' => $enquiry->id, 'step' => $item->step_key]
                );

                $item->update(['status' => 'sent', 'sent_at' => now()]);
                $processed++;
            } catch (\Throwable $e) {
                Log::warning('Marketing email queue failed', ['id' => $item->id, 'error' => $e->getMessage()]);
                $item->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            }
        }

        return $processed;
    }
}
