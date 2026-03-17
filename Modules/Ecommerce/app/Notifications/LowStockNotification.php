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
use Modules\Ecommerce\Models\ProductVariant;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProductVariant $variant
    ) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail', 'database'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerte stock bas — {$this->variant->sku}")
            ->line("Le variant {$this->variant->sku} du produit {$this->variant->product->name} a atteint {$this->variant->stock} unités (seuil: {$this->variant->low_stock_threshold}).")
            ->action('Voir le produit', route('admin.ecommerce.products.edit', $this->variant->product_id));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'stock' => $this->variant->stock,
            'threshold' => $this->variant->low_stock_threshold,
            'product_name' => $this->variant->product->name,
        ];
    }
}
