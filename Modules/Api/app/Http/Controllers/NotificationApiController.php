<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Notifications
 *
 * Endpoints for reading and managing the authenticated user's notifications.
 */
class NotificationApiController extends BaseApiController
{
    /**
     * Return a paginated list of the authenticated user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return $this->respondSuccess($notifications);
    }

    /**
     * Mark a single notification as read by its ID.
     */
    public function markRead(string $id, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->respondSuccess(null, 'Notification marquée comme lue');
    }

    /**
     * Mark all unread notifications as read in one operation.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->respondSuccess(null, 'Toutes les notifications marquées comme lues');
    }

    /**
     * Permanently delete a notification by its ID.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return $this->respondSuccess(null, 'Notification supprimée');
    }
}
