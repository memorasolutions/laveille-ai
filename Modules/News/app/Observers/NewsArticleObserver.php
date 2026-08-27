<?php

declare(strict_types=1);

namespace Modules\News\Observers;

use App\Support\ResponseCache\PublicCachePurger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;

class NewsArticleObserver
{
    public function updated(NewsArticle $article): void
    {
        // Capturé UNE SEULE FOIS avant tout traitement : createShortUrlIfNeeded() effectue
        // un updateQuietly() imbriqué qui resynchronise l'état "original" du modèle (syncOriginal),
        // ce qui ferait retomber isDirty('is_published') à false si on le recalculait après coup -
        // dispatchAutoToolDetection() manquerait alors systématiquement la toute première publication.
        $justPublished = (bool) $article->is_published && $article->isDirty('is_published');

        // ACTION : purge des pages de LISTE (accueil + index actualités) à chaque bascule de
        // publication - captée ici, AVANT createShortUrlIfNeeded() (même piège que ci-dessus :
        // wasChanged() reflète le DERNIER save(), un updateQuietly() imbriqué l'écraserait).
        // Corrige le défaut mesuré le 2026-08-26 : la fiche publiée devient visible sur sa
        // propre page (jamais mise en cache avant publication, donc jamais périmée), mais
        // restait invisible sur /actualites et l'accueil jusqu'à l'expiration naturelle de LEUR
        // cache (personne ne purgeait ces URLs) - ce point unique couvre les 3 chemins de
        // publication existants (bascule rapide admin, écran de composition, `news:apply
        // --publish`) car tous passent par un update() Eloquent qui déclenche cet observer.
        // MCP: SELF (<5 lignes utiles)
        // RAISON: choke point unique (DRY) - la dépublication doit purger tout autant (une
        // fiche retirée doit aussi disparaître des listes sans attendre l'expiration).
        $this->purgePublicListCache($article->wasChanged('is_published'));

        $this->createShortUrlIfNeeded($article, $justPublished);
        $this->dispatchAutoToolDetection($article, $justPublished);
    }

    /**
     * Purge ciblée (jamais un ResponseCache::clear() global - voir docblock de
     * PublicCachePurger) des pages qui LISTENT les actualités : l'accueil (aperçu des
     * dernières actualités, cf. HomeController), /actualites (news.index) et /verifications
     * (news.verifications, même contrôleur filtré sur factChecked()). La fiche elle-même n'a
     * pas besoin d'être purgée ici : une fiche non publiée répond 404 (jamais mise en cache,
     * cf. PublicNewsController::show), donc sa page ne peut porter aucune version périmée au
     * moment où elle devient publiée.
     *
     * Hors périmètre, assumé : les variantes filtrées/paginées de /actualites
     * (?category=/?period=/?page=) restent en cache jusqu'à leur propre expiration (600s,
     * cacheResponse:600 sur la route news.index) - les purger toutes exigerait de connaître à
     * l'avance chaque combinaison, ce que Spatie ne permet pas de cibler par motif. Dix minutes
     * d'attente sur une vue secondaire filtrée est un compromis assumé.
     *
     * PRÉCISION (mesure du 2026-08-27, corrige une lecture trop rapide du 2026-08-26) : la
     * durée « sept jours » citée ailleurs dans ce module vient de `cache_lifetime_in_seconds`
     * (config/responsecache.php), le DÉFAUT du package quand une route ne précise AUCUNE durée -
     * elle ne s'applique à AUCUNE route publique de ce site, qui déclarent toutes une durée
     * explicite (news.index/news.show/news.verifications/home = 600s, glossaire = 3600s...).
     * Sans cette purge, l'exposition réelle d'une fiche fraîchement publiée sur /actualites et
     * l'accueil était donc au plus dix minutes (l'expiration naturelle de LEUR propre cache),
     * jamais sept jours - le défaut restait réel (aucune purge n'existait avant ce correctif),
     * seule l'ampleur rapportée était surestimée.
     */
    private function purgePublicListCache(bool $publicationStateChanged): void
    {
        if (! $publicationStateChanged) {
            return;
        }

        PublicCachePurger::forgetRoutes(['home', 'news.index', 'news.verifications']);
    }

    private function createShortUrlIfNeeded(NewsArticle $article, bool $justPublished): void
    {
        if (! class_exists(\Modules\ShortUrl\Services\ShortUrlService::class)) {
            return;
        }

        // Uniquement quand is_published passe à true
        if (! $justPublished) {
            return;
        }

        if ($article->short_url_id) {
            return;
        }

        $domain = \Modules\ShortUrl\Models\ShortUrlDomain::where('is_default', true)->first();
        if (! $domain) {
            return;
        }

        $baseSlug = 'actu-'.mb_substr($article->slug, 0, 20);
        $slug = $baseSlug;
        $counter = 2;

        while (\Modules\ShortUrl\Models\ShortUrl::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        try {
            $service = app(\Modules\ShortUrl\Services\ShortUrlService::class);
            $shortUrl = $service->createShortUrl([
                'original_url' => config('app.url').'/actualites/'.$article->slug,
                'slug' => $slug,
                'title' => $article->seo_title ?? $article->title,
                'og_title' => $article->seo_title ?? $article->title,
                'og_description' => $article->meta_description,
                'og_image' => $article->image_url,
                'redirect_type' => 301,
                'is_active' => true,
                'domain_id' => $domain->id,
            ], null);

            $article->updateQuietly(['short_url_id' => $shortUrl->id]);

            Log::info("Short URL created: {$slug} → article {$article->id}");
        } catch (\Throwable $e) {
            Log::warning("Short URL creation failed for article {$article->id}: ".$e->getMessage());
        }
    }

    /**
     * Dispatch la détection automatique d'outils annuaire à la publication (source=auto).
     * Couvre TOUS les articles peu importe category_tag (contrairement à ContentPublished
     * dans NewsArticle::booted(), qui exige un category_tag) pour maximiser la détection.
     * Le bouton manuel "Suggérer les outils détectés" reste disponible en parallèle
     * (source=manual via sync(), jamais affecté par ce chantier).
     */
    private function dispatchAutoToolDetection(NewsArticle $article, bool $justPublished): void
    {
        if (! class_exists(\Modules\News\Jobs\AutoDetectNewsToolsJob::class)) {
            return;
        }

        if (! $justPublished) {
            return;
        }

        \Modules\News\Jobs\AutoDetectNewsToolsJob::dispatch($article);
    }
}
