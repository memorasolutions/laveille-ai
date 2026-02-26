<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->paginate(15);

        return $this->respondSuccess($notifications);
    }

    public function markRead(string $id, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->respondSuccess(null, 'Notification marquée comme lue');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->respondSuccess(null, 'Toutes les notifications marquées comme lues');
    }

    public function destroy(string $id, Request $request): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return $this->respondSuccess(null, 'Notification supprimée');
    }
}
