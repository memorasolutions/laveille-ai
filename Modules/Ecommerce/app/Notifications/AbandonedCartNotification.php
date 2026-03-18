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
        $subjects = [
            1 => __('Vous avez oublié quelque chose !'),
            2 => __('Votre panier vous attend'),
            3 => __('Dernière chance — 10% de réduction'),
        ];

        $mail = (new MailMessage)
            ->subject($subjects[$this->reminderNumber] ?? __('Votre panier abandonné'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Vous avez laissé des articles dans votre panier.'));

        foreach ($this->cart->items->take(3) as $item) {
            /** @var CartItem $item */
            $name = $item->variant->product->name ?? 'Article';
            $mail->line("- {$name} (x{$item->quantity})");
        }

        if ($this->cart->items->count() > 3) {
            $remaining = $this->cart->items->count() - 3;
            $mail->line(__('... et :count autres articles.', ['count' => $remaining]));
        }

        $recoverUrl = config('modules.ecommerce.abandoned_cart.recover_url', '/cart');

        return $mail
            ->action(__('Reprendre mes achats'), url($recoverUrl))
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
