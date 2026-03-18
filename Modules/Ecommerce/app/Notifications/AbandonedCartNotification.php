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
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;

class AbandonedCartNotification extends TemplatedNotification
{
    public function __construct(
        public Cart $cart,
        public int $reminderNumber,
    ) {}

    protected function getTemplateSlug(): string
    {
        return 'ecommerce_abandoned_cart';
    }

    protected function getTemplateData(object $notifiable): array
    {
        $itemNames = $this->cart->items->take(3)->map(
            fn (CartItem $item) => $item->variant->product->name ?? 'Article'
        )->implode(', ');

        $cartTotal = $this->cart->items->sum(
            fn (CartItem $i) => (float) $i->variant->price * $i->quantity
        );

        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'cart' => [
                'items' => $itemNames,
                'item_count' => (string) $this->cart->items->count(),
                'total' => number_format($cartTotal, 2),
                'url' => url((string) config('modules.ecommerce.abandoned_cart.recover_url', '/cart')),
            ],
            'currency' => (string) config('modules.ecommerce.currency', 'CAD'),
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $subjects = [
            1 => __('Vous avez oublié quelque chose !'),
            2 => __('Votre panier vous attend'),
            3 => __('Dernière chance - 10% de réduction'),
        ];

        $recoverUrl = url((string) config('modules.ecommerce.abandoned_cart.recover_url', '/cart'));

        $mail = (new MailMessage)
            ->subject($subjects[$this->reminderNumber] ?? __('Votre panier abandonné'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Vous avez laissé des articles dans votre panier.'));

        foreach ($this->cart->items->take(3) as $item) {
            /** @var CartItem $item */
            $name = $item->variant->product->name ?? 'Article';
            $mail->line("- {$name} (x{$item->quantity})");
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
