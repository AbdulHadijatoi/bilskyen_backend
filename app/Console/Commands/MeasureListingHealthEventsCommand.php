<?php

namespace App\Console\Commands;

use App\Services\ListingHealthEventService;
use Illuminate\Console\Command;

class MeasureListingHealthEventsCommand extends Command
{
    protected $signature = 'listings:measure-health-events';

    protected $description = 'Capture after-metrics for listing health fix events (7 days post-fix)';

    public function handle(ListingHealthEventService $listingHealthEventService): int
    {
        $measured = $listingHealthEventService->measurePendingEvents();
        $this->info("Measured {$measured} listing health event(s).");

        return self::SUCCESS;
    }
}
