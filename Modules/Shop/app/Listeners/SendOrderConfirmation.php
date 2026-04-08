<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Notifications\OrderConfirmedNotification;

class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShopOrderPaid $event): void
    {
        $order = $event->order;

        if ($order->user) {
            $order->user->notify(new OrderConfirmedNotification($order));
        } elseif ($order->email) {
            Notification::route('mail', $order->email)->notify(new OrderConfirmedNotification($order));
        }
    }
}
