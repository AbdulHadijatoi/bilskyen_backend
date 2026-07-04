<?php

namespace App\Console\Commands;

use App\Services\ListingHealthService;
use Illuminate\Console\Command;

class ComputeListingHealthScoresCommand extends Command
{
    protected $signature = 'listings:compute-health-scores
                            {--dealer= : Compute scores for a single dealer ID}';

    protected $description = 'Precompute listing health scores for dealer inventory';

    public function handle(ListingHealthService $service): int
    {
        if ($dealerId = $this->option('dealer')) {
            $count = $service->computeAndStoreDealerScores((int) $dealerId);
            $this->info("Computed health scores for {$count} vehicle(s) on dealer {$dealerId}.");

            return self::SUCCESS;
        }

        $count = $service->computeAllDealerScores();
        $this->info("Computed health scores for {$count} published vehicle(s).");

        return self::SUCCESS;
    }
}
