<?php

namespace App\Services;

use App\Models\MarketplaceUserNotification;
use App\Models\User;

class MarketplaceNotificationService
{
    public function notify(
        User $user,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        ?int $vehicleId = null,
        ?array $metadata = null,
    ): MarketplaceUserNotification {
        return MarketplaceUserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'vehicle_id' => $vehicleId,
            'metadata' => $metadata,
        ]);
    }
}
