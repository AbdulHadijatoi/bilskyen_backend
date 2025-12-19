<?php

namespace App\Http\Controllers;

use App\Models\PushNotificationSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushNotificationController extends Controller
{
    public function __construct(
        private PushNotificationService $pushNotificationService
    ) {}
    /**
     * Subscribe user to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'sub.endpoint' => 'required|url',
            'sub.keys.p256dh' => 'required|string',
            'sub.keys.auth' => 'required|string',
            'userId' => 'nullable|exists:users,id',
            'deviceId' => 'nullable|string',
        ]);

        $sub = $request->input('sub');
        $userId = $request->input('userId');
        $deviceId = $request->input('deviceId');

        $subscription = PushNotificationSubscription::updateOrCreate(
            ['endpoint' => $sub['endpoint']],
            [
                'p256dh' => $sub['keys']['p256dh'],
                'auth' => $sub['keys']['auth'],
                'user_id' => $userId,
                'device_id' => $deviceId,
                'expiration_time' => isset($sub['expirationTime']) ? new \DateTime($sub['expirationTime']) : null,
                'is_active' => true,
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Unsubscribe user from push notifications
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        PushNotificationSubscription::where('endpoint', $request->input('endpoint'))
            ->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }

    /**
     * Send push notification
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'endpoint' => 'nullable|url',
            'userId' => 'nullable|exists:users,id',
        ]);

        $message = $request->input('message');
        $endpoint = $request->input('endpoint');
        $userId = $request->input('userId');

        if (!$endpoint && !$userId) {
            return response()->json([
                'success' => false,
                'error' => 'Provide either endpoint or userId',
            ], 400);
        }

        $result = $this->pushNotificationService->sendNotification(
            $message,
            $endpoint,
            $userId ? (int) $userId : null
        );

        $statusCode = $result['success'] ? 200 : 400;
        return response()->json($result, $statusCode);
    }
}

