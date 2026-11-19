<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate($perPage);

        return NotificationResource::collection($notifications);
    }

    /**
     * Get the unread notification count for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): NotificationResource
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return new NotificationResource($notification);
    }

    /**
     * Mark all unread notifications of the authenticated user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Tất cả thông báo đã được đánh dấu là đã đọc.',
        ]);
    }

    /**
     * Dismiss / delete a notification from the user's inbox.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->delete();

        return response()->json([
            'message' => 'Đã xóa thông báo khỏi danh sách.',
        ]);
    }
}
