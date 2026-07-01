<?php

namespace App\Console\Commands;

use App\Models\Dealer;
use App\Models\DealerSyndicationSetting;
use App\Services\Syndication\SyndicationService;
use Illuminate\Console\Command;

class SyncSyndicationFeeds extends Command
{
    protected $signature = 'syndication:sync {--dealer= : Sync a single dealer id}';

    protected $description = 'Regenerate syndication feeds for dealers with enabled providers';

    public function handle(SyndicationService $service): int
    {
        if ($dealerId = $this->option('dealer')) {
            $dealer = Dealer::findOrFail((int) $dealerId);
            $count = $service->syncDealer($dealer);
            $this->info("Synced {$count} vehicles for dealer {$dealer->id}.");

            return self::SUCCESS;
        }

        $dealerIds = DealerSyndicationSetting::where('enabled', true)->distinct()->pluck('dealer_id');
        $total = 0;
        foreach ($dealerIds as $id) {
            $dealer = Dealer::find($id);
            if ($dealer) {
                $total += $service->syncDealer($dealer);
            }
        }

        $this->info("Synced {$total} vehicle records across {$dealerIds->count()} dealers.");

        return self::SUCCESS;
    }
}
