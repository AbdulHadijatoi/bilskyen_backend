<?php

namespace App\Services\Marketing;

use App\Models\Dealer;
use App\Models\DealerMarketingCampaign;
use App\Models\Lead;
use App\Models\MarketingEmailQueue;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DealerMarketingCampaignService
{
    public function listForDealer(Dealer $dealer): Collection
    {
        return DealerMarketingCampaign::where('dealer_id', $dealer->id)
            ->orderByDesc('id')
            ->get();
    }

    public function create(Dealer $dealer, User $user, array $data): DealerMarketingCampaign
    {
        return DealerMarketingCampaign::create([
            'dealer_id' => $dealer->id,
            'created_by_user_id' => $user->id,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'email',
            'audience' => $data['audience'] ?? 'all_leads',
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'status' => 'draft',
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);
    }

    public function update(DealerMarketingCampaign $campaign, array $data): DealerMarketingCampaign
    {
        if ($campaign->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft campaigns can be edited.');
        }

        $campaign->update(collect($data)->only([
            'name', 'type', 'audience', 'subject', 'body', 'scheduled_at',
        ])->all());

        return $campaign->fresh();
    }

    public function sendNow(DealerMarketingCampaign $campaign, MarketingAutomationService $automation): int
    {
        if ($campaign->status === 'sent') {
            throw new \InvalidArgumentException('Campaign was already sent.');
        }

        if (trim((string) $campaign->subject) === '' || trim((string) $campaign->body) === '') {
            throw new \InvalidArgumentException('Campaign subject and body are required before sending.');
        }

        $recipients = $this->resolveAudience($campaign);
        $queued = 0;
        $seenEmails = [];

        DB::transaction(function () use ($campaign, $automation, $recipients, &$queued, &$seenEmails) {
            foreach ($recipients as $lead) {
                $email = strtolower(trim((string) ($lead->buyerUser?->email ?? $lead->enquiry?->email ?? '')));
                if ($email === '' || isset($seenEmails[$email])) {
                    continue;
                }

                if ($automation->isUnsubscribed($email, $campaign->dealer_id)) {
                    continue;
                }

                MarketingEmailQueue::create([
                    'lead_id' => $lead->id,
                    'dealer_id' => $campaign->dealer_id,
                    'recipient_email' => $email,
                    'sequence_key' => $campaign->type === 'retargeting' ? 'dealer_retargeting' : 'dealer_campaign',
                    'step_key' => 'campaign_'.$campaign->id,
                    'meta' => [
                        'campaign_id' => $campaign->id,
                        'subject' => $campaign->subject,
                        'body' => $campaign->body,
                        'campaign_name' => $campaign->name,
                    ],
                    'status' => 'pending',
                    'scheduled_at' => now(),
                ]);

                $seenEmails[$email] = true;
                $queued++;
            }

            if ($queued === 0) {
                throw new \InvalidArgumentException('No eligible recipients found for this campaign audience.');
            }

            $campaign->update([
                'status' => 'sent',
                'sent_count' => $queued,
                'scheduled_at' => $campaign->scheduled_at ?? now(),
            ]);
        });

        return $queued;
    }

    private function resolveAudience(DealerMarketingCampaign $campaign): Collection
    {
        $query = Lead::with(['buyerUser', 'enquiry'])
            ->where('dealer_id', $campaign->dealer_id);

        return match ($campaign->audience) {
            'stale_leads' => $query->where(function ($q) {
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<=', now()->subDays(14));
            })->limit(500)->get(),
            'vehicle_viewers' => $query->whereNotNull('vehicle_id')->limit(500)->get(),
            default => $query->limit(500)->get(),
        };
    }
}
