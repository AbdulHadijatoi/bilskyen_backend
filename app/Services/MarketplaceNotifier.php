<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\User;

class MarketplaceNotifier
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function notifyDealerOwner(Dealer $dealer, string $title, string $message, array $metadata = []): void
    {
        $dealer->loadMissing('owner');
        $owner = $dealer->owner;
        if (! $owner) {
            return;
        }

        $this->notificationService->createNotification([
            'title' => $title,
            'message' => $message,
            'target_roles' => ['dealer'],
            'scheduled_at' => now(),
            'metadata' => array_merge($metadata, [
                'dealer_id' => $dealer->id,
                'user_id' => $owner->id,
            ]),
        ]);
    }

    public function notifyUser(User $user, string $title, string $message, array $metadata = []): void
    {
        $roles = $user->getRoleNames()->toArray();
        if ($roles === []) {
            $roles = ['seller'];
        }

        $this->notificationService->createNotification([
            'title' => $title,
            'message' => $message,
            'target_roles' => $roles,
            'scheduled_at' => now(),
            'metadata' => array_merge($metadata, [
                'user_id' => $user->id,
            ]),
        ]);
    }
}
