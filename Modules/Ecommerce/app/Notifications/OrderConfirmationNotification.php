<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Order;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.url').'/account/orders/'.$this->order->id;
        $currency = (string) config('modules.ecommerce.currency', 'CAD');

        return (new MailMessage)
            ->subject('Confirmation de commande #'.$this->order->order_number)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Nous avons bien reçu votre commande.')
            ->line('Total : '.number_format((float) $this->order->total, 2).' '.$currency)
            ->action('Voir ma commande', $url)
            ->line('Merci de votre confiance.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
        ];
    }
}
