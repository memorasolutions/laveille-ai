<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Ads\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Modules\Ads\Models\AdPlacement;

class AdsRenderer
{
    public function render(string $key): ?string
    {
        // Le jour (America/Toronto) entre dans la clé de cache : sans lui, la rotation
        // des encarts livres serait figée par ce cache et n'avancerait jamais. Une
        // entrée par emplacement et par jour, ce qui reste négligeable.
        $day = now()->timezone('America/Toronto')->format('Y-z');

        return Cache::remember("ad_placement:{$key}:{$day}", 600, function () use ($key) {
            $ad = AdPlacement::active()->byKey($key)->first();

            if (! $ad) {
                return null;
            }

            $html = $ad->ad_code;

            // #230 — Si ad_code contient une balise composant Blade (<x-namespace::name>),
            // compiler côté serveur pour permettre la réutilisation DRY de composants
            // (ex. <x-fronttheme::book-promo />). Sinon, HTML brut comme avant.
            if (is_string($html) && str_contains($html, '<x-')) {
                try {
                    // Contexte de rotation : chaque emplacement reçoit son propre rang
                    // (l'identifiant de la ligne), ce qui garantit que deux encarts
                    // d'une même page n'affichent pas le même livre. Le compteur de
                    // requête de BookPromoRotator ne suffirait pas ici : chaque
                    // emplacement est mis en cache séparément et n'est donc pas rendu
                    // dans la même requête que son voisin.
                    if (class_exists(\Modules\Books\Services\BookPromoRotator::class)) {
                        \Modules\Books\Services\BookPromoRotator::setContext($key, (int) $ad->id);
                    }

                    $html = Blade::render($html);
                } catch (\Throwable $e) {
                    \Log::warning("AdsRenderer: Blade::render échec pour {$key}", ['error' => $e->getMessage()]);
                    // fallback : laisser le HTML brut
                } finally {
                    if (class_exists(\Modules\Books\Services\BookPromoRotator::class)) {
                        \Modules\Books\Services\BookPromoRotator::clearContext();
                    }
                }
            }

            // Pubs internes : ajouter le label "Publicité" (les externes comme Google le gèrent elles-mêmes)
            if (! $ad->is_external) {
                $html = '<div class="ad-wrapper ad-internal">'
                    .'<span class="ad-label">Publicité</span>'
                    .$html
                    .'</div>';
            }

            return $html;
        });
    }

    public function renderShortcodes(string $content): string
    {
        return (string) preg_replace_callback('/\[ad key="([^"]+)"\]/', function ($matches) {
            return $this->render($matches[1]) ?? '';
        }, $content);
    }

    public function injectAfterParagraph(string $content, string $adKey, int $afterParagraph = 3): string
    {
        $ad = $this->render($adKey);
        if (! $ad) {
            return $content;
        }

        $paragraphs = preg_split('/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        $pCount = 0;
        $injected = false;

        for ($i = 0; $i < count($paragraphs); $i++) {
            $result .= $paragraphs[$i];
            if ($paragraphs[$i] === '</p>') {
                $pCount++;
                if ($pCount === $afterParagraph && ! $injected) {
                    $result .= "\n".$ad."\n";
                    $injected = true;
                }
            }
        }

        return $result;
    }

    public function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget("ad_placement:{$key}");

            return;
        }

        AdPlacement::all()->each(fn (AdPlacement $ad) => Cache::forget("ad_placement:{$ad->key}"));
    }
}
