<?php

namespace App\Services\Marketing;

use App\Models\AbandonedEnquirySession;
use App\Models\Enquiry;
use App\Models\MarketingEmailQueue;
use App\Services\PlatformSettingService;
use Illuminate\Http\Request;

class AbandonedEnquiryService
{
    public function __construct(
        private PlatformSettingService $platformSettingService,
        private MarketingAutomationService $marketingAutomationService,
    ) {}

    public function isEnabled(): bool
    {
        $enabled = $this->platformSettingService->get('marketing', 'abandoned_enquiry_enabled', true);

        return ! ($enabled === false || $enabled === 'false');
    }

    public function trackProgress(Request $request, array $data): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $sessionId = $request->session()->getId();
        if (! $sessionId) {
            return;
        }

        AbandonedEnquirySession::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'dealer_id' => $data['dealer_id'] ?? null,
                'form_data' => $data,
                'last_activity_at' => now(),
            ]
        );
    }

    public function markRecovered(Request $request, Enquiry $enquiry): void
    {
        $sessionId = $request->session()->getId();
        if (! $sessionId) {
            return;
        }

        AbandonedEnquirySession::where('session_id', $sessionId)
            ->whereNull('recovered_at')
            ->update([
                'recovered_at' => now(),
                'enquiry_id' => $enquiry->id,
            ]);
    }

    public function processAbandoned(int $limit = 30): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $timeoutMinutes = (int) $this->platformSettingService->get('marketing', 'abandoned_timeout_minutes', 30);
        $cutoff = now()->subMinutes($timeoutMinutes);

        $sessions = AbandonedEnquirySession::whereNull('recovered_at')
            ->whereNull('enquiry_id')
            ->where('last_activity_at', '<=', $cutoff)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('last_activity_at')
            ->limit($limit)
            ->get();

        $count = 0;
        foreach ($sessions as $session) {
            $email = $session->form_data['email'] ?? null;
            if (! $email || $this->marketingAutomationService->isUnsubscribed($email, $session->dealer_id)) {
                $session->update(['recovered_at' => now()]);
                continue;
            }

            MarketingEmailQueue::create([
                'enquiry_id' => null,
                'lead_id' => null,
                'dealer_id' => $session->dealer_id,
                'recipient_email' => $email,
                'sequence_key' => 'abandoned_enquiry',
                'step_key' => 'reminder',
                'meta' => $session->form_data,
                'status' => 'pending',
                'scheduled_at' => now(),
            ]);

            $session->update(['recovered_at' => now()]);
            $count++;
        }

        return $count;
    }
}
