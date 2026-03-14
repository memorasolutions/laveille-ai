<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    public function sendToUser(object $user, Notification $notification): void
    {
        $user->notify($notification);
    }

    public function sendToUsers(iterable $users, Notification $notification): void
    {
        NotificationFacade::send($users, $notification);
    }

    public function markAsRead(object $user, ?string $notificationId = null): void
    {
        if ($notificationId) {
            $user->notifications()->where('id', $notificationId)->update(['read_at' => now()]);
        } else {
            $user->unreadNotifications->markAsRead();
        }
    }

    public function getUnread(object $user, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return $user->unreadNotifications()->limit($limit)->get();
    }

    public function getAll(object $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $user->notifications()->limit($limit)->get();
    }

    public function deleteOld(object $user, int $daysOld = 90): int
    {
        return $user->notifications()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
