<?php

namespace App\Console\Commands;

use App\Services\ListingBillingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ChargeDailyListingsCommand extends Command
{
    protected $signature = 'subscriptions:charge-daily-listings {--date=}';

    protected $description = 'Charge daily usage for published listings on pay-as-you-go plans';

    public function handle(ListingBillingService $billingService): int
    {
        $timezone = $billingService->marketplaceTimezone();
        $billingDate = $this->option('date')
            ? Carbon::parse($this->option('date'), $timezone)
            : now($timezone)->subDay();

        $charged = $billingService->chargeForDate($billingDate);

        $this->info("Created {$charged} billing period(s) for {$billingDate->toDateString()}.");

        return self::SUCCESS;
    }
}
