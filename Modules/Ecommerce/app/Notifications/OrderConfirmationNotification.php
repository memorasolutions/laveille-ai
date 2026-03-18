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
use Modules\Notifications\Services\EmailTemplateService;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (class_exists(EmailTemplateService::class)) {
            $service = app(EmailTemplateService::class);
            $rendered = $service->render('ecommerce_order_confirmation', [
                'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
                'app' => ['name' => config('app.name'), 'url' => config('app.url')],
                'order' => [
                    'number' => $this->order->order_number,
                    'total' => number_format((float) $this->order->total, 2),
                    'date' => $this->order->created_at->format('d/m/Y'),
                    'url' => config('app.url').'/account/orders/'.$this->order->id,
                ],
                'currency' => (string) config('modules.ecommerce.currency', 'CAD'),
            ]);

            if ($rendered) {
                return (new MailMessage)
                    ->subject($rendered['subject'])
                    ->view('notifications::email.html-wrapper', ['content' => $rendered['body_html']]);
            }
        }

        $url = config('app.url').'/account/orders/'.$this->order->id;
        $currency = (string) config('modules.ecommerce.currency', 'CAD');

        return (new MailMessage)
            ->subject('Confirmation de commande #'.$this->order->order_number)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Nous avons bien recu votre commande #'.$this->order->order_number.'.')
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
