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
    /**
     * Rend l'emplacement `$key`.
     *
     * Le choix de l'audience (membre ou anonyme) et l'alternance AdSense / pub directe se
     * décident ICI, hors de tout cache : sinon une version mise en cache pour un anonyme
     * serait servie à un membre (ou l'inverse). Seul le rendu DIRECT compilé (coût réel de la
     * compilation Blade) reste mis en cache par clé et par jour.
     */
    public function render(string $key): ?string
    {
        $ad = $this->resolveActive($key);

        if (! $ad) {
            return null;
        }

        // Interrupteur ADSENSE_MEMBERS_SEE_ADS : vrai (défaut) = les membres voient AdSense comme
        // les anonymes; faux = ancien comportement (membres sans AdSense).
        $membersSeeAds = (bool) config('services.adsense.members_see_ads');
        $withheldFromUser = auth()->check() && ! $membersSeeAds;
        $hasAdsense = $ad->isAdsense() && (bool) config('services.adsense.client_id');
        $hasDirect = $ad->hasDirect();

        if ($hasAdsense && $hasDirect) {
            // Alternance quotidienne déterministe : jour pair = AdSense, impair = direct.
            // Si les membres ne voient pas AdSense, un membre reçoit TOUJOURS la pub directe.
            $useAdsense = ! $withheldFromUser && $this->dayOfYear() % 2 === 0;

            return $useAdsense ? $this->renderAdsense($ad) : $this->renderDirect($ad);
        }

        if ($hasAdsense) {
            // AdSense seul : rien du tout pour un membre si l'interrupteur le retire aux membres.
            return $withheldFromUser ? null : $this->renderAdsense($ad);
        }

        if ($hasDirect) {
            return $this->renderDirect($ad);
        }

        return null;
    }

    /**
     * Rendu de l'unité AdSense (balise `<ins>`). Jamais mis en cache : il dépend de l'audience.
     * Le label « Publicité » est omis, AdSense s'auto-étiquette.
     */
    public function renderAdsense(AdPlacement $ad): string
    {
        $height = (int) ($ad->min_height ?: 280);
        $format = (string) ($ad->ad_format ?: 'auto');
        $client = (string) config('services.adsense.client_id');
        $lazy = (bool) $ad->lazy;

        // Une unité « fluid » d'AdSense est une annonce In-Article : elle exige
        // data-ad-layout="in-article" et un texte centré, et NON data-full-width-responsive
        // (réservé aux unités display responsive). Sans ce layout, l'unité ne se remplit pas.
        $isFluid = $format === 'fluid';
        $insStyle = $isFluid
            ? 'display:block;text-align:center;min-height:'.$height.'px'
            : 'display:block;min-height:'.$height.'px';

        $html = '<div class="ad-wrapper ad-external lv-adsense-wrap" style="min-height:'.$height.'px">'
            .'<ins class="adsbygoogle lv-adsense" style="'.$insStyle.'"'
            .' data-ad-client="'.e($client).'"'
            .' data-ad-slot="'.e((string) $ad->ad_slot).'"'
            .($isFluid ? ' data-ad-layout="in-article"' : '')
            .' data-ad-format="'.e($format).'"'
            .($isFluid ? '' : ' data-full-width-responsive="true"')
            .($lazy ? ' data-lv-lazy="1"' : '')
            .'></ins>';

        if (! $lazy) {
            $html .= '<script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>';
        }

        return $html.'</div>';
    }

    /**
     * Rendu de la pub directe, compilé puis mis en cache par emplacement et par jour.
     */
    protected function renderDirect(AdPlacement $ad): string
    {
        // Le jour (America/Toronto) entre dans la clé de cache : sans lui, la rotation
        // des encarts livres serait figée par ce cache et n'avancerait jamais. Une
        // entrée par emplacement et par jour, ce qui reste négligeable.
        $key = $ad->key;

        return (string) Cache::remember("ad_placement:{$key}:{$this->dayKey()}", 600, function () use ($ad, $key) {
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

    /**
     * Ligne active de l'emplacement, avec un cache léger (le rendu, lui, n'est pas décidé ici).
     * On met en cache les attributs bruts (tableau), pas le modèle, ce qui reste sûr à
     * sérialiser; un tableau vide signifie « aucun emplacement actif » (évite de re-requêter).
     */
    protected function resolveActive(string $key): ?AdPlacement
    {
        $attributes = Cache::remember("ad_placement_row:{$key}:{$this->dayKey()}", 600, function () use ($key): array {
            $row = AdPlacement::active()->byKey($key)->first();

            return $row ? $row->getAttributes() : [];
        });

        if ($attributes === []) {
            return null;
        }

        /** @var AdPlacement $ad */
        $ad = (new AdPlacement)->newFromBuilder($attributes);

        return $ad;
    }

    protected function dayKey(): string
    {
        return now()->timezone('America/Toronto')->format('Y-z');
    }

    protected function dayOfYear(): int
    {
        return now()->timezone('America/Toronto')->dayOfYear;
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
        // Les clés de cache réelles portent le suffixe du jour (America/Toronto) : sans lui,
        // Cache::forget viserait une clé inexistante. On vide les DEUX familles : le rendu
        // direct compilé et la ligne résolue.
        $day = $this->dayKey();

        $forget = function (string $adKey) use ($day): void {
            Cache::forget("ad_placement:{$adKey}:{$day}");
            Cache::forget("ad_placement_row:{$adKey}:{$day}");
        };

        if ($key) {
            $forget($key);

            return;
        }

        AdPlacement::all()->each(fn (AdPlacement $ad) => $forget($ad->key));
    }
}
