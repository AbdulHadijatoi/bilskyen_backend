<?php

namespace App\Console\Commands;

use App\Mail\DealerLeadSlaAlertMail;
use App\Models\Dealer;
use App\Models\Lead;
use App\Constants\LeadStage;
use App\Services\MailService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDealerLeadSlaAlertsCommand extends Command
{
    protected $signature = 'dealers:lead-sla-alerts';

    protected $description = 'Email dealers about leads not contacted within SLA (24 hours)';

    private const SLA_HOURS = 24;

    public function handle(
        SubscriptionFeatureService $subscriptionFeatureService,
        MailService $mailService,
    ): int {
        $sent = 0;
        $cutoff = now()->subHours(self::SLA_HOURS);

        Dealer::query()
            ->with('owner')
            ->whereHas('owner', fn ($q) => $q->whereNotNull('email'))
            ->chunkById(50, function ($dealers) use ($subscriptionFeatureService, $mailService, $cutoff, &$sent) {
                foreach ($dealers as $dealer) {
                    if (! $subscriptionFeatureService->hasFeature($dealer, 'lead_sla_alerts')) {
                        continue;
                    }

                    $overdueLeads = Lead::query()
                        ->where('dealer_id', $dealer->id)
                        ->where('lead_stage_id', LeadStage::NEW)
                        ->whereNull('first_contacted_at')
                        ->where('created_at', '<=', $cutoff)
                        ->with(['vehicle', 'assignedUser'])
                        ->orderBy('created_at')
                        ->limit(20)
                        ->get();

                    if ($overdueLeads->isEmpty()) {
                        continue;
                    }

                    $cacheKey = 'lead_sla_alert:'.$dealer->id.':'.now()->format('Y-m-d');
                    if (Cache::has($cacheKey)) {
                        continue;
                    }

                    $email = $dealer->owner?->email;
                    if (! $email) {
                        continue;
                    }

                    try {
                        $mailService->sendMailable(
                            $email,
                            new DealerLeadSlaAlertMail(
                                dealerName: $dealer->name ?? 'Dealer',
                                leads: $overdueLeads->map(fn (Lead $lead) => [
                                    'id' => $lead->id,
                                    'vehicle_title' => $lead->vehicle?->title,
                                    'assigned_to' => $lead->assignedUser?->name,
                                    'hours_waiting' => (int) $lead->created_at->diffInHours(now()),
                                    'created_at' => $lead->created_at->format('Y-m-d H:i'),
                                ])->all(),
                                slaHours: self::SLA_HOURS,
                            ),
                            ['mail_type' => 'dealer_lead_sla_alert', 'dealer_id' => $dealer->id],
                            false
                        );
                        Cache::put($cacheKey, true, now()->addHours(20));
                        $sent++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to send lead SLA alert', [
                            'dealer_id' => $dealer->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Sent {$sent} lead SLA alert(s).");

        return self::SUCCESS;
    }
}
