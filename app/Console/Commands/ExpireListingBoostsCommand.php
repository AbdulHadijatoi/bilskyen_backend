<?php

namespace App\Console\Commands;

use App\Services\ListingBoostService;
use Illuminate\Console\Command;

class ExpireListingBoostsCommand extends Command
{
    protected $signature = 'listings:expire-boosts';

    protected $description = 'Remove expired listing boosts';

    public function handle(ListingBoostService $listingBoostService): int
    {
        $expired = $listingBoostService->expireStaleBoosts();
        $this->info("Removed {$expired} expired boost(s).");

        return self::SUCCESS;
    }
}
