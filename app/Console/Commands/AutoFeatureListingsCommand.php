<?php

namespace App\Console\Commands;

use App\Models\Dealer;
use App\Services\DealerAutoFeatureService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Console\Command;

class AutoFeatureListingsCommand extends Command
{
    protected $signature = 'listings:auto-feature';

    protected $description = 'Auto-feature top health-score listings for Premium dealers';

    public function handle(
        DealerAutoFeatureService $autoFeatureService,
        SubscriptionFeatureService $subscriptionFeatureService,
    ): int {
        $updated = 0;

        Dealer::query()->chunkById(50, function ($dealers) use (
            $autoFeatureService,
            $subscriptionFeatureService,
            &$updated
        ) {
            foreach ($dealers as $dealer) {
                if (! $subscriptionFeatureService->hasFeature($dealer, 'auto_feature_listings')) {
                    continue;
                }

                $updated += $autoFeatureService->syncDealerFeaturedListings($dealer);
            }
        });

        $this->info("Updated {$updated} featured listing assignment(s).");

        return self::SUCCESS;
    }
}
