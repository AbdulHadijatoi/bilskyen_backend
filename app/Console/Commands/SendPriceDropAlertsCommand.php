<?php

namespace App\Console\Commands;

use App\Services\MarketplaceAlertService;
use Illuminate\Console\Command;

class SendPriceDropAlertsCommand extends Command
{
    protected $signature = 'alerts:price-drops';

    protected $description = 'Email users when a favorited vehicle price drops';

    public function handle(MarketplaceAlertService $alertService): int
    {
        $sent = $alertService->sendPriceDropAlerts();
        $this->info("Sent {$sent} price drop alert(s).");

        return self::SUCCESS;
    }
}
