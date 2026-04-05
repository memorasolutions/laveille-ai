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
        return (new MailMessage)
            ->subject('Confirmation de votre commande #' . $this->order->id)
            ->greeting('Bonjour !')
            ->line('Votre commande #' . $this->order->id . ' a bien ete confirmee.')
            ->line('Montant total : ' . number_format($this->order->total, 2, ',', ' ') . ' $')
            ->action('Voir la boutique', url(config('shop.routes.prefix', 'boutique')))
            ->line('Merci pour votre achat !');
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => 'Votre commande #' . $this->order->id . ' a ete confirmee.',
        ];
    }
}
