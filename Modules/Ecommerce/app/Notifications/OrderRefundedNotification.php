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

class OrderRefundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public float $amount,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (class_exists(EmailTemplateService::class)) {
            $service = app(EmailTemplateService::class);
            $rendered = $service->render('ecommerce_order_refunded', [
                'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
                'app' => ['name' => config('app.name'), 'url' => config('app.url')],
                'order' => [
                    'number' => $this->order->order_number,
                    'url' => config('app.url').'/account/orders/'.$this->order->id,
                ],
                'refund' => ['amount' => number_format($this->amount, 2)],
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
            ->subject('Remboursement commande #'.$this->order->order_number)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre remboursement de '.number_format($this->amount, 2).' '.$currency.' pour la commande #'.$this->order->order_number.' a ete traite.')
            ->line('Le montant sera credite sur votre compte dans un delai de 5 a 10 jours ouvrables.')
            ->action('Voir ma commande', $url)
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
