<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Fabrique unique du client HTTP Gelato : hôte par API + en-tête d'authentification.
 * Une clé d'API inconnue échoue bruyamment, jamais silencieusement.
 */
final class GelatoHttpClient
{
    public static function forApi(string $api): PendingRequest
    {
        $hosts = [
            'base' => config('shop.gelato.api_url'),
            'order' => 'https://order.gelatoapis.com',
            'product' => 'https://product.gelatoapis.com',
            'ecommerce' => 'https://ecommerce.gelatoapis.com',
        ];

        if (! isset($hosts[$api])) {
            throw new \InvalidArgumentException("API Gelato inconnue : {$api}");
        }

        // Pas de cast sur la clé ni l'hôte : on reproduit EXACTEMENT les call-sites d'origine
        // (config() sans cast), pour un refactor strictement iso-comportement.
        return Http::withHeaders(['X-API-KEY' => config('shop.gelato.api_key')])
            ->baseUrl($hosts[$api]);
    }
}
