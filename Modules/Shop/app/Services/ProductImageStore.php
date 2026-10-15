<?php

declare(strict_types=1);

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Télécharge et auto-héberge les images de produit Gelato.
 *
 * POURQUOI : Gelato ne sert pas d'URL d'image stable. Le `previewUrl` du produit et les
 * `fileUrl` des mockups de variante sont des URL AWS S3 PRÉ-SIGNÉES (X-Amz-Expires=86400),
 * signées à neuf à chaque appel d'API et valides 24 h SEULEMENT. Stocker une de ces URL dans
 * la base la condamne : 24 h plus tard elle répond 403 et l'image du produit est cassée partout
 * (page produit, carte de la vitrine, og:image). Mesuré le 2026-10-15 : toutes les images de la
 * boutique cassées, toutes couleurs, parce que la synchro avait stocké l'URL signée expirée.
 *
 * REMÈDE : au moment de la synchro (où l'URL signée est encore fraîche), on télécharge l'image
 * et on la dépose sur le disque public local (`storage/app/public/shop/products/...`, servi via
 * le lien `public/storage`). On stocke alors un chemin LOCAL stable (`/storage/shop/products/x.jpg`)
 * qui n'expire jamais. Vaut pour TOUS les produits, présents et à venir (le correctif vit dans la
 * synchro, pas dans une retouche ponctuelle d'un produit). Même esprit que NewsImageService.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */
class ProductImageStore
{
    /** Poids maximal d'une image de mockup téléchargée (Gelato : ~30-80 Ko, on borne large). */
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const DIR = 'shop/products';

    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('public');
    }

    /**
     * Rend un chemin d'image LOCAL stable pour une URL d'image Gelato (ou null si indisponible).
     *
     * Idempotent : une valeur déjà locale (`/storage/...`) est renvoyée telle quelle, sans
     * re-téléchargement — une re-synchro ne refait donc pas le travail et n'accumule rien.
     * Une URL distante est téléchargée et déposée sous un nom dérivé de sa partie STABLE
     * (le chemin, sans la query signée), de sorte que la même image écrase son propre fichier
     * plutôt que d'en créer un nouveau à chaque signature.
     */
    public function localize(?string $url): ?string
    {
        $url = is_string($url) ? trim($url) : '';
        if ($url === '') {
            return null;
        }

        // Déjà local : ne rien faire (idempotence des re-synchros).
        if (Str::startsWith($url, '/storage/')) {
            return $url;
        }

        // On ne traite que des URL http(s) absolues ; tout le reste est ignoré prudemment.
        if (! Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
        $name = self::DIR.'/'.sha1($path).'.jpg';

        // Déjà téléchargée lors d'une synchro précédente : réutiliser.
        try {
            if ($this->disk()->exists($name)) {
                return '/storage/'.$name;
            }
        } catch (\Throwable $e) {
            // disque indisponible : on tente quand même le téléchargement ci-dessous
        }

        try {
            $ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130 Safari/537.36';

            $response = Http::withHeaders(['User-Agent' => $ua])
                ->timeout(20)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('shop.image.localize.http', ['status' => $response->status(), 'path' => $path]);

                return null;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if ($contentType !== '' && ! Str::startsWith($contentType, 'image/')) {
                Log::warning('shop.image.localize.not_image', ['content_type' => $contentType, 'path' => $path]);

                return null;
            }

            $body = $response->body();
            if ($body === '' || strlen($body) > self::MAX_BYTES) {
                Log::warning('shop.image.localize.size', ['bytes' => strlen($body), 'path' => $path]);

                return null;
            }

            $this->disk()->put($name, $body);

            return '/storage/'.$name;
        } catch (\Throwable $e) {
            Log::warning('shop.image.localize.exception', ['message' => $e->getMessage(), 'path' => $path]);

            return null;
        }
    }

    /**
     * Localise une liste d'URL, en écartant celles qui échouent (jamais une URL cassée en base).
     *
     * @param  array<int, string|null>  $urls
     * @return array<int, string>
     */
    public function localizeMany(array $urls): array
    {
        $out = [];
        foreach ($urls as $u) {
            $local = $this->localize(is_string($u) ? $u : null);
            if ($local !== null) {
                $out[] = $local;
            }
        }

        return array_values(array_unique($out));
    }
}
