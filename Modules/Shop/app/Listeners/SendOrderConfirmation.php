<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Gelato\ZeroErreur;
use Modules\Shop\Models\Order;
use Modules\Shop\Notifications\OrderConfirmedNotification;

class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShopOrderPaid $event): void
    {
        $order = $event->order;

        // Drapeau « zéro erreur » : un rejeu Stripe ré-émet ShopOrderPaid, mais le client ne reçoit qu'UNE confirmation.
        // Réservation atomique (compare-and-set) avant l'envoi ; libérée si l'envoi échoue pour permettre la reprise.
        $guarded = ZeroErreur::enabled();
        if ($guarded) {
            $claimed = Order::whereKey($order->id)->whereNull('confirmation_sent_at')->update(['confirmation_sent_at' => now()]);
            if ($claimed !== 1) {
                return;
            }
        }

        try {
            if ($order->user) {
                $order->user->notify(new OrderConfirmedNotification($order));
            } elseif ($order->email) {
                Notification::route('mail', $order->email)->notify(new OrderConfirmedNotification($order));
            }
        } catch (\Throwable $e) {
            if ($guarded) {
                Order::whereKey($order->id)->update(['confirmation_sent_at' => null]);
            }
            throw $e;
        }
    }
}
