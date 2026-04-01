<?php

declare(strict_types=1);

namespace Modules\Community\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Community\Events\ContentPublished;
use Modules\Community\Models\CategorySubscription;
use Modules\Community\Notifications\CategoryContentNotification;

class NotifyCategorySubscribers implements ShouldQueue
{
    public function handle(ContentPublished $event): void
    {
        $subscribers = CategorySubscription::where('category_tag', $event->categoryTag)
            ->where('module', $event->module)
            ->with('user')
            ->get();

        foreach ($subscribers as $subscription) {
            if ($subscription->user) {
                Notification::send(
                    $subscription->user,
                    new CategoryContentNotification($event->content, $event->categoryTag)
                );
            }
        }
    }
}
