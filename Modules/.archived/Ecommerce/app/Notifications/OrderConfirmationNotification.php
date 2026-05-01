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

class OrderConfirmationNotification extends TemplatedNotification
{
    public function __construct(public Order $order) {}

    protected function getTemplateSlug(): string
    {
        return 'ecommerce_order_confirmation';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'order' => [
                'number' => $this->order->order_number,
                'total' => number_format((float) $this->order->total, 2),
                'date' => $this->order->created_at->format('d/m/Y'),
                'url' => config('app.url').'/account/orders/'.$this->order->id,
            ],
            'currency' => (string) config('modules.ecommerce.currency', 'CAD'),
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $currency = (string) config('modules.ecommerce.currency', 'CAD');

        return (new MailMessage)
            ->subject('Confirmation de commande #'.$this->order->order_number)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Nous avons bien reçu votre commande #'.$this->order->order_number.'.')
            ->line('Total : '.number_format((float) $this->order->total, 2).' '.$currency)
            ->action('Voir ma commande', config('app.url').'/account/orders/'.$this->order->id)
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
