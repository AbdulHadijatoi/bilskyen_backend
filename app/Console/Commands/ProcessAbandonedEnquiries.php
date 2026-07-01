<?php

namespace App\Console\Commands;

use App\Services\Marketing\AbandonedEnquiryService;
use Illuminate\Console\Command;

class ProcessAbandonedEnquiries extends Command
{
    protected $signature = 'marketing:process-abandoned {--limit=30}';

    protected $description = 'Queue reminder emails for abandoned enquiry forms';

    public function handle(AbandonedEnquiryService $service): int
    {
        $count = $service->processAbandoned((int) $this->option('limit'));
        $this->info("Queued {$count} abandoned enquiry reminders.");

        return self::SUCCESS;
    }
}
