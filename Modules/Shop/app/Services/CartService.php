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

    /**
     * @throws \InvalidArgumentException combinaison couleur/taille inexistante ou produit non vendable
     */
    public function add(int $productId, int $qty = 1, ?string $variantLabel = null, ?string $gelatoVariantId = null): Cart
    {
        $product = Product::findOrFail($productId);
        $resolver = $this->resolver();

        if (! $resolver->isPassThrough($product) && $product->status !== 'published') {
            throw new \InvalidArgumentException('Produit non disponible.');
        }

        // Libellé canonique, identifiant Gelato et prix viennent TOUJOURS du serveur (variantes synchronisées).
        $resolved = $resolver->resolve($product, $variantLabel, $gelatoVariantId);
        if ($resolved === null) {
            throw new \InvalidArgumentException('Variante inexistante ou indisponible.');
        }
        $variantLabel = $resolved['label'];
        $gelatoVariantId = $resolved['gelato_uid'];

        $cart = $this->getOrCreateCart();
        $items = $cart->items ?? [];
        $index = $this->findItemIndex($items, $productId, $variantLabel);

        if ($index !== false) {
            $items[$index]['quantity'] += $qty;
        } else {
            $items[] = [
                'product_id' => $productId,
                'variant_label' => $variantLabel,
                'gelato_variant_id' => $gelatoVariantId,
                'quantity' => $qty,
                'unit_price' => $resolved['price'],
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
                'product_variants' => $product?->variants ?? [],
                'product_sizes' => $product?->metadata['sizes'] ?? [],
                'product_category' => $product?->category,
            ]);
        }, $cart->items);
    }

    public function getSubtotal(): float
    {
        $cart = $this->getCart();

        if (! $cart || empty($cart->items)) {
            return 0.0;
        }

        return $this->subtotalOf($cart->items);
    }

    /** Sous-total d'une liste d'articles DONNÉE (instantané) : le checkout facture ce calcul, jamais une relecture du panier. */
    public function subtotalOf(array $items): float
    {
        return array_reduce($items, fn ($total, $item) => $total + ($item['unit_price'] * $item['quantity']), 0.0);
    }

    /** Taxe sur une base DONNÉE (produit + livraison + manutention) pour une province; Québec par défaut (estimation du panier). */
    public function taxAmountOf(float $taxableBase, string $province = 'QC', string $country = 'CA'): float
    {
        return app(TaxCalculator::class)->compute($country, $province, $taxableBase)['total'];
    }

    /** TPS seule (composante fédérale) sur une base donnée. */
    public function tpsOf(float $subtotal): float
    {
        return round($subtotal * config('shop.tax.tps', 0) / 100, 2);
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmountOf($this->getSubtotal());
    }

    public function getTpsOnly(): float
    {
        return $this->tpsOf($this->getSubtotal());
    }

    public function getTotal(): float
    {
        return round($this->getSubtotal() + $this->getTaxAmount(), 2);
    }

    /**
     * Ré-aligne chaque article sur le catalogue courant : libellé canonique (couleur + taille), identifiant Gelato
     * de CETTE couleur et de CETTE taille, prix. Un article dont la variante n'existe plus est laissé tel quel
     * (le contrôle de disponibilité du checkout le refusera : jamais de repli sur le prix de base).
     */
    public function revalidatePrices(): bool
    {
        $cart = $this->getCart();
        if (! $cart || empty($cart->items)) {
            return false;
        }

        $changed = false;
        $items = $cart->items;

        foreach ($items as &$item) {
            $product = Product::find($item['product_id'] ?? 0);
            if (! $product) {
                continue;
            }

            $resolved = $this->resolver()->resolve($product, $item['variant_label'] ?? null, $item['gelato_variant_id'] ?? null);
            if ($resolved === null) {
                continue;
            }

            if (abs($resolved['price'] - ($item['unit_price'] ?? 0)) > 0.01) {
                $item['unit_price'] = $resolved['price'];
                $changed = true;
            }
            if (($item['variant_label'] ?? null) !== $resolved['label'] || ($item['gelato_variant_id'] ?? null) !== $resolved['gelato_uid']) {
                $item['variant_label'] = $resolved['label'];
                $item['gelato_variant_id'] = $resolved['gelato_uid'];
                $changed = true;
            }
        }
        unset($item);

        if ($changed) {
            $cart->update(['items' => $items]);
        }

        return $changed;
    }

    /**
     * Raison pour laquelle un article du panier ne peut PAS être vendu maintenant, ou null s'il l'est.
     * Produit supprimé/dépublié (y compris retiré côté Gelato par la synchro) ou variante inexistante.
     */
    public function unavailableReason(array $item): ?string
    {
        $product = Product::find($item['product_id'] ?? 0);
        if (! $product) {
            return 'produit_introuvable';
        }
        if ($this->resolver()->isPassThrough($product)) {
            return null; // design personnalisé : brouillon par construction, contrôlé par le print file
        }
        if ($product->status !== 'published') {
            return 'produit_non_publie';
        }
        if ($this->resolver()->resolve($product, $item['variant_label'] ?? null, $item['gelato_variant_id'] ?? null) === null) {
            return 'variante_indisponible';
        }

        return null;
    }

    public function clear(): void
    {
        $cart = $this->getCart();
        $cart?->delete();
    }

    public function syncWithUser(int $userId): void
    {
        $this->syncSessionCart(session()->getId(), $userId);
    }

    /**
     * Synchronise le panier d'une session (avant login) vers un utilisateur.
     * Appelé avec l'ancien session_id capturé AVANT la régénération de session.
     */
    public function syncSessionCart(string $oldSessionId, int $userId): void
    {
        $sessionCart = Cart::bySession($oldSessionId)->active()->first();

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

    /**
     * Change la variante (couleur et/ou taille) d'un article : la variante COMPLÈTE est ré-résolue côté serveur
     * (identifiant Gelato de la bonne couleur ET de la bonne taille, prix), jamais l'identifiant transmis par le navigateur.
     *
     * @return array l'article tel qu'enregistré (libellé canonique, identifiant, prix)
     * @throws \InvalidArgumentException combinaison inexistante ou article introuvable (jamais un faux succès)
     */
    public function updateItemVariant(int $productId, string $oldLabel, string $newLabel, ?string $newGelatoUid = null): array
    {
        $cart = $this->getCart();
        $items = $cart?->items ?? [];
        $index = $this->findItemIndex($items, $productId, $oldLabel);

        if ($index === false) {
            throw new \InvalidArgumentException('Article introuvable dans le panier.');
        }

        $product = Product::findOrFail($productId);
        $resolved = $this->resolver()->resolve($product, $newLabel, $newGelatoUid);
        if ($resolved === null) {
            throw new \InvalidArgumentException('Variante inexistante ou indisponible.');
        }
        $items[$index]['variant_label'] = $resolved['label'];
        $items[$index]['gelato_variant_id'] = $resolved['gelato_uid'];
        $items[$index]['unit_price'] = $resolved['price'];
        $cart->update(['items' => $items]);

        return $items[$index];
    }

    private function resolver(): VariantResolver
    {
        return app(VariantResolver::class);
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
