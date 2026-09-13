<?php

declare(strict_types=1);

namespace Modules\News\Console\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\News\Models\NewsArticle;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : ticket #2525 (2026-09-13) - logique de SÉLECTION partagée par les deux commandes de
 * rattrapage (news:backfill-auto-terms / news:backfill-auto-tools), qui portaient jusqu'ici EXACTEMENT
 * le même défaut par copier-coller : sélectionner le lot via whereDoesntHave('terms'|'tools')
 * confond deux questions différentes - « la fiche a-t-elle une liaison ? » et « la fiche a-t-elle
 * déjà été EXAMINÉE ? ». Une fiche qui ne mentionne aucun terme (ou aucun outil) n'obtient jamais
 * de liaison, reste donc éternellement dans le périmètre whereDoesntHave, et se fait retraiter à
 * CHAQUE exécution - mesuré en production sur cinq lots consécutifs : 400 fiches traitées par lot,
 * mais seulement 303 puis 249 puis 189 puis 149 réellement évacuées (coût par résultat qui double
 * toutes les deux exécutions, la commande n'atteint jamais zéro).
 *
 * RAISON: le correctif sépare les deux questions - la relation (terms()/tools()) reste
 *         inchangée pour SAVOIR si une liaison existe, mais la SÉLECTION du lot à traiter
 *         s'appuie désormais sur une colonne horodatée dédiée ($examinedColumn), posée une fois
 *         qu'une fiche a été passée au détecteur, qu'elle ait ou non produit une correspondance.
 *         Une fiche examinée SANS correspondance ne revient donc plus jamais dans le lot -
 *         c'est le coeur du correctif. `--rescanner` reste la seule porte pour repasser
 *         volontairement sur le corpus déjà examiné (amélioration du détecteur).
 *
 * Trait plutôt qu'une classe abstraite : les deux commandes gardent chacune leur propre
 * `$signature`/`$description` et leur propre logique d'attache (suggest()/attachAuto() pour les
 * outils, suggest() à effet de bord pour les termes - les deux mécanismes DIVERGENT réellement,
 * ce n'est pas une ressemblance de forme à fusionner). Seule la sélection/le marquage - la
 * connaissance RÉELLEMENT dupliquée et fautive - est mutualisée ici.
 */
trait SelectsArticlesPendingAutoDetection
{
    /**
     * Sélectionne le lot de fiches publiées à traiter pour cette exécution.
     *
     * Sans --rescanner (mode normal) : uniquement les fiches dont $examinedColumn est NULL,
     * c'est-à-dire jamais examinées. Triées par identifiant croissant (les plus anciennes
     * d'abord) sauf en tirage au hasard ($randomOrder, réservé à la simulation).
     *
     * Avec --rescanner : le filtre disparaît (tout le corpus publié redevient éligible), trié
     * sur la colonne d'examen elle-même - les fiches encore jamais examinées (NULL) d'abord,
     * classées premières par MySQL comme par SQLite en tri ASC, puis les plus anciennement
     * examinées. Sans cette option, une fiche déjà examinée ne serait plus JAMAIS reconsidérée,
     * même après une amélioration du détecteur.
     *
     * @return Collection<int, NewsArticle>
     */
    protected function selectArticlesPendingExamination(
        string $examinedColumn,
        int $limit,
        bool $rescanner,
        bool $randomOrder
    ): Collection {
        $query = NewsArticle::published();

        if (! $rescanner) {
            $query->whereNull($examinedColumn);
        }

        if ($randomOrder) {
            $query->inRandomOrder();
        } elseif ($rescanner) {
            $query->orderBy($examinedColumn)->orderBy('id');
        } else {
            $query->orderBy('id');
        }

        return $query->limit($limit)->get();
    }

    /**
     * Marque une fiche comme EXAMINÉE à l'instant présent, qu'elle ait ou non produit une
     * correspondance - l'écriture absente de l'ancienne version des deux commandes, sans
     * laquelle une fiche sans correspondance revenait indéfiniment dans le lot.
     *
     * Écrit par le QUERY BUILDER brut (jamais via save() sur le modèle Eloquent) : une simple
     * opération de bookkeeping système ne doit ni déclencher NewsArticleObserver ni faire
     * avancer updated_at/content_updated_at d'une fiche - même doctrine que
     * Modules\Core\Services\ViewCounterService::record().
     */
    protected function markArticleExamined(NewsArticle $article, string $examinedColumn): void
    {
        DB::table('news_articles')
            ->where('id', $article->id)
            ->update([$examinedColumn => now()]);
    }

    /**
     * Nombre de fiches publiées JAMAIS EXAMINÉES restantes - la mesure honnête du retard de
     * rattrapage. À ne jamais confondre avec « sans liaison » : une fiche peut avoir été
     * examinée et n'avoir simplement aucune correspondance à faire (absence normale), sans que
     * cela constitue un retard.
     */
    protected function countArticlesPendingExamination(string $examinedColumn): int
    {
        return NewsArticle::published()->whereNull($examinedColumn)->count();
    }
}
