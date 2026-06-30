<?php

namespace App\Console\Commands;

use App\Services\SubscriptionLifecycleService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire dealer subscriptions past their ends_at date';

    public function handle(SubscriptionLifecycleService $lifecycleService): int
    {
        $count = $lifecycleService->expireDueSubscriptions();

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
