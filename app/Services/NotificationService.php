<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\Contact;
use App\Jobs\DispatchNotificationJob;
use Illuminate\Support\Facades\DB;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class NotificationService
{
    /**
     * Create a notification
     */
    public function createNotification(array $notificationData): Notification
    {
        $notification = Notification::create([
            'title' => $notificationData['title'],
            'message' => $notificationData['message'],
            'target_roles' => $notificationData['target_roles'] ?? [],
            'sent' => false,
            'scheduled_at' => $notificationData['scheduled_at'] ?? now(),
            'metadata' => $notificationData['metadata'] ?? null,
        ]);

        // Attempt instant push if due
        if ($notification->scheduled_at <= now() && !$notification->sent) {
            DispatchNotificationJob::dispatch($notification);
        }

        return $notification;
    }

    /**
     * Update a notification
     */
    public function updateNotification(Notification $notification, array $notificationData): Notification
    {
        $notification->update($notificationData);

        // Attempt instant push if due and not already sent
        if ($notification->scheduled_at <= now() && !$notification->sent) {
            DispatchNotificationJob::dispatch($notification);
        }

        return $notification;
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(Notification $notification): void
    {
        $notification->delete();
    }

    /**
     * Mark notifications as read for a user
     */
    public function markAsRead(int $userId, array $notificationIds): array
    {
        $user = User::findOrFail($userId);
        $processedIds = [];
        $alreadyReadCount = 0;
        $notFoundCount = 0;

        foreach ($notificationIds as $notificationId) {
            $notification = Notification::find($notificationId);

            if (!$notification) {
                $notFoundCount++;
                continue;
            }

            // Check if already read
            if ($notification->isReadBy($user)) {
                $alreadyReadCount++;
                continue;
            }

            // Mark as read
            $notification->reads()->attach($user->id, ['read_at' => now()]);
            $processedIds[] = $notificationId;
        }

        return [
            'requestedIds' => $notificationIds,
            'processedIds' => $processedIds,
            'updatedCount' => count($processedIds),
            'alreadyReadCount' => $alreadyReadCount,
            'notFoundCount' => $notFoundCount,
            'totalRequested' => count($notificationIds),
        ];
    }

    /**
     * Get notifications for a user with filters
     */
    public function getUserNotifications(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Notification::query();

        // Filter by user role
        $userRole = $user->role;
        $query->where(function ($q) use ($userRole) {
            $q->whereJsonContains('target_roles', $userRole)
              ->orWhereJsonLength('target_roles', 0); // Empty array means all users
        });

        // Apply filters
        if (isset($filters['unread']) && $filters['unread']) {
            $readNotificationIds = $user->readNotifications()->pluck('notifications.id');
            $query->whereNotIn('id', $readNotificationIds);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $filters['sort'] ?? [['id' => 'created_at', 'desc' => true]];
        foreach ($sortBy as $sort) {
            $query->orderBy($sort['id'], $sort['desc'] ? 'desc' : 'asc');
        }

        return $query->paginate($filters['perPage'] ?? 10);
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount(User $user, ?\DateTime $since = null): int
    {
        $readNotificationIds = $user->readNotifications()->pluck('notifications.id');

        $query = Notification::whereNotIn('id', $readNotificationIds)
            ->where(function ($q) use ($user) {
                $userRole = $user->role;
                $q->whereJsonContains('target_roles', $userRole)
                  ->orWhereJsonLength('target_roles', 0);
            });

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query->count();
    }

    /**
     * Create purchase notifications
     * Calculates purchasePrice and paidAmount from transaction entries
     */
    public function createPurchaseNotifications(
        Purchase $purchase,
        Vehicle $vehicle,
        Contact $contact,
        bool $clearExisting = false
    ): void {
        if ($clearExisting) {
            Notification::whereJsonContains('metadata->purchase_id', $purchase->id)
                ->whereJsonContains('metadata->vehicle_id', $vehicle->id)
                ->delete();
        }

        // Calculate purchasePrice and paidAmount from transaction entries
        $purchasePrice = 0;
        $paidAmount = 0;

        if ($purchase->transaction) {
            // Purchase price = sum of debit entries to Vehicle Inventory account
            $purchasePrice = $purchase->transaction->entries()
                ->where('type', 'debit')
                ->whereHas('financialAccount', function ($query) {
                    $query->where('name', 'Vehicle Inventory');
                })
                ->sum('amount');

            // Paid amount = sum of credit entries from paid from account (excluding Accounts Payable)
            $paidAmount = $purchase->transaction->entries()
                ->where('type', 'credit')
                ->where('financial_account_id', $purchase->paid_from_financial_account_id)
                ->sum('amount');
        }

        $notifications = [];
        $fifteenDaysAfterPurchase = $purchase->purchase_date->copy()->addDays(15);

        // Documents pending notification
        if (in_array('Documents pending', $vehicle->pending_works ?? [])) {
            $notifications[] = [
                'title' => "Vehicle {$vehicle->make} {$vehicle->model} has pending documents.",
                'message' => "Please ensure all documents for vehicle {$vehicle->make} {$vehicle->model} are completed.",
                'target_roles' => ['dealer'],
                'scheduled_at' => $fifteenDaysAfterPurchase,
                'metadata' => [
                    'purchase_id' => $purchase->id,
                    'vehicle_id' => $vehicle->id,
                ],
                'sent' => false,
            ];
        }

        // Blacklist flag notification
        if (count($vehicle->blacklist_flags ?? []) > 0) {
            $flags = implode(', ', $vehicle->blacklist_flags);
            $notifications[] = [
                'title' => "Vehicle {$vehicle->make} {$vehicle->model} has blacklist flags.",
                'message' => "Vehicle {$vehicle->make} {$vehicle->model} has the following blacklist issues: {$flags}",
                'target_roles' => ['dealer'],
                'scheduled_at' => $fifteenDaysAfterPurchase,
                'metadata' => [
                    'purchase_id' => $purchase->id,
                    'vehicle_id' => $vehicle->id,
                ],
                'sent' => false,
            ];
        }

        // Payment pending notification
        if ($paidAmount < $purchasePrice) {
            $pendingAmount = $purchasePrice - $paidAmount;
            $contactName = $contact->name ?? $contact->company_name;
            $notifications[] = [
                'title' => "Purchase payment pending for {$vehicle->make} {$vehicle->model}.",
                'message' => "The purchase of vehicle {$vehicle->make} {$vehicle->model} has a pending payment of {$pendingAmount}. Please follow up with {$contactName} to complete the payment.",
                'target_roles' => ['dealer'],
                'scheduled_at' => $fifteenDaysAfterPurchase,
                'metadata' => [
                    'purchase_id' => $purchase->id,
                    'vehicle_id' => $vehicle->id,
                ],
                'sent' => false,
            ];
        }

        if (count($notifications) > 0) {
            Notification::insert($notifications);
        }
    }

    /**
     * Create sale notifications
     * Calculates salePrice and receivedAmount from transaction entries
     */
    public function createSaleNotifications(
        Sale $sale,
        Vehicle $vehicle,
        Contact $contact,
        bool $clearExisting = false
    ): void {
        if ($clearExisting) {
            Notification::whereJsonContains('metadata->sale_id', $sale->id)
                ->whereJsonContains('metadata->vehicle_id', $vehicle->id)
                ->delete();
        }

        // Calculate salePrice and receivedAmount from transaction entries
        $salePrice = 0;
        $receivedAmount = 0;

        if ($sale->transaction) {
            // Sale price = sum of credit entries to Sales Revenue account
            $salePrice = $sale->transaction->entries()
                ->where('type', 'credit')
                ->whereHas('financialAccount', function ($query) {
                    $query->where('name', 'Sales Revenue');
                })
                ->sum('amount');

            // Received amount = sum of debit entries to received to account (excluding Accounts Receivable and COGS)
            $receivedAmount = $sale->transaction->entries()
                ->where('type', 'debit')
                ->where('financial_account_id', $sale->received_to_financial_account_id)
                ->whereDoesntHave('financialAccount', function ($query) {
                    $query->whereIn('name', ['Accounts Receivable', 'Cost of Goods Sold']);
                })
                ->sum('amount');
        }

        $notifications = [];
        $fifteenDaysAfterSale = $sale->sale_date->copy()->addDays(15);

        // Name Transfer pending
        if (in_array('Name transfer', $vehicle->pending_works ?? [])) {
            $notifications[] = [
                'title' => "Name transfer pending for {$vehicle->make} {$vehicle->model}.",
                'message' => "The name transfer application for vehicle {$vehicle->make} {$vehicle->model} is still pending at RTO.",
                'target_roles' => ['dealer'],
                'scheduled_at' => $fifteenDaysAfterSale,
                'metadata' => [
                    'sale_id' => $sale->id,
                    'vehicle_id' => $vehicle->id,
                ],
                'sent' => false,
            ];
        }

        // RC Name Transfer pending
        if (in_array('Registration certificate transfer', $vehicle->pending_works ?? [])) {
            $notifications[] = [
                'title' => "RC transfer pending for {$vehicle->make} {$vehicle->model}.",
                'message' => "The RC ownership update for vehicle {$vehicle->make} {$vehicle->model} has not yet reflected in VAHAN database.",
                'target_roles' => ['dealer'],
                'scheduled_at' => $fifteenDaysAfterSale,
                'metadata' => [
                    'sale_id' => $sale->id,
                    'vehicle_id' => $vehicle->id,
                ],
                'sent' => false,
            ];
        }

        // Insurance Name Transfer pending
        if (in_array('Insurance transfer', $vehicle->pending_works ?? [])) {
            $notifications[] = [
                'title' => "Insurance transfer pending for {$vehicle->make} {$vehicle->model}.",
                'message' => "The insurance policy for vehicle {$vehicle->make} {$vehicle->model} is still in the seller's name. Please update with insurer.",
                'target_roles' => ['dealer'],
                'scheduled_at' => $fifteenDaysAfterSale,
                'metadata' => [
                    'sale_id' => $sale->id,
                    'vehicle_id' => $vehicle->id,
                ],
                'sent' => false,
            ];
        }

        // Payment pending notification
        if ($receivedAmount < $salePrice) {
            $pendingAmount = $salePrice - $receivedAmount;
            $contactName = $contact->name ?? $contact->company_name;
            $notifications[] = [
                'title' => "Sale payment pending for {$vehicle->make} {$vehicle->model}.",
                'message' => "The sale of vehicle {$vehicle->make} {$vehicle->model} has a pending payment of {$pendingAmount}. Please follow up with {$contactName} to complete the payment.",
                'target_roles' => ['dealer'],
                'scheduled_at' => $fifteenDaysAfterSale,
                'metadata' => [
                    'sale_id' => $sale->id,
                    'vehicle_id' => $vehicle->id,
                ],
                'sent' => false,
            ];
        }

        if (count($notifications) > 0) {
            Notification::insert($notifications);
        }
    }

    /**
     * Dispatch pending notifications (cron job)
     */
    public function dispatchNotifications(int $limit = 50): array
    {
        $pending = Notification::where('sent', false)
            ->where('scheduled_at', '<=', now())
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $results = [];

        foreach ($pending as $notification) {
            $result = $this->sendNotification($notification);
            $results[] = [
                'id' => $notification->id,
                'title' => $notification->title,
                'audienceUsers' => $result['audienceUsers'],
                'attempted' => $result['attempted'],
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'errorsSample' => $result['errorsSample'] ?? [],
            ];

            if ($result['attempted'] > 0) {
                $notification->update(['sent' => true, 'sent_at' => now()]);
            }
        }

        return [
            'processed' => count($results),
            'notifications' => $results,
        ];
    }

    /**
     * Send a notification via web push
     */
    private function sendNotification(Notification $notification): array
    {
        // Resolve audience user IDs
        $targetUserIds = $notification->metadata['targetUserIds'] ?? [];
        $targetRoles = $notification->target_roles ?? [];

        $audienceUserIds = [];
        if (count($targetUserIds) > 0) {
            $audienceUserIds = User::whereIn('id', $targetUserIds)
                ->where('banned', false)
                ->pluck('id')
                ->toArray();
        } elseif (count($targetRoles) > 0) {
            $audienceUserIds = User::whereIn('role', $targetRoles)
                ->where('banned', false)
                ->pluck('id')
                ->toArray();
        } else {
            $audienceUserIds = User::where('banned', false)->pluck('id')->toArray();
        }

        // Load subscriptions
        $subscriptions = \App\Models\PushNotificationSubscription::where('is_active', true)
            ->whereIn('user_id', $audienceUserIds)
            ->get();

        // Send push notifications
        $vapidPublicKey = config('services.webpush.public_key');
        $vapidPrivateKey = config('services.webpush.private_key');
        $vapidSubject = config('services.webpush.subject');

        if (!$vapidPublicKey || !$vapidPrivateKey) {
            return [
                'audienceUsers' => count($audienceUserIds),
                'attempted' => 0,
                'sent' => 0,
                'failed' => 0,
            ];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $vapidSubject,
                'publicKey' => $vapidPublicKey,
                'privateKey' => $vapidPrivateKey,
            ],
        ]);

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($subscriptions as $subscription) {
            try {
                $webPushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->p256dh,
                        'auth' => $subscription->auth,
                    ],
                ]);

                $payload = json_encode([
                    'title' => $notification->title,
                    'body' => $notification->message,
                    'data' => [
                        'notificationId' => $notification->id,
                        'url' => $notification->metadata['url'] ?? '/',
                    ],
                ]);

                $webPush->queueNotification($webPushSubscription, $payload);
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }
        }

        // Flush notifications
        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                $errors[] = $report->getReason();

                // Deactivate dead endpoints
                if (in_array($report->getStatusCode(), [404, 410])) {
                    \App\Models\PushNotificationSubscription::where('endpoint', $report->getEndpoint())
                        ->update(['is_active' => false]);
                }
            }
        }

        return [
            'audienceUsers' => count($audienceUserIds),
            'attempted' => count($subscriptions),
            'sent' => $sent,
            'failed' => $failed,
            'errorsSample' => array_slice($errors, 0, 5),
        ];
    }
}

