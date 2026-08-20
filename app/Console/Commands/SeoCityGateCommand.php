<?php

namespace App\Console\Commands;

use App\Models\MarketplaceCity;
use App\Services\CityIndexService;
use Illuminate\Console\Command;

class SeoCityGateCommand extends Command
{
    protected $signature = 'seo:city-gate';

    protected $description = 'Count indexable city car pages (warn at 30, fail at 50)';

    public function handle(CityIndexService $cities): int
    {
        $count = $cities->indexableCarsCount();
        $warn = MarketplaceCity::INDEXABLE_CARS_WARNING;
        $stop = MarketplaceCity::INDEXABLE_CARS_HARD_STOP;

        $this->info("Indexable city car pages: {$count} (warning {$warn}, hard stop {$stop}).");

        if ($count >= $stop) {
            $this->error("HARD STOP: {$count} indexable city pages (limit {$stop}). Do not add more city templates.");

            return self::FAILURE;
        }

        if ($count >= $warn) {
            $this->warn("WARNING: {$count} indexable city pages (threshold {$warn}). Unique local copy is required before growing further.");
        }

        return self::SUCCESS;
    }
}
