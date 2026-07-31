<?php

namespace App\Console\Commands;

use App\Services\CityIndexService;
use Illuminate\Console\Command;

class ReindexMarketplaceCitiesCommand extends Command
{
    protected $signature = 'cities:reindex';

    protected $description = 'Rebuild marketplace city SEO index from dealers, locations, and published vehicles';

    public function handle(CityIndexService $cityIndexService): int
    {
        $this->info('Reindexing marketplace cities...');
        $count = $cityIndexService->reindexAll();
        $this->info("Updated {$count} cities.");

        return self::SUCCESS;
    }
}
