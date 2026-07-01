<?php

namespace App\Console\Commands;

use App\Services\Marketing\AbandonedEnquiryService;
use App\Services\Marketing\MarketingAutomationService;
use Illuminate\Console\Command;

class ProcessMarketingQueue extends Command
{
    protected $signature = 'marketing:process-queue {--limit=50}';

    protected $description = 'Send due marketing automation emails';

    public function handle(MarketingAutomationService $service): int
    {
        $count = $service->processDueEmails((int) $this->option('limit'));
        $this->info("Processed {$count} marketing emails.");

        return self::SUCCESS;
    }
}
