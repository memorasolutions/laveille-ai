<?php

declare(strict_types=1);

namespace Modules\Dictionary\Observers;

use App\Support\ResponseCache\PublicCachePurger;
use Modules\Dictionary\Models\Term;

/**
 * Purge ciblée du cache de réponse (Spatie ResponseCache) des pages qui LISTENT les termes
 * du glossaire, à chaque publication, modification (en restant publié) ou dépublication d'une
 * fiche - même patron que Modules\News\Observers\NewsArticleObserver::purgePublicListCache(),
 * réutilisant le même composant App\Support\ResponseCache\PublicCachePurger (DRY : la
 * connaissance « quelles pages publiques dépendent de ce contenu » ne se réécrit pas par
 * module).
 *
 * Mesuré le 2026-08-27 : AUCUNE purge n'existait pour ce module avant ce correctif (ni pour la
 * fiche, ni pour les listes) - le cas le plus dépourvu des modules audités ce jour-là. Les deux
 * routes publiques du glossaire déclarent une durée EXPLICITE de 3600s (cacheResponse:3600,
 * cf. Modules/Dictionary/routes/web.php) - jamais le défaut de 7 jours de
 * config/responsecache.php (qui ne s'applique qu'aux routes sans durée déclarée).
 */
class TermObserver
{
    public function created(Term $term): void
    {
        $this->purgePublicListCache((bool) $term->is_published);
    }

    public function updated(Term $term): void
    {
        $isPublished = (bool) $term->is_published;
        $wasPublished = (bool) $term->getOriginal('is_published');

        // Toute modification d'une fiche publiée (ou qui vient de le devenir/cesser de
        // l'être) purge les listes : le contenu affiché sur ces pages (nom, définition,
        // catégorie...) peut avoir changé, pas seulement le statut de publication.
        $this->purgePublicListCache(($isPublished || $wasPublished) && $term->wasChanged());
    }

    public function deleted(Term $term): void
    {
        $this->purgePublicListCache((bool) $term->is_published);
    }

    /**
     * Purge /glossaire (dictionary.index) et l'accueil (widget « termes à découvrir »,
     * cf. Modules\FrontTheme\Http\Controllers\HomeController). La fiche elle-même
     * (dictionary.show, cache 3600s) N'EST PAS purgée ici - même périmètre que
     * NewsArticleObserver : une fiche dépubliée y répond 404 (jamais mise en cache, cf.
     * PublicDictionaryController::show → Term::published()->firstOrFail()), donc le sens
     * dépublication ne peut jamais laisser de version périmée sur sa propre page. Le sens
     * inverse (édition de contenu d'un terme qui RESTE publié) reste hors périmètre assumé de
     * ce correctif : sa propre page se régénère au plus tard à l'expiration naturelle de son
     * cache (3600s) - jamais un ResponseCache::clear() global, qui annulerait le gain de
     * performance pour tout le trafic.
     */
    private function purgePublicListCache(bool $shouldPurge): void
    {
        if (! $shouldPurge) {
            return;
        }

        PublicCachePurger::forgetRoutes(['home', 'dictionary.index']);
    }
}
