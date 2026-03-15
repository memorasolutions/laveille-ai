<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Ecommerce\Models\Order;

class OrderShippedNotification extends Notification implements ShouldQueue
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

        $mail = (new MailMessage)
            ->subject('Votre commande a été expédiée')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Bonne nouvelle! Votre commande #'.$this->order->order_number.' a été expédiée.');

        if ($this->order->tracking_number) {
            $mail->line('Numéro de suivi : '.$this->order->tracking_number);
        }

        return $mail
            ->action('Suivre ma commande', $url)
            ->line('Merci de votre confiance.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'tracking_number' => $this->order->tracking_number,
        ];
    }
}
