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
use Modules\Ecommerce\Models\ProductVariant;

class LowStockNotification extends TemplatedNotification
{
    public function __construct(
        public ProductVariant $variant
    ) {}

    protected function getTemplateSlug(): string
    {
        return 'ecommerce_low_stock';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'variant' => [
                'sku' => $this->variant->sku,
                'stock' => $this->variant->stock,
                'threshold' => $this->variant->low_stock_threshold,
                'product_name' => $this->variant->product->name,
                'product_id' => $this->variant->product_id,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerte stock bas — {$this->variant->sku}")
            ->line("Le variant {$this->variant->sku} du produit {$this->variant->product->name} a atteint {$this->variant->stock} unites (seuil: {$this->variant->low_stock_threshold}).")
            ->action('Voir le produit', route('admin.ecommerce.products.edit', $this->variant->product_id));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
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
