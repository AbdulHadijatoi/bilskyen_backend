<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class DispatchNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch {--limit=50}';

    protected $description = 'Dispatch pending in-app notifications via web push';

    public function handle(NotificationService $notificationService): int
    {
        $limit = min((int) $this->option('limit'), 200);
        $result = $notificationService->dispatchNotifications($limit);

        $this->info("Processed {$result['processed']} notification(s).");

        return self::SUCCESS;
    }
}
