<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ShippingMethod;
use Modules\Ecommerce\Models\ShippingZone;

class ShippingService
{
    public function calculateShipping(Cart $cart, ?string $method = null, ?string $province = null): float
    {
        $subtotal = $this->getCartSubtotal($cart);

        // Zone-based shipping if province provided and zones exist
        if ($province !== null) {
            $zoneMethod = $this->findZoneMethod($province, $subtotal);
            if ($zoneMethod) {
                return $this->calculateMethodCost($zoneMethod, $cart, $subtotal);
            }
        }

        // Fallback to config-based logic
        $method ??= (string) config('modules.ecommerce.shipping.default_method', 'flat_rate');

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
    public function getAvailableMethods(Cart $cart, ?string $province = null): array
    {
        $subtotal = $this->getCartSubtotal($cart);

        // Zone-based methods
        if ($province !== null) {
            $zone = ShippingZone::forProvince($province)->active()->first();
            if ($zone) {
                return $zone->methods()
                    ->active()
                    ->where(fn ($q) => $q->whereNull('min_order')->orWhere('min_order', '<=', $subtotal))
                    ->where(fn ($q) => $q->whereNull('max_order')->orWhere('max_order', '>=', $subtotal))
                    ->orderBy('sort_order')
                    ->get()
                    ->map(fn (ShippingMethod $m) => [
                        'key' => $m->type,
                        'label' => $m->name,
                        'cost' => $this->calculateMethodCost($m, $cart, $subtotal),
                    ])
                    ->values()
                    ->all();
            }
        }

        // Fallback to config
        $freeThreshold = (float) config('modules.ecommerce.shipping.free_threshold', 0);

        $methods = [
            ['key' => 'flat_rate', 'label' => 'Livraison standard', 'cost' => (float) config('modules.ecommerce.shipping.flat_rate', 0)],
        ];

        if ($subtotal >= $freeThreshold) {
            $methods[] = ['key' => 'free', 'label' => 'Livraison gratuite', 'cost' => 0.0];
        }

        return $methods;
    }

    private function findZoneMethod(string $province, float $subtotal): ?ShippingMethod
    {
        $zone = ShippingZone::forProvince($province)->active()->first();
        if (! $zone) {
            return null;
        }

        return $zone->methods()
            ->active()
            ->where(fn ($q) => $q->whereNull('min_order')->orWhere('min_order', '<=', $subtotal))
            ->where(fn ($q) => $q->whereNull('max_order')->orWhere('max_order', '>=', $subtotal))
            ->orderBy('sort_order')
            ->first();
    }

    private function calculateMethodCost(ShippingMethod $method, Cart $cart, float $subtotal): float
    {
        return match ($method->type) {
            'flat_rate' => $method->cost,
            'free' => 0.0,
            'per_weight' => $method->cost * $this->getCartWeight($cart),
            'percentage' => round($subtotal * ($method->cost / 100), 2),
            default => 0.0,
        };
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
