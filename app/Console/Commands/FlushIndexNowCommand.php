<?php

namespace App\Console\Commands;

use App\Services\Seo\IndexNowService;
use Illuminate\Console\Command;

class FlushIndexNowCommand extends Command
{
    protected $signature = 'indexnow:flush';

    protected $description = 'Submit queued listing URLs to the IndexNow endpoint';

    public function handle(IndexNowService $indexNow): int
    {
        if (! $indexNow->isEnabled()) {
            $this->info('IndexNow is disabled (set INDEXNOW_KEY to enable).');

            return self::SUCCESS;
        }

        $queued = count($indexNow->queuedUrls());
        $sent = $indexNow->flush();
        $this->info("IndexNow flushed {$sent} of {$queued} queued URL(s).");

        return self::SUCCESS;
    }
}
