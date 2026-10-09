<?php

declare(strict_types=1);

namespace Modules\Shop\Services;

/**
 * Taxes de vente par province de LIVRAISON, entièrement pilotées par `shop.tax.*` (aucun taux en dur ici).
 *
 * Base taxable = produit + livraison + manutention (tous taxables quand liés à un bien taxable). Le total est arrondi
 * UNE fois sur le taux combiné; la 1re composante (TPS) est arrondie seule et la 2e prend le reste, de sorte que la
 * somme des lignes affichées égale toujours la taxe facturée au cent près.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
final class TaxCalculator
{
    /** Province connue du barème (`shop.tax.provinces`). Le checkout REFUSE une province CA non résolue plutôt que de sous-facturer. */
    public function isKnownProvince(?string $province): bool
    {
        return array_key_exists(strtoupper(trim((string) $province)), (array) config('shop.tax.provinces', []));
    }

    /**
     * @return array{total: float, label: string, lines: list<array{code: string, label: string, rate: float, amount: float}>}
     */
    public function compute(?string $country, ?string $province, float $taxableBase): array
    {
        $none = ['total' => 0.0, 'label' => '', 'lines' => []];
        $base = max(0.0, $taxableBase);

        if (strtoupper(trim((string) $country)) !== 'CA' || $base <= 0.0) {
            return $none;
        }

        $def = (array) (config('shop.tax.provinces', [])[strtoupper(trim((string) $province))] ?? []);
        $tps = (float) config('shop.tax.tps', 0);
        $tvq = (float) config('shop.tax.tvq', 0);

        if (isset($def['hst']) && (float) $def['hst'] > 0) {
            $rate = (float) $def['hst'];
            $total = round($base * $rate / 100, 2);

            return [
                'total' => $total,
                'label' => 'TVH ('.self::pct($rate).' %)',
                'lines' => [['code' => 'TVH', 'label' => 'TVH', 'rate' => $rate, 'amount' => $total]],
            ];
        }

        if (! empty($def['qst'])) {
            $total = round($base * ($tps + $tvq) / 100, 2);
            $gst = min($total, round($base * $tps / 100, 2));

            return [
                'total' => $total,
                'label' => 'TPS + TVQ',
                'lines' => [
                    ['code' => 'TPS', 'label' => 'TPS', 'rate' => $tps, 'amount' => $gst],
                    ['code' => 'TVQ', 'label' => 'TVQ', 'rate' => $tvq, 'amount' => round($total - $gst, 2)],
                ],
            ];
        }

        $pst = (bool) config('shop.tax.collect_pst', false) ? (float) ($def['pst'] ?? 0) : 0.0;
        if ($pst > 0) {
            $total = round($base * ($tps + $pst) / 100, 2);
            $gst = min($total, round($base * $tps / 100, 2));

            return [
                'total' => $total,
                'label' => 'TPS + TVP',
                'lines' => [
                    ['code' => 'TPS', 'label' => 'TPS', 'rate' => $tps, 'amount' => $gst],
                    ['code' => 'TVP', 'label' => 'TVP', 'rate' => $pst, 'amount' => round($total - $gst, 2)],
                ],
            ];
        }

        $total = round($base * $tps / 100, 2);

        return [
            'total' => $total,
            'label' => 'TPS ('.self::pct($tps).' %)',
            'lines' => [['code' => 'TPS', 'label' => 'TPS', 'rate' => $tps, 'amount' => $total]],
        ];
    }

    private static function pct(float $rate): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($rate, 3, '.', ''), '0'), '.'));
    }
}
