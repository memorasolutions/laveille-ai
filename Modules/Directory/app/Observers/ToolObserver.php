<?php

declare(strict_types=1);

namespace Modules\Directory\Observers;

use App\Support\ResponseCache\PublicCachePurger;
use Modules\Directory\Jobs\CaptureScreenshotJob;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\EcosystemCountService;

class ToolObserver
{
    /**
     * Ticket #2289 (2026-09-05) - POINT UNIQUE de la garde contre la jonction schéma/séparateur
     * cassée par une espace insécable (ex. "https ://domaine", href résolu comme un chemin
     * RELATIF, URL mortes explorées par les robots). Déplacée ICI, sur l'écriture du modèle,
     * après un premier tour qui l'avait posée sur trois commandes seulement : un recensement a
     * montré une douzaine d'autres appelants (DirectoryAdminController, IngestController,
     * PublicDirectoryController, ToolDiscoveryService, ConvertPricesCadCommand, des seeders...)
     * qui écrivent les deux mêmes champs SANS passer par ces trois commandes.
     *
     * LEÇON DU 2026-09-05 sur ce même projet (exclusion d'auto-lien posée sur le NOM d'un terme,
     * jamais sur ses ALIAS - deux correctifs livrés sans effet réel, le mauvais point de
     * fabrication) : une garde qui vit sur l'appelant ne protège QUE l'appelant où elle est
     * posée. En la mettant sur `saving()`, TOUT chemin d'écriture, existant ou futur, est fermé
     * par le même geste - c'est la même connaissance métier appliquée au même endroit.
     *
     * CHAQUE locale est traitée, jamais seulement fr_CA : mesuré le 2026-09-05, certaines fiches
     * portent aussi des traductions 'fr' et 'en' sur ces deux champs. getTranslations() renvoie
     * la structure décodée {locale => texte} quelle que soit la locale active du modèle.
     */
    public function saving(Tool $tool): void
    {
        foreach (['description', 'short_description'] as $champ) {
            foreach ($tool->getTranslations($champ) as $locale => $valeur) {
                if (! is_string($valeur) || $valeur === '') {
                    continue;
                }

                $repare = lv_repare_jonction_schema_url($valeur);
                if ($repare !== $valeur) {
                    $tool->setTranslation($champ, $locale, $repare);
                }
            }
        }
    }

    public function saved(Tool $tool): void
    {
        // La capture est DISPATCHÉE, jamais exécutée ici (2026-08-23). Elle l'était en
        // synchrone, à l'intérieur du save() : captureWithRetry fait 3 tentatives de
        // Process::timeout(90) séparées par des pauses de 2 s et 4 s, soit 276 secondes au
        // pire. Deux conséquences mesurées, toutes deux réelles :
        //  - EnrichToolJob, dont le délai est calculé sur la cascade OpenRouter (270 s),
        //    portait en plus ces 276 s sans le savoir : il se tuait par expiration, alerte
        //    « has timed out » du 2026-08-23 13h38 Québec (17:38 UTC) à l'appui ;
        //  - publier un outil depuis l'administration pouvait bloquer la requête HTTP
        //    d'autant, ce que rien ne survit côté navigateur.
        // CaptureScreenshotJob existait déjà, correctement dimensionné (400 s, une seule
        // tentative) et posté sur la file `screenshots`, qui a un consommateur (cron
        // 2255189680). Le contrôle isAvailable() est retiré d'ici : le job le refait au
        // moment de s'exécuter, sur la machine qui exécute VRAIMENT la capture, et il
        // JOURNALISE l'indisponibilité au lieu de l'avaler en silence.
        if (
            $tool->wasChanged('status')
            && $tool->status === 'published'
            && empty($tool->screenshot_locked)
            && (empty($tool->screenshot) || str_starts_with((string) $tool->screenshot, 'http'))
        ) {
            CaptureScreenshotJob::dispatch($tool);
        }

        // Invalidation ecosystem_tag sur update réel uniquement (wasChanged() fiable ici, cf.
        // updated() ci-dessous pour la création — jamais wasRecentlyCreated dans saved() :
        // cette propriété reste `true` pour toute la durée de vie de l'instance PHP après un
        // insert, même lors d'un save() ultérieur sans rapport, ce qui invaliderait le cache
        // à chaque fois qu'un job/script garde le même objet Tool en mémoire).
        if ($tool->wasChanged(['ecosystem_tag', 'status'])) {
            EcosystemCountService::flushCache();
        }
    }

    public function created(Tool $tool): void
    {
        // wasChanged()/getChanges() sont TOUJOURS vides sur une création (Eloquent n'appelle
        // syncChanges() que dans performUpdate(), jamais dans performInsert() — vérifié sur ce
        // projet le 2026-07-23, cf. vendor/laravel/framework/.../Model.php). L'event created()
        // ne fire qu'une seule fois, exactement à l'insertion : c'est le bon endroit pour
        // invalider sans dépendre de wasChanged() ni du flag instable wasRecentlyCreated.
        EcosystemCountService::flushCache();

        $this->purgePublicListCache($tool->status === 'published');
    }

    public function updated(Tool $tool): void
    {
        $isPublished = $tool->status === 'published';
        $wasPublished = $tool->getOriginal('status') === 'published';

        // Toute modification d'une fiche publiée (ou qui vient de le devenir/cesser de
        // l'être) purge les listes : le contenu affiché sur ces pages (nom, tarif, logo...)
        // peut avoir changé, pas seulement le statut de publication.
        $this->purgePublicListCache(($isPublished || $wasPublished) && $tool->wasChanged());
    }

    public function deleted(Tool $tool): void
    {
        if ($tool->ecosystem_tag !== null) {
            EcosystemCountService::flushCache();
        }

        $this->purgePublicListCache($tool->status === 'published');
    }

    /**
     * Purge ciblée (jamais un ResponseCache::clear() global — voir docblock de
     * PublicCachePurger) des pages qui LISTENT les outils : l'accueil (widget « outils
     * populaires », cf. HomeController) et /annuaire (directory.index). La fiche elle-même
     * (directory.show) n'a pas besoin d'être purgée ici : sa route porte le middleware
     * doNotCacheResponse (cf. Modules/Directory/routes/web.php) — elle n'est jamais mise en
     * cache, donc ne peut jamais porter de version périmée.
     *
     * Mesuré le 2026-08-27 : avant ce correctif, seule la bascule de mise en avant
     * (DirectoryAdminController::toggleFeatured) purgeait quoi que ce soit — et via un
     * ResponseCache::clear() global, pas ciblé. La création et la modification d'une fiche via
     * l'admin (store()/update()) ne purgeaient rien : /annuaire (600s) et l'accueil (600s)
     * restaient périmés jusqu'à expiration naturelle. Hors périmètre, assumé, comme pour
     * NewsArticleObserver : les listes secondaires (/collections, comparateur filtré) ne sont
     * pas purgées par ce point de passage.
     */
    private function purgePublicListCache(bool $shouldPurge): void
    {
        if (! $shouldPurge) {
            return;
        }

        PublicCachePurger::forgetRoutes(['home', 'directory.index']);
    }
}
