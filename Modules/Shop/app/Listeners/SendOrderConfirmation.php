<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Notifications\OrderConfirmedNotification;

class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShopOrderPaid $event): void
    {
        if ($event->order->user) {
            $event->order->user->notify(new OrderConfirmedNotification($event->order));
        }
    }
}
