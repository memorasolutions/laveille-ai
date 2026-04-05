<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Shop\Models\Cart;
use Modules\Shop\Models\Product;

class CartService
{
    public function getCart(): ?Cart
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id())->active()->first();
        }

        return Cart::bySession(session()->getId())->active()->first();
    }

    public function getOrCreateCart(): Cart
    {
        $cart = $this->getCart();

        if ($cart) {
            return $cart;
        }

        return Cart::create([
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
            'items' => [],
            'expires_at' => now()->addHours(config('shop.cart.expiry_hours', 72)),
        ]);
    }

    public function add(int $productId, int $qty = 1, ?string $variantLabel = null): Cart
    {
        $cart = $this->getOrCreateCart();
        $items = $cart->items ?? [];
        $index = $this->findItemIndex($items, $productId, $variantLabel);

        if ($index !== false) {
            $items[$index]['quantity'] += $qty;
        } else {
            $product = Product::findOrFail($productId);
            $items[] = [
                'product_id' => $productId,
                'variant_label' => $variantLabel,
                'quantity' => $qty,
                'unit_price' => (float) $product->price,
            ];
        }

        $cart->update(['items' => $items]);

        return $cart;
    }

    public function remove(int $productId, ?string $variantLabel = null): Cart
    {
        $cart = $this->getCart();

        if (! $cart) {
            return new Cart(['items' => []]);
        }

        $items = $cart->items ?? [];
        $index = $this->findItemIndex($items, $productId, $variantLabel);

        if ($index !== false) {
            unset($items[$index]);
            $cart->update(['items' => array_values($items)]);
        }

        return $cart;
    }

    public function updateQuantity(int $productId, int $qty, ?string $variantLabel = null): Cart
    {
        $cart = $this->getCart();

        if (! $cart) {
            return new Cart(['items' => []]);
        }

        $items = $cart->items ?? [];
        $index = $this->findItemIndex($items, $productId, $variantLabel);

        if ($index !== false) {
            if ($qty <= 0) {
                unset($items[$index]);
                $items = array_values($items);
            } else {
                $items[$index]['quantity'] = $qty;
            }
            $cart->update(['items' => $items]);
        }

        return $cart;
    }

    public function getContent(): array
    {
        $cart = $this->getCart();

        if (! $cart || empty($cart->items)) {
            return [];
        }

        $productIds = array_column($cart->items, 'product_id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return array_map(function ($item) use ($products) {
            $product = $products[$item['product_id']] ?? null;

            return array_merge($item, [
                'product_name' => $product?->name ?? 'Produit supprimé',
                'product_slug' => $product?->slug,
                'product_images' => $product?->images ?? [],
            ]);
        }, $cart->items);
    }

    public function getSubtotal(): float
    {
        $cart = $this->getCart();

        if (! $cart || empty($cart->items)) {
            return 0.0;
        }

        return array_reduce($cart->items, function ($total, $item) {
            return $total + ($item['unit_price'] * $item['quantity']);
        }, 0.0);
    }

    public function getTaxAmount(): float
    {
        $subtotal = $this->getSubtotal();
        $tps = config('shop.tax.tps', 0);
        $tvq = config('shop.tax.tvq', 0);

        return round($subtotal * ($tps + $tvq) / 100, 2);
    }

    public function getTotal(): float
    {
        return round($this->getSubtotal() + $this->getTaxAmount(), 2);
    }

    public function clear(): void
    {
        $cart = $this->getCart();
        $cart?->delete();
    }

    public function syncWithUser(int $userId): void
    {
        $sessionCart = Cart::bySession(session()->getId())->active()->first();

        if (! $sessionCart) {
            return;
        }

        $userCart = Cart::where('user_id', $userId)->active()->first();

        if ($userCart) {
            // Fusionner les items session dans le panier user
            $mergedItems = $userCart->items ?? [];
            foreach ($sessionCart->items ?? [] as $sessionItem) {
                $index = $this->findItemIndex($mergedItems, $sessionItem['product_id'], $sessionItem['variant_label'] ?? null);
                if ($index !== false) {
                    $mergedItems[$index]['quantity'] += $sessionItem['quantity'];
                } else {
                    $mergedItems[] = $sessionItem;
                }
            }
            $userCart->update(['items' => $mergedItems]);
            $sessionCart->delete();
        } else {
            $sessionCart->update(['user_id' => $userId, 'session_id' => session()->getId()]);
        }
    }

    public function itemCount(): int
    {
        $cart = $this->getCart();

        if (! $cart || empty($cart->items)) {
            return 0;
        }

        return array_reduce($cart->items, fn ($total, $item) => $total + $item['quantity'], 0);
    }

    private function findItemIndex(array $items, int $productId, ?string $variantLabel): int|false
    {
        foreach ($items as $index => $item) {
            if ($item['product_id'] === $productId && ($item['variant_label'] ?? null) === $variantLabel) {
                return $index;
            }
        }

        return false;
    }
}
