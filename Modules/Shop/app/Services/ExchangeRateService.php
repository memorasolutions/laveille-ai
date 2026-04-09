<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public function getUsdToCad(): float
    {
        $cached = Cache::get('exchange_rate_usd_cad');
        if ($cached !== null) {
            return (float) $cached;
        }

        try {
            $response = Http::timeout(5)->get('https://api.frankfurter.app/latest?from=USD&to=CAD');

            if ($response->successful()) {
                $rate = (float) $response->json('rates.CAD');
                Cache::put('exchange_rate_usd_cad', $rate, 24 * 60 * 60);
                return $rate;
            }
        } catch (\Exception $e) {
            Log::warning('ExchangeRateService : API frankfurter échouée — ' . $e->getMessage());
        }

        // Fallback .env sans cacher (réessaie au prochain appel)
        return (float) config('shop.usd_cad_rate', 1.40);
    }

    public static function rate(): float
    {
        return app(static::class)->getUsdToCad();
    }
}
