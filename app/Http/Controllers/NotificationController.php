<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use App\Helpers\FilterHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get user notifications
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $filters = [
            'unread' => $request->input('unread') === 'true',
            'search' => $request->input('search'),
            'sort' => json_decode($request->input('sort', '[]'), true),
            'perPage' => $request->input('perPage', 10),
        ];

        $notifications = $this->notificationService->getUserNotifications($user, $filters);

        // Add is_read field
        $notifications->getCollection()->transform(function ($notification) use ($user) {
            return [
                ...$notification->toArray(),
                'is_read' => $notification->isReadBy($user),
                'reads_count' => $notification->reads_count,
            ];
        });

        return $this->paginatedResponse($notifications);
    }

    /**
     * Get notification count
     */
    public function getCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $unread = $request->input('unread', 'true') === 'true';
        $since = $request->input('since') ? new \DateTime($request->input('since')) : null;

        if ($unread) {
            $count = $this->notificationService->getUnreadCount($user, $since);
        } else {
            $user->load('roles');
            $userRoleNames = $user->roles->pluck('name')->toArray();
            $count = Notification::where(function ($q) use ($userRoleNames) {
                $q->where(function ($subQ) use ($userRoleNames) {
                    foreach ($userRoleNames as $roleName) {
                        $subQ->orWhereJsonContains('target_roles', $roleName);
                    }
                })->orWhereJsonLength('target_roles', 0);
            })->count();
        }

        return response()->json(['count' => $count]);
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $ids = $request->input('ids', []);

        $result = $this->notificationService->markAsRead($user, $ids);

        return response()->json($result);
    }

    /**
     * Dispatch notifications (cron job)
     */
    public function dispatch(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 50), 200);

        $result = $this->notificationService->dispatchPendingNotifications($limit);

        return response()->json($result);
    }

    /**
     * Create notification
     */
    public function create(Request $request): JsonResponse
    {
        $notification = $this->notificationService->createNotification($request->all());

        return response()->json($notification, 201);
    }

    /**
     * Update notification
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:notifications,id',
        ]);

        $notification = Notification::findOrFail($request->input('id'));
        $notification = $this->notificationService->updateNotification($notification, $request->all());

        return response()->json($notification);
    }

    /**
     * Delete notification
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:notifications,id',
        ]);

        $notification = Notification::findOrFail($request->input('id'));
        $this->notificationService->deleteNotification($notification);

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    /**
     * Format paginated response
     */
    private function paginatedResponse($paginator): JsonResponse
    {
        return response()->json([
            'docs' => $paginator->items(),
            'totalDocs' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'page' => $paginator->currentPage(),
            'totalPages' => $paginator->lastPage(),
            'hasPrevPage' => $paginator->currentPage() > 1,
            'hasNextPage' => $paginator->hasMorePages(),
            'prevPage' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
            'nextPage' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ]);
    }
}

