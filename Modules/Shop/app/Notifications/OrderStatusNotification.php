<?php

namespace Modules\Shop\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Shop\Models\Order;

class OrderStatusNotification extends Notification implements ShouldQueue
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
            ->subject('Mise à jour commande #' . $this->order->id)
            ->greeting('Bonjour !')
            ->line('Le statut de votre commande #' . $this->order->id . ' a été mis à jour.')
            ->line('Nouveau statut : ' . $this->order->status)
            ->action('Voir la boutique', url(config('shop.routes.prefix', 'boutique')))
            ->line('Nous vous remercions de votre confiance.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Commande #' . $this->order->id . ' — statut mis à jour',
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'message' => 'Commande #' . $this->order->id . ' — statut : ' . $this->order->status,
        ];
    }
}
