<?php

namespace Modules\Shop\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Shop\Models\Order;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;
    public ?string $trackingUrl;

    public function __construct(Order $order, ?string $trackingUrl = null)
    {
        $this->order = $order;
        $this->trackingUrl = $trackingUrl;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Votre commande #' . $this->order->id . ' a été expédiée')
            ->greeting('Bonjour !')
            ->line('Bonne nouvelle ! Votre commande #' . $this->order->id . ' a été expédiée.');

        if ($this->trackingUrl) {
            $mail->action('Suivre mon colis', $this->trackingUrl);
        }

        return $mail->line('Merci pour votre patience !');
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'tracking_url' => $this->trackingUrl,
            'message' => 'Votre commande #' . $this->order->id . ' a été expédiée.',
        ];
    }
}
