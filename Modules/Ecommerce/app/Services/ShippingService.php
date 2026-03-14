<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;

class ShippingService
{
    public function calculateShipping(Cart $cart, ?string $method = null): float
    {
        $method ??= (string) config('modules.ecommerce.shipping.default_method', 'flat_rate');
        $subtotal = $this->getCartSubtotal($cart);

        if ($subtotal >= (float) config('modules.ecommerce.shipping.free_threshold', 0)) {
            return 0.0;
        }

        return match ($method) {
            'flat_rate' => (float) config('modules.ecommerce.shipping.flat_rate', 0),
            'per_weight' => $this->getCartWeight($cart) * (float) config('modules.ecommerce.shipping.per_kg_rate', 0),
            'free' => 0.0,
            default => 0.0,
        };
    }

    /**
     * @return array<int, array{key: string, label: string, cost: float}>
     */
    public function getAvailableMethods(Cart $cart): array
    {
        $subtotal = $this->getCartSubtotal($cart);
        $freeThreshold = (float) config('modules.ecommerce.shipping.free_threshold', 0);

        $methods = [
            ['key' => 'flat_rate', 'label' => 'Livraison standard', 'cost' => (float) config('modules.ecommerce.shipping.flat_rate', 0)],
        ];

        if ($subtotal >= $freeThreshold) {
            $methods[] = ['key' => 'free', 'label' => 'Livraison gratuite', 'cost' => 0.0];
        }

        return $methods;
    }

    private function getCartSubtotal(Cart $cart): float
    {
        return (float) $cart->items->sum(fn (CartItem $i) => $i->variant->price * $i->quantity);
    }

    private function getCartWeight(Cart $cart): float
    {
        return (float) $cart->items->sum(fn (CartItem $i) => ($i->variant->weight ?? 0) * $i->quantity);
    }
}
