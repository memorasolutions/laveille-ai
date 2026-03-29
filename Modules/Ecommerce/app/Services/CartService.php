<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use App\Models\User;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ProductVariant;

class CartService
{
    public function __construct(
        private InventoryService $inventoryService,
    ) {}

    public function getOrCreateCart(?User $user = null): Cart
    {
        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        return Cart::firstOrCreate(['session_id' => session()->getId()]);
    }

    public function addItem(Cart $cart, ProductVariant $variant, int $qty = 1): CartItem
    {
        if (! $this->inventoryService->canFulfill($variant, $qty)) {
            throw new \RuntimeException('Stock insuffisant.');
        }

        /** @var CartItem|null $item */
        $item = $cart->items()->where('variant_id', $variant->id)->first();

        if ($item) {
            $item->quantity += $qty;
            $item->save();

            return $item;
        }

        /** @var CartItem */
        return $cart->items()->create([
            'variant_id' => $variant->id,
            'quantity' => $qty,
        ]);
    }

    public function updateItemQuantity(CartItem $item, int $qty): CartItem
    {
        if (! $this->inventoryService->canFulfill($item->variant, $qty)) {
            throw new \RuntimeException('Stock insuffisant.');
        }

        $item->quantity = $qty;
        $item->save();

        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function getTotal(Cart $cart): float
    {
        return (float) $cart->items->sum(fn (CartItem $i) => $i->variant->price * $i->quantity);
    }

    public function getItemCount(Cart $cart): int
    {
        return (int) $cart->items->sum('quantity');
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    public function mergeSessionCart(Cart $sessionCart, User $user): Cart
    {
        $userCart = $this->getOrCreateCart($user);

        foreach ($sessionCart->items as $item) {
            $this->addItem($userCart, $item->variant, $item->quantity);
        }

        $sessionCart->items()->delete();

        return $userCart;
    }
}
