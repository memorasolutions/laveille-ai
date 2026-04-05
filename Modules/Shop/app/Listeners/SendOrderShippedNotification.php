<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Shop\Events\ShopOrderShipped;
use Modules\Shop\Notifications\OrderShippedNotification;

class SendOrderShippedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShopOrderShipped $event): void
    {
        if ($event->order->user) {
            $event->order->user->notify(new OrderShippedNotification($event->order, $event->trackingUrl));
        }
    }
}
