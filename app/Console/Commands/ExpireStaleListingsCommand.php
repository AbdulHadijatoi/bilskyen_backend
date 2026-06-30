<?php

namespace App\Console\Commands;

use App\Services\ListingExpirationService;
use Illuminate\Console\Command;

class ExpireStaleListingsCommand extends Command
{
    protected $signature = 'listings:expire-stale';

    protected $description = 'Archive published listings past their expires_at date';

    public function handle(ListingExpirationService $expirationService): int
    {
        $count = $expirationService->expireStaleListings();

        $this->info("Archived {$count} expired listing(s).");

        return self::SUCCESS;
    }
}
