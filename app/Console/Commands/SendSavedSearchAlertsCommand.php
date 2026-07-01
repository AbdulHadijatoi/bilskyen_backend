<?php

namespace App\Console\Commands;

use App\Services\MarketplaceAlertService;
use Illuminate\Console\Command;

class SendSavedSearchAlertsCommand extends Command
{
    protected $signature = 'alerts:saved-searches';

    protected $description = 'Email users when new vehicles match their saved searches';

    public function handle(MarketplaceAlertService $alertService): int
    {
        $sent = $alertService->sendSavedSearchAlerts();
        $this->info("Sent {$sent} saved search alert(s).");

        return self::SUCCESS;
    }
}
