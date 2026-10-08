<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Services;

use Modules\Shop\Models\Product;

/**
 * Résolveur UNIQUE couleur + taille -> (libellé canonique, productUid, prix) d'un produit.
 *
 * Source de vérité = les variantes synchronisées du produit (jamais ce que le navigateur prétend) :
 * un libellé « Blanc - 2XL » est décomposé, la couleur est retrouvée PAR SON LIBELLÉ, l'identifiant Gelato
 * vient de product_uids[taille] et le prix de size_prices[taille] de CETTE couleur.
 * Tolère un libellé déjà doublé par un ancien panier (« Blanc - 2XL - 2XL ») et le ramène à sa forme canonique.
 * Renvoie null quand la combinaison est inexistante, sans identifiant SERVEUR valide ou sans prix (jamais de repli sur le prix de base
ni sur l'identifiant d'une autre taille proposé par le navigateur).
 */
class VariantResolver
{
    /** Produit « design personnalisé » : libellé/identifiant fixés par l'éditeur, pas de variante à résoudre. */
    public function isPassThrough(Product $product): bool
    {
        return ! empty($product->metadata['custom_design'] ?? null);
    }

    /**
     * @return array{label: ?string, gelato_uid: ?string, price: float}|null
     */
    public function resolve(Product $product, ?string $label, ?string $clientUid = null): ?array
    {
        $variants = array_values(array_filter((array) ($product->variants ?? []), 'is_array'));

        if ($this->isPassThrough($product)) {
            return ['label' => $label, 'gelato_uid' => $clientUid, 'price' => (float) $product->price];
        }

        if ($variants === []) {
            // Un produit CATALOGUE sans variante synchronisée n'a aucun identifiant serveur : une commande ne pourrait se fonder
            // que sur ce que le navigateur prétend, au prix de base. Refus net (jamais de repli).
            if ($this->isCatalog($product)) {
                return null;
            }

            return ['label' => $label, 'gelato_uid' => $clientUid, 'price' => (float) $product->price];
        }

        $label = $label === null ? '' : trim($label);
        if ($label === '') {
            return null; // le produit a des variantes : une variante est obligatoire
        }

        return $this->hasColors($variants)
            ? $this->resolveColored($product, $variants, $label)
            : $this->resolveSizeOnly($product, $variants, $label);
    }

    private function hasColors(array $variants): bool
    {
        foreach ($variants as $v) {
            if (! empty($v['product_uids']) || ! empty($v['color_slug']) || ! empty($v['color'])) {
                return true;
            }
        }

        return false;
    }

    private function resolveColored(Product $product, array $variants, string $label): ?array
    {
        foreach ($variants as $v) {
            $color = (string) ($v['label'] ?? '');
            if ($color === '' || ($label !== $color && ! str_starts_with($label, $color.' - '))) {
                continue;
            }

            $sizes = array_keys((array) ($v['size_prices'] ?? []));
            $rest = $label === $color ? [] : array_map('trim', explode(' - ', substr($label, strlen($color) + 3)));
            // Taille(s) restante(s) : une seule valeur, éventuellement répétée par un ancien libellé doublé.
            if (count(array_unique($rest)) > 1) {
                continue;
            }
            $size = $rest[0] ?? (count($sizes) === 1 ? (string) $sizes[0] : '');
            if ($size === '' || ! isset($v['size_prices'][$size])) {
                continue;
            }

            $price = (float) $v['size_prices'][$size];
            $uid = (string) ($v['product_uids'][$size] ?? '');
            if ($price <= 0 || $uid === '') {
                return null;
            }
            // Produit catalogue : seul un identifiant présent dans le mapping store est commandable.
            if ($this->isCatalog($product) && empty(($product->metadata['store_variant_map'] ?? [])[$uid])) {
                return null;
            }

            return ['label' => $color.' - '.$size, 'gelato_uid' => $uid, 'price' => $price];
        }

        return null;
    }

    private function resolveSizeOnly(Product $product, array $variants, string $label): ?array
    {
        foreach ($variants as $v) {
            if (($v['label'] ?? null) !== $label) {
                continue;
            }
            // L'identifiant vient UNIQUEMENT du serveur : jamais celui du navigateur (qui pourrait viser une autre taille).
            $uid = (string) ($v['gelato_uid'] ?? '');
            $price = (float) ($v['size_prices'][$label] ?? $product->price);
            if ($uid === '' || $price <= 0) {
                return null;
            }
            if ($this->isCatalog($product) && empty(($product->metadata['store_variant_map'] ?? [])[$uid])) {
                return null;
            }

            return ['label' => $label, 'gelato_uid' => $uid, 'price' => $price];
        }

        return null;
    }

    private function isCatalog(Product $product): bool
    {
        return ! empty($product->metadata['gelato_store_product_id'] ?? null);
    }
}
