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
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Notifications\Services\EmailTemplateService;

class AbandonedCartNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Cart $cart,
        public int $reminderNumber,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $itemNames = $this->cart->items->take(3)->map(
            fn (CartItem $item) => $item->variant->product->name ?? 'Article'
        )->implode(', ');

        $cartTotal = $this->cart->items->sum(
            fn (CartItem $i) => (float) $i->variant->price * $i->quantity
        );

        $recoverUrl = url((string) config('modules.ecommerce.abandoned_cart.recover_url', '/cart'));

        if (class_exists(EmailTemplateService::class)) {
            $service = app(EmailTemplateService::class);
            $rendered = $service->render('ecommerce_abandoned_cart', [
                'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
                'app' => ['name' => config('app.name'), 'url' => config('app.url')],
                'cart' => [
                    'items' => $itemNames,
                    'item_count' => (string) $this->cart->items->count(),
                    'total' => number_format($cartTotal, 2),
                    'url' => $recoverUrl,
                ],
                'currency' => (string) config('modules.ecommerce.currency', 'CAD'),
            ]);

            if ($rendered) {
                return (new MailMessage)
                    ->subject($rendered['subject'])
                    ->view('notifications::email.html-wrapper', ['content' => $rendered['body_html']]);
            }
        }

        $subjects = [
            1 => __('Vous avez oublie quelque chose !'),
            2 => __('Votre panier vous attend'),
            3 => __('Derniere chance - 10% de reduction'),
        ];

        $mail = (new MailMessage)
            ->subject($subjects[$this->reminderNumber] ?? __('Votre panier abandonne'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Vous avez laisse des articles dans votre panier.'));

        foreach ($this->cart->items->take(3) as $item) {
            /** @var CartItem $item */
            $name = $item->variant->product->name ?? 'Article';
            $mail->line("- {$name} (x{$item->quantity})");
        }

        if ($this->cart->items->count() > 3) {
            $remaining = $this->cart->items->count() - 3;
            $mail->line(__('... et :count autres articles.', ['count' => $remaining]));
        }

        return $mail
            ->action(__('Reprendre mes achats'), $recoverUrl)
            ->line(__('Merci de votre confiance !'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'cart_id' => $this->cart->id,
            'item_count' => $this->cart->items->count(),
            'total' => $this->cart->items->sum(fn (CartItem $i) => (float) $i->variant->price * $i->quantity),
            'reminder_number' => $this->reminderNumber,
        ];
    }
}
