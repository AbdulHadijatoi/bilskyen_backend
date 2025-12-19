<?php

namespace App\Services;

use App\Models\PushNotificationSubscription;
use App\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Subscribe user to push notifications
     */
    public function subscribeUser(
        array $subscriptionData,
        ?int $userId = null,
        ?string $deviceId = null
    ): bool {
        try {
            $update = [
                'endpoint' => $subscriptionData['endpoint'],
                'keys' => [
                    'p256dh' => $subscriptionData['keys']['p256dh'],
                    'auth' => $subscriptionData['keys']['auth'],
                ],
                'is_active' => true,
            ];

            if (isset($subscriptionData['expirationTime'])) {
                $update['expiration_time'] = $subscriptionData['expirationTime']
                    ? \Carbon\Carbon::createFromTimestamp($subscriptionData['expirationTime'])
                    : null;
            }

            if ($userId) {
                $update['user_id'] = $userId;
            }

            if ($deviceId) {
                $update['device_id'] = $deviceId;
            }

            PushNotificationSubscription::updateOrCreate(
                ['endpoint' => $subscriptionData['endpoint']],
                $update
            );

            return true;
        } catch (\Exception $e) {
            Log::error('subscribeUser error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribeUser(string $endpoint): bool
    {
        try {
            PushNotificationSubscription::where('endpoint', $endpoint)
                ->update(['is_active' => false]);

            return true;
        } catch (\Exception $e) {
            Log::error('unsubscribeUser error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification
     */
    public function sendNotification(
        string $message,
        ?string $endpoint = null,
        ?int $userId = null
    ): array {
        try {
            if (!$endpoint && !$userId) {
                throw new \Exception('Provide either endpoint or userId');
            }

            // Get subscriptions
            $subscriptions = [];
            if ($endpoint) {
                $sub = PushNotificationSubscription::where('endpoint', $endpoint)
                    ->where('is_active', true)
                    ->first();
                if ($sub) {
                    $subscriptions[] = $sub;
                }
            } elseif ($userId) {
                $subscriptions = PushNotificationSubscription::where('user_id', $userId)
                    ->where('is_active', true)
                    ->get()
                    ->all();
            }

            if (empty($subscriptions)) {
                return [
                    'success' => false,
                    'error' => 'No active subscriptions found',
                    'sent' => 0,
                    'failed' => 0,
                    'deactivated' => 0,
                ];
            }

            // Configure VAPID
            $vapidPublicKey = config('services.webpush.public_key');
            $vapidPrivateKey = config('services.webpush.private_key');
            $vapidSubject = config('services.webpush.subject');

            if (!$vapidPublicKey || !$vapidPrivateKey) {
                return [
                    'success' => false,
                    'error' => 'VAPID keys not configured',
                    'sent' => 0,
                    'failed' => 0,
                    'deactivated' => 0,
                ];
            }

            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $vapidSubject,
                    'publicKey' => $vapidPublicKey,
                    'privateKey' => $vapidPrivateKey,
                ],
            ]);

            $payload = json_encode([
                'title' => 'Test Notification',
                'body' => $message,
                'icon' => '/icons/icon.png',
            ]);

            $sent = 0;
            $failed = 0;
            $deactivated = 0;

            foreach ($subscriptions as $subscription) {
                try {
                    $wpSub = Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'keys' => [
                            'p256dh' => $subscription->p256dh,
                            'auth' => $subscription->auth,
                        ],
                    ]);

                    $webPush->queueNotification($wpSub, $payload);
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Error queueing notification: ' . $e->getMessage());
                }
            }

            // Flush notifications
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;
                } else {
                    $failed++;
                    $statusCode = $report->getStatusCode();

                    // Deactivate dead endpoints
                    if (in_array($statusCode, [404, 410])) {
                        PushNotificationSubscription::where('endpoint', $report->getEndpoint())
                            ->update(['is_active' => false]);
                        $deactivated++;
                    }
                }
            }

            return [
                'success' => $sent > 0,
                'sent' => $sent,
                'failed' => $failed,
                'deactivated' => $deactivated,
            ];
        } catch (\Exception $e) {
            Log::error('sendNotification error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send notification',
                'sent' => 0,
                'failed' => 0,
                'deactivated' => 0,
            ];
        }
    }
}

