<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * Taux de PRIX = max(taux vivant, plancher). Si l'API donne un taux plus BAS que le plancher (CAD fort : moins de
     * CAD par USD), le prix de vente baisserait alors que le coût d'impression reste payé en USD : la marge s'éroderait.
     * Le plancher (`shop.pricing.usd_cad_rate_floor`) empêche de sous-tarifer; un taux plus haut est suivi tel quel.
     */
    public function getUsdToCad(): float
    {
        return max($this->liveRate(), (float) config('shop.pricing.usd_cad_rate_floor', 1.40));
    }

    private function liveRate(): float
    {
        $cached = Cache::get('exchange_rate_usd_cad');
        if ($cached !== null && (float) $cached > 0) {
            return (float) $cached;
        }

        try {
            $response = Http::timeout(5)->get('https://api.frankfurter.app/latest?from=USD&to=CAD');

            if ($response->successful()) {
                $rate = (float) $response->json('rates.CAD');
                if ($rate > 0) { // jamais de taux nul ou absent mis en cache (prix à 0 $)
                    Cache::put('exchange_rate_usd_cad', $rate, 24 * 60 * 60);

                    return $rate;
                }
            }
        } catch (\Exception $e) {
            Log::warning('ExchangeRateService : API frankfurter échouée — ' . $e->getMessage());
        }

        // Repli .env sans cacher (réessaie au prochain appel). Clé réelle : shop.pricing.usd_cad_rate (l'ancienne
        // lecture de shop.usd_cad_rate ignorait SHOP_USD_CAD_RATE).
        return (float) config('shop.pricing.usd_cad_rate', 1.40);
    }

    public static function rate(): float
    {
        return app(static::class)->getUsdToCad();
    }
}
