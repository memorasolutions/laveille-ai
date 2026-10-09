<?php

declare(strict_types=1);

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\Product;

/**
 * Garde-fou de marge plancher, exécuté AVANT l'encaissement (le webhook serait trop tard : les frais Stripe sont déjà engagés).
 *
 * Résultat net = (1 - frais_pct) × total - frais_fixe - taxe_à_remettre - C
 * où C = coût d'impression courant (cost_base USD × taux de prix courant × quantité) + coût RÉEL d'expédition Gelato.
 * Le prix de livraison du devis inclut déjà la manutention (`shop.handling_fee`), qui est une recette, pas un coût :
 * on la retire pour retrouver le coût transporteur. Commande bloquée si net < `pricing.floor_margin_cad`.
 *
 * Coût par VARIANTE : `metadata.cost_by_uid` (rempli par la synchro Gelato). Produit synchronisé avant ce champ : repli
 * `cost_base` (taille de référence) jusqu'à la prochaine synchro forcée.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
final class MarginGuard
{
    /**
     * Coût unitaire USD de la variante commandée : `metadata.cost_by_uid[uid]`. Variante inconnue de la table (ancien
     * produit, uid non reconnu) = MAX des coûts connus du produit (jamais la taille de référence seule, qui sous-estime).
     * Sans aucune table, repli sur `cost_base`. Aucun coût valide = null (commande bloquée).
     */
    private function unitCostUsd(?Product $product, string $uid): ?float
    {
        $meta = (array) ($product?->metadata ?? []);
        $byUid = array_filter((array) ($meta['cost_by_uid'] ?? []), fn ($c) => is_numeric($c) && (float) $c > 0);

        if ($byUid !== []) {
            return (float) ($byUid[$uid] ?? max($byUid));
        }

        $base = (float) ($meta['cost_base'] ?? 0);

        return $base > 0 ? $base : null;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $cartItems  instantané du panier (product_id, quantity)
     * @return array{ok: bool, net: float, cost: float, reason: string|null}
     */
    public function evaluate(array $cartItems, float $total, float $taxAmount, float $shippingCost): array
    {
        $rate = ExchangeRateService::rate();
        $cost = 0.0;

        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id'] ?? 0);
            $unitCost = $this->unitCostUsd($product, (string) ($item['gelato_variant_id'] ?? ''));
            $qty = max(1, (int) ($item['quantity'] ?? 1));

            // Coût inconnu = commande TOUJOURS bloquée : un article facturé mais exclu du coût serait une perte acceptée.
            // Le drapeau `floor_block_unknown_cost` ne change que le MESSAGE de l'incident, jamais la décision.
            if ($unitCost === null) {
                $why = config('shop.pricing.floor_block_unknown_cost', true) ? 'coût inconnu' : 'coût inconnu (drapeau floor_block_unknown_cost ignoré : jamais de vente sans coût)';
                Log::warning('Shop MarginGuard : commande refusée, '.$why, ['product_id' => $item['product_id'] ?? null]);

                return ['ok' => false, 'net' => 0.0, 'cost' => 0.0, 'reason' => $why.' produit #'.($item['product_id'] ?? '?')];
            }

            $cost += $unitCost * $rate * $qty;
        }

        $carrier = max(0.0, $shippingCost - (float) config('shop.handling_fee', 0));
        $cost += $carrier;

        $feePct = (float) config('shop.pricing.stripe_fee_pct', 0.029);
        $feeFixed = (float) config('shop.pricing.stripe_fee_fixed', 0.30);
        $floor = (float) config('shop.pricing.floor_margin_cad', 0.0);

        // Comparaison sur le net NON arrondi : un net de -0,004 $ ne doit jamais passer comme « 0,00 >= 0,00 ».
        $rawNet = (1 - $feePct) * $total - $feeFixed - $taxAmount - $cost;
        $ok = $rawNet >= $floor;
        $net = round($rawNet, 2); // affichage et journal seulement

        if (! $ok) {
            Log::warning('Shop MarginGuard : commande refusée, marge insuffisante', [
                'net' => $net, 'floor' => $floor, 'cost' => round($cost, 2), 'total' => $total, 'tax' => $taxAmount, 'shipping' => $shippingCost, 'rate' => $rate,
            ]);
        }

        return ['ok' => $ok, 'net' => $net, 'cost' => round($cost, 2), 'reason' => $ok ? null : 'marge nette '.number_format($net, 2).' $ sous le plancher '.number_format($floor, 2).' $'];
    }
}
