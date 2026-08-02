<?php

namespace App\Console\Commands;

use App\Services\SuggestionService;
use Illuminate\Console\Command;

class RefreshSearchSuggestionsCommand extends Command
{
    protected $signature = 'suggestions:refresh';

    protected $description = 'Rebuild inventory-grounded search and advisor suggestion pools (no AI)';

    public function handle(SuggestionService $suggestionService): int
    {
        $this->info('Refreshing search suggestion pools...');
        $count = $suggestionService->refresh();
        $this->info("Cached {$count} suggestion items across locales and surfaces.");

        return self::SUCCESS;
    }
}
