<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Ecommerce\Models\Order;

class OrderRefundedNotification extends TemplatedNotification
{
    public function __construct(
        public Order $order,
        public float $amount,
    ) {}

    protected function getTemplateSlug(): string
    {
        return 'ecommerce_order_refunded';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'order' => [
                'number' => $this->order->order_number,
                'url' => config('app.url').'/account/orders/'.$this->order->id,
            ],
            'refund' => ['amount' => number_format($this->amount, 2)],
            'currency' => (string) config('modules.ecommerce.currency', 'CAD'),
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $currency = (string) config('modules.ecommerce.currency', 'CAD');

        return (new MailMessage)
            ->subject('Remboursement commande #'.$this->order->order_number)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre remboursement de '.number_format($this->amount, 2).' '.$currency.' pour la commande #'.$this->order->order_number.' a été traité.')
            ->line('Le montant sera crédité sur votre compte dans un délai de 5 à 10 jours ouvrables.')
            ->action('Voir ma commande', config('app.url').'/account/orders/'.$this->order->id)
            ->line('Merci de votre patience.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'refund_amount' => $this->amount,
        ];
    }
}
