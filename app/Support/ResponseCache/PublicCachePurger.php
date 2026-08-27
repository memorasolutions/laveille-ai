<?php

declare(strict_types=1);

namespace App\Support\ResponseCache;

use Illuminate\Support\Facades\Route;

/**
 * Purge CIBLÉE du cache de réponse (Spatie ResponseCache) pour une liste d'URLs nommées
 * (routes), indépendamment du module qui les possède.
 *
 * RAISON D'ÊTRE : quand une fiche change d'état (publication, dépublication, contenu affiché
 * en aperçu), sa PROPRE page peut se régénérer d'elle-même (jamais mise en cache tant qu'elle
 * n'a jamais été servie en 200), mais les pages qui la LISTENT (accueil, index de module,
 * rubriques) ont déjà été mises en cache par un visiteur précédent et ne se régénèrent pas
 * avant l'expiration naturelle de leur propre durée de vie - jusque-là, la fiche reste
 * invisible là où un visiteur irait la chercher. Ce helper généralise le mécanisme déjà en
 * place pour la fiche elle-même (cf. Modules\News\Actions\NewsToolSyncAction::invalidatePublicCache)
 * à N'IMPORTE QUELLE liste de routes nommées, pour que chaque module appelant reste responsable
 * de SA connaissance propre (quelles routes le concernent), sans dupliquer la mécanique Spatie.
 *
 * Volontairement PAS un ResponseCache::clear() global : ce site sert son rendu 10x plus vite
 * depuis que les pages publiques sont mises en cache (v1.220.0) - un clear() global à chaque
 * publication annulerait ce gain pour TOUT le trafic, pas seulement pour la fiche concernée.
 *
 * Volontairement PAS de tags Spatie (`cache_tag` / `usingTags()`) : le store configuré ici est
 * `file` (config/cache.php), qui n'implémente pas `TaggableStore` - `Cache::store('file')->tags()`
 * lève `BadMethodCallException` à l'exécution. Les tags redeviendront viables si le store passe
 * un jour à `redis`/`memcached` ; en attendant, une liste de routes nommées est la seule option
 * à la fois fiable et sans dépendance nouvelle.
 *
 * Chaque appelant reste TENU de tenir sa propre liste de routes à jour (une route de liste
 * oubliée reste un défaut silencieux) - ce helper ne devine rien, il exécute une liste fournie.
 */
final class PublicCachePurger
{
    /**
     * @param  array<int, string>  $routeNames  Noms de routes (ex. ['home', 'news.index']).
     *                                          Une route absente (module désactivé) est
     *                                          ignorée silencieusement, jamais une erreur.
     */
    public static function forgetRoutes(array $routeNames): void
    {
        if ($routeNames === [] || ! class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
            return;
        }

        $urls = collect($routeNames)
            ->filter(fn (string $name) => Route::has($name))
            ->map(fn (string $name) => route($name))
            ->all();

        if ($urls === []) {
            return;
        }

        try {
            // On rompt la chaîne car usingSuffix() retourne AbstractRequestBuilder (lib
            // Spatie), ce qui tromperait PHPStan si chaîné avec forget() de CacheItemSelector -
            // même garde que NewsToolSyncAction::invalidatePublicCache().
            $cacheSelector = \Spatie\ResponseCache\Facades\ResponseCache::selectCachedItems()
                ->forUrls($urls);
            $cacheSelector->usingSuffix('');
            $cacheSelector->forget();
        } catch (\Throwable $e) {
            // Un cache qui refuse de se purger ne doit jamais faire échouer une publication.
        }
    }
}
