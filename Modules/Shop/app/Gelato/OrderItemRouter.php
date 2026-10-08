<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

/**
 * Résolveur UNIQUE du payload Gelato d'un article (source de vérité du routage, par produit).
 *
 * - CATALOGUE (metadata.gelato_store_product_id non vide) : storeProductVariantId via metadata.store_variant_map.
 *   Jamais de fichier : Gelato imprime le design du store.
 * - ÉDITEUR (sinon) : productUid + fichier d'impression APPROVED (PrintFileService). Jamais metadata.print_file_url.
 */
class OrderItemRouter
{
    public function __construct(private PrintFileService $printFiles) {}

    public static function isCatalog(?object $product): bool
    {
        return ! empty(($product->metadata ?? [])['gelato_store_product_id'] ?? null);
    }

    /**
     * @param object $item OrderItem (id, product_id, product, gelato_variant_id, quantity)
     * @return array<string,mixed>
     * @throws GelatoRoutingException        produit/variante inexploitable ou catalogue sans mapping
     * @throws PrintFileNotApprovedException éditeur sans fichier approuvé
     */
    public function resolvePayload(object $item): array
    {
        $product = $item->product ?? null;
        $variant = $item->gelato_variant_id ?? null;

        // Identifiant FIGÉ à la commande (checkout) : il PRIME sur la relation vivante (produit archivé/modifié depuis)
        // et sur le mapping courant. Un article déjà encaissé n'est jamais refusé parce que le catalogue a bougé.
        $frozen = $item->gelato_store_product_variant_id ?? null;
        if (! empty($frozen)) {
            return [
                'itemReferenceId' => (string) $item->id,
                'storeProductVariantId' => $frozen,
                'quantity' => $item->quantity,
            ];
        }

        if (! $product) {
            throw new GelatoRoutingException("Article #{$item->id} : produit_introuvable.");
        }
        if (empty($variant)) {
            throw new GelatoRoutingException("Article #{$item->id} : variant_id_absent (identifiant de variante Gelato manquant).");
        }

        if (self::isCatalog($product)) {
            // Marqueur incohérent (colonne ≠ métadonnée) : le mapping ne décrit pas forcément le produit Gelato visé. Refus net.
            $column = $product->gelato_store_product_id ?? null;
            if (! empty($column) && (string) $column !== (string) $product->metadata['gelato_store_product_id']) {
                throw new GelatoRoutingException("Produit catalogue au marqueur Gelato incohérent (colonne {$column} ≠ métadonnée {$product->metadata['gelato_store_product_id']}) : article #{$item->id} non routable.");
            }
            $storeVariantId = ($product->metadata['store_variant_map'] ?? [])[$variant] ?? null;
            if (empty($storeVariantId)) {
                throw new GelatoRoutingException("Produit catalogue sans storeProductVariantId pour la variante {$variant} (article #{$item->id}).");
            }

            return [
                'itemReferenceId' => (string) $item->id,
                'storeProductVariantId' => $storeVariantId,
                'quantity' => $item->quantity,
            ];
        }

        $url = $this->printFiles->assertOrderable((int) ($item->product_id ?? $product->id), $variant, $variant)->public_url;

        return [
            'itemReferenceId' => (string) $item->id,
            'productUid' => $variant,
            'quantity' => $item->quantity,
            'files' => [['type' => 'default', 'url' => $url]],
        ];
    }

    /** Identifiant storeProductVariantId à figer sur l'article (null pour un produit éditeur). Lève si non routable. */
    public function frozenStoreVariantId(object $item): ?string
    {
        return $this->resolvePayload($item)['storeProductVariantId'] ?? null;
    }

    /** Raison d'invalidité, ou null si l'article est commandable. Ne lève jamais. */
    public function validate(object $item): ?string
    {
        try {
            $this->resolvePayload($item);

            return null;
        } catch (GelatoRoutingException|PrintFileNotApprovedException $e) {
            return $e->getMessage();
        }
    }
}
