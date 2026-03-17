<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Promotion;

class PromotionService
{
    /**
     * Apply all valid automatic promotions to a cart and return total discount.
     */
    public function applyToCart(Cart $cart): float
    {
        $promotions = Promotion::valid()
            ->automatic()
            ->orderBy('priority', 'desc')
            ->get();

        $subtotal = $this->cartSubtotal($cart);
        $totalQty = $this->cartQuantity($cart);
        $totalDiscount = 0.0;
        $hasNonStackable = false;

        foreach ($promotions as $promo) {
            if (! $promo->meetsConditions($subtotal, $totalQty)) {
                continue;
            }

            if ($hasNonStackable && ! $promo->is_stackable) {
                continue;
            }

            $discount = $this->calculateDiscount($promo, $cart, $subtotal);

            if ($discount > 0) {
                $totalDiscount += $discount;

                if (! $promo->is_stackable) {
                    $hasNonStackable = true;
                }
            }
        }

        return round(min($totalDiscount, $subtotal), 2);
    }

    /**
     * Get the best single promotion for display purposes.
     */
    public function bestPromotion(Cart $cart): ?Promotion
    {
        $promotions = Promotion::valid()
            ->automatic()
            ->orderBy('priority', 'desc')
            ->get();

        $subtotal = $this->cartSubtotal($cart);
        $totalQty = $this->cartQuantity($cart);
        $best = null;
        $bestDiscount = 0.0;

        foreach ($promotions as $promo) {
            if (! $promo->meetsConditions($subtotal, $totalQty)) {
                continue;
            }

            $discount = $this->calculateDiscount($promo, $cart, $subtotal);

            if ($discount > $bestDiscount) {
                $bestDiscount = $discount;
                $best = $promo;
            }
        }

        return $best;
    }

    /**
     * Calculate discount for a single promotion against a cart.
     */
    public function calculateDiscount(Promotion $promo, Cart $cart, ?float $subtotal = null): float
    {
        $subtotal ??= $this->cartSubtotal($cart);

        $applicableSubtotal = $this->applicableSubtotal($promo, $cart);

        if ($applicableSubtotal <= 0) {
            return 0.0;
        }

        return match ($promo->type) {
            Promotion::TYPE_PERCENTAGE => round($applicableSubtotal * (float) $promo->value / 100, 2),
            Promotion::TYPE_FIXED => round(min((float) $promo->value, $applicableSubtotal), 2),
            Promotion::TYPE_BOGO => $this->calculateBogo($promo, $cart),
            Promotion::TYPE_FREE_SHIPPING => 0.0,
            Promotion::TYPE_TIERED => $this->calculateTiered($promo, $applicableSubtotal),
            default => 0.0,
        };
    }

    /**
     * Check if a promotion grants free shipping.
     */
    public function grantsFreeShipping(Cart $cart): bool
    {
        $subtotal = $this->cartSubtotal($cart);
        $totalQty = $this->cartQuantity($cart);

        return Promotion::valid()
            ->automatic()
            ->where('type', Promotion::TYPE_FREE_SHIPPING)
            ->get()
            ->contains(fn (Promotion $p) => $p->meetsConditions($subtotal, $totalQty));
    }

    /**
     * Calculate BOGO discount from cart items.
     */
    private function calculateBogo(Promotion $promo, Cart $cart): float
    {
        $config = $promo->bogo_config ?? [];
        $buyQty = (int) ($config['buy_qty'] ?? 1);
        $getQty = (int) ($config['get_qty'] ?? 1);
        $getDiscount = (float) ($config['get_discount'] ?? 100);

        $discount = 0.0;

        foreach ($cart->items as $item) {
            /** @var CartItem $item */
            if (! $this->itemApplies($promo, $item)) {
                continue;
            }

            $sets = intdiv($item->quantity, $buyQty + $getQty);
            $freeItems = $sets * $getQty;
            $unitPrice = (float) $item->variant->price;
            $discount += $freeItems * $unitPrice * ($getDiscount / 100);
        }

        return round($discount, 2);
    }

    /**
     * Calculate tiered pricing discount.
     */
    private function calculateTiered(Promotion $promo, float $subtotal): float
    {
        $tiers = $promo->tiers ?? [];

        usort($tiers, fn ($a, $b) => (float) ($b['min_amount'] ?? 0) <=> (float) ($a['min_amount'] ?? 0));

        foreach ($tiers as $tier) {
            $minAmount = (float) ($tier['min_amount'] ?? 0);
            $discountPercent = (float) ($tier['discount_percent'] ?? 0);

            if ($subtotal >= $minAmount) {
                return round($subtotal * $discountPercent / 100, 2);
            }
        }

        return 0.0;
    }

    /**
     * Calculate subtotal of cart items that apply to this promotion.
     */
    private function applicableSubtotal(Promotion $promo, Cart $cart): float
    {
        if ($promo->applies_to === Promotion::APPLIES_ALL) {
            return $this->cartSubtotal($cart);
        }

        $total = 0.0;

        foreach ($cart->items as $item) {
            /** @var CartItem $item */
            if ($this->itemApplies($promo, $item)) {
                $total += (float) $item->variant->price * $item->quantity;
            }
        }

        return $total;
    }

    /**
     * Check if a cart item is targeted by the promotion.
     */
    private function itemApplies(Promotion $promo, CartItem $item): bool
    {
        if ($promo->applies_to === Promotion::APPLIES_ALL) {
            return true;
        }

        if ($promo->applies_to === Promotion::APPLIES_PRODUCTS) {
            $productId = $item->variant->product_id ?? $item->variant->product?->id;

            return $promo->appliesToProduct((int) $productId);
        }

        if ($promo->applies_to === Promotion::APPLIES_CATEGORIES) {
            $categoryIds = $item->variant->product?->categories?->pluck('id')?->all() ?? [];

            foreach ($categoryIds as $catId) {
                if ($promo->appliesToCategory((int) $catId)) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    private function cartSubtotal(Cart $cart): float
    {
        return (float) $cart->items->sum(
            fn (CartItem $item) => (float) $item->variant->price * $item->quantity
        );
    }

    private function cartQuantity(Cart $cart): int
    {
        return (int) $cart->items->sum('quantity');
    }
}
