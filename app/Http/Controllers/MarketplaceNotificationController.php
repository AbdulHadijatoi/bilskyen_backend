<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceUserNotification;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceNotificationController extends Controller
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return $this->unauthorized();
        }

        return $this->success(['notifications' => $this->formatNotifications($user)]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return $this->success(['count' => 0]);
        }

        $count = MarketplaceUserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return $this->success(['count' => $count]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (! $user) {
            return $this->unauthorized();
        }

        $ids = $request->input('ids', []);
        if (! is_array($ids) || $ids === []) {
            MarketplaceUserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } else {
            MarketplaceUserNotification::query()
                ->where('user_id', $user->id)
                ->whereIn('id', $ids)
                ->update(['read_at' => now()]);
        }

        return $this->success(['message' => __('messages.api.notifications_marked_read')]);
    }

    private function resolveUser(Request $request): ?User
    {
        return $this->authService->getAuthenticatedUser($request) ?? $request->user();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formatNotifications(User $user): array
    {
        return MarketplaceUserNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (MarketplaceUserNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'action_url' => $n->action_url,
                'vehicle_id' => $n->vehicle_id,
                'read_at' => $n->read_at?->toISOString(),
                'created_at' => $n->created_at?->toISOString(),
            ])
            ->values()
            ->all();
    }
}
