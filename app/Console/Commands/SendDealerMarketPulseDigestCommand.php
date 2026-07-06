<?php

namespace App\Console\Commands;

use App\Constants\AiGenerationTask;
use App\Mail\DealerMarketPulseDigestMail;
use App\Models\Dealer;
use App\Services\AiService;
use App\Services\ListingHealthService;
use App\Services\MailService;
use App\Services\MarketPulseService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Console\Command;

class SendDealerMarketPulseDigestCommand extends Command
{
    protected $signature = 'dealers:market-pulse-digest';

    protected $description = 'Send weekly market pulse and listing attention digest emails to active dealers';

    public function handle(
        MarketPulseService $marketPulseService,
        ListingHealthService $listingHealthService,
        AiService $aiService,
        MailService $mailService,
        SubscriptionFeatureService $subscriptionFeatureService,
    ): int {
        $sent = 0;

        Dealer::query()
            ->with('owner')
            ->whereHas('owner', fn ($q) => $q->whereNotNull('email'))
            ->chunkById(50, function ($dealers) use ($marketPulseService, $listingHealthService, $aiService, $mailService, $subscriptionFeatureService, &$sent) {
                foreach ($dealers as $dealer) {
                    if (! $subscriptionFeatureService->hasActiveSubscription($dealer)
                        || ! $subscriptionFeatureService->hasFeature($dealer, 'market_pulse')) {
                        continue;
                    }

                    $email = $dealer->owner?->email;
                    if (! $email) {
                        continue;
                    }

                    $pulse = $marketPulseService->compareDealer($dealer->id);
                    $summaries = collect($pulse['comparisons'] ?? [])
                        ->pluck('summary')
                        ->filter()
                        ->values()
                        ->all();

                    $attentionSummaries = $listingHealthService->attentionSummariesForDealer($dealer->id, 3);
                    $portfolio = $listingHealthService->getPortfolioSummary($dealer->id);
                    $aiBriefing = null;

                    if ($attentionSummaries !== [] && $aiService->dealerCanUseAi($dealer)
                        && app(\App\Services\SubscriptionFeatureService::class)->hasFeature($dealer, 'listing_health_ai_briefing')) {
                        try {
                            $topItem = $listingHealthService->vehiclesNeedingAttention($dealer->id, 1)[0] ?? null;
                            if ($topItem) {
                                $result = $aiService->generate(
                                    task: AiGenerationTask::LISTING_HEALTH_REWRITE,
                                    context: [
                                        'dealer_name' => $dealer->name,
                                        'portfolio' => $portfolio,
                                        'top_listing' => $topItem,
                                        'attention_summaries' => $attentionSummaries,
                                    ],
                                    user: $dealer->owner,
                                    dealer: $dealer,
                                    locale: app()->getLocale(),
                                    contextType: 'dealer',
                                    contextId: $dealer->id,
                                );
                                $aiBriefing = $result['text'] ?? null;
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Listing health AI briefing failed', [
                                'dealer_id' => $dealer->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    if ($summaries === [] && $attentionSummaries === []) {
                        continue;
                    }

                    try {
                        $mailService->sendMailable(
                            $email,
                            new DealerMarketPulseDigestMail(
                                dealerName: $dealer->name ?? __('messages.common.dealer_fallback'),
                                summaries: $summaries,
                                attentionSummaries: $attentionSummaries,
                                portfolio: $portfolio,
                                aiBriefing: $aiBriefing,
                            ),
                            ['mail_type' => 'dealer_market_pulse_digest', 'dealer_id' => $dealer->id],
                            false
                        );
                        $sent++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to send dealer market pulse digest', [
                            'dealer_id' => $dealer->id,
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Sent {$sent} market pulse digest(s).");

        return self::SUCCESS;
    }
}
