<?php

namespace App\Console\Commands;

use App\Mail\DealerPriceChangeAlertMail;
use App\Models\Dealer;
use App\Services\ListingHealthEventService;
use App\Services\MailService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendDealerPriceChangeAlertsCommand extends Command
{
    protected $signature = 'dealers:price-change-alerts';

    protected $description = 'Email dealers about published listings with stale prices (14+ days unchanged)';

    public function handle(
        ListingHealthEventService $listingHealthEventService,
        SubscriptionFeatureService $subscriptionFeatureService,
        MailService $mailService,
    ): int {
        $sent = 0;

        Dealer::query()
            ->with('owner')
            ->whereHas('owner', fn ($q) => $q->whereNotNull('email'))
            ->chunkById(50, function ($dealers) use (
                $listingHealthEventService,
                $subscriptionFeatureService,
                $mailService,
                &$sent
            ) {
                foreach ($dealers as $dealer) {
                    if (! $subscriptionFeatureService->hasFeature($dealer, 'price_change_alerts')) {
                        continue;
                    }

                    $email = $dealer->owner?->email;
                    if (! $email) {
                        continue;
                    }

                    $vehicles = $listingHealthEventService->stalePriceVehiclesForDealer($dealer->id);
                    if ($vehicles === []) {
                        continue;
                    }

                    $cacheKey = 'price_change_alert:'.$dealer->id.':'.now()->format('Y-W');
                    if (Cache::has($cacheKey)) {
                        continue;
                    }

                    try {
                        $mailService->sendMailable(
                            $email,
                            new DealerPriceChangeAlertMail(
                                dealerName: $dealer->name ?? 'Dealer',
                                vehicles: $vehicles,
                            ),
                            ['mail_type' => 'dealer_price_change_alert', 'dealer_id' => $dealer->id],
                            false
                        );
                        Cache::put($cacheKey, true, now()->addDays(7));
                        $sent++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Failed to send dealer price change alert', [
                            'dealer_id' => $dealer->id,
                            'email' => $email,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Sent {$sent} price change alert(s).");

        return self::SUCCESS;
    }
}
