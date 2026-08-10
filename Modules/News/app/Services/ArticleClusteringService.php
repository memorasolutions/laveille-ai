<?php

declare(strict_types=1);

namespace Modules\News\Services;

use Modules\News\Models\NewsArticle;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai - Actus 2.0 : fusion multi-sources des actualités.
 *
 * Regroupe déterministiquement les articles candidats du jour qui couvrent le même sujet,
 * AVANT l'appel IA. Aucune API d'embeddings, aucun appel réseau : uniquement DedupService
 * (titres + entités), réutilisé tel quel (DRY, design doc section 3.2). Composantes connexes
 * calculées par union-find sur DedupService::isSameStoryCluster().
 *
 * Absorption (arbitrage post-revue, section 14 du design doc) : les deux cas « deuxième fiche
 * comparative le même jour sur le même sujet » et « article qui arrive après la création de la
 * fiche » partagent le MÊME mécanisme ici - avant de former un nouveau groupe ou de traiter un
 * singleton, on vérifie d'abord si une fiche comparative récente (is_comparative_digest = true,
 * dans la fenêtre window_hours) correspond déjà au sujet. Si oui, les articles sont rattachés
 * comme membres sans régénérer le texte de la fiche (zéro appel IA additionnel).
 */
class ArticleClusteringService
{
    /**
     * @param  array<int, NewsArticle>  $articles
     * @return array{
     *     new_groups: array<int, array<int, NewsArticle>>,
     *     singletons: array<int, NewsArticle>,
     *     absorptions: array<int, array{digest: NewsArticle, members: array<int, NewsArticle>}>
     * }
     */
    public function cluster(array $articles): array
    {
        $newGroups = [];
        $singletons = [];
        $absorptions = [];

        foreach ($this->buildComponents(array_values($articles)) as $component) {
            $digest = $this->findMatchingDigest($component[0]);

            if ($digest !== null) {
                $absorptions[] = ['digest' => $digest, 'members' => $component];
                continue;
            }

            if (count($component) >= (int) config('news.fusion.min_group_size', 2)) {
                $newGroups[] = $component;
            } else {
                $singletons[] = $component[0];
            }
        }

        return [
            'new_groups' => $newGroups,
            'singletons' => $singletons,
            'absorptions' => $absorptions,
        ];
    }

    /**
     * Union-find : deux articles rejoignent la même composante connexe si
     * DedupService::isSameStoryCluster() les juge similaires (titres/entités). Déterministe :
     * mêmes entrées, mêmes composantes, à chaque exécution (aucun aléa, aucun appel réseau).
     *
     * @param  array<int, NewsArticle>  $articles
     * @return array<int, array<int, NewsArticle>>
     */
    private function buildComponents(array $articles): array
    {
        $count = count($articles);
        $parent = range(0, max(0, $count - 1));

        $find = function (int $i) use (&$parent, &$find): int {
            while ($parent[$i] !== $i) {
                $parent[$i] = $parent[$parent[$i]];
                $i = $parent[$i];
            }

            return $i;
        };

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $signals = [];
                $result = DedupService::isSameStoryCluster(
                    ['title' => $articles[$i]->title],
                    ['title' => $articles[$j]->title],
                    $signals
                );

                if ($result['is_same_cluster']) {
                    $rootI = $find($i);
                    $rootJ = $find($j);
                    if ($rootI !== $rootJ) {
                        $parent[$rootJ] = $rootI;
                    }
                }
            }
        }

        $groups = [];
        for ($i = 0; $i < $count; $i++) {
            $groups[$find($i)][] = $articles[$i];
        }

        return array_values($groups);
    }

    /**
     * Cherche une fiche comparative récente déjà publiée qui correspond au même sujet que le
     * représentant de la composante - mécanisme d'absorption unique (voir docblock de classe).
     */
    private function findMatchingDigest(NewsArticle $representative): ?NewsArticle
    {
        $windowHours = (int) config('news.fusion.window_hours', 36);

        // ACTION: is_published sélectionné explicitement - trouvé empiriquement en test (section
        // 14) : une sélection partielle sans cette colonne fait lire null à l'appelant
        // (absorbFusionMember() en hérite alors silencieusement pour le membre rattaché).
        // MCP: SELF (<5 lignes)
        // RAISON: un membre absorbé doit hériter du vrai statut de publication de la fiche.
        $recentDigests = NewsArticle::query()
            ->where('is_comparative_digest', true)
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->where('id', '!=', $representative->id)
            ->get(['id', 'title', 'seo_title', 'is_published']);

        foreach ($recentDigests as $digest) {
            $signals = [];
            $result = DedupService::isSameStoryCluster(
                ['title' => $representative->title],
                ['title' => $digest->seo_title ?? $digest->title],
                $signals
            );

            if ($result['is_same_cluster']) {
                return $digest;
            }
        }

        return null;
    }
}
