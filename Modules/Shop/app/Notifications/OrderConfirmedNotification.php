<?php

namespace Modules\Shop\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Shop\Models\Order;

class OrderConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->order->load('items.product');
        $trackingUrl = route('shop.order-lookup') . '?order_number=' . urlencode((string) $this->order->order_number); // jamais le courriel dans l'URL (journaux, historique, Referer)

        return (new MailMessage)
            ->subject('Confirmation de votre commande #' . $this->order->id)
            ->view('shop::emails.order-confirmed', [
                'order' => $this->order,
                'trackingUrl' => $trackingUrl,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Commande #' . $this->order->id . ' confirmée',
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => 'Votre commande #' . $this->order->id . ' a été confirmée.',
        ];
    }
}
