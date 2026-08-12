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
     * ACTION (observabilité Actus 2.0, 2026-08-11) : « quasi-regroupement » = paire (ou
     * candidat d'absorption) REFUSÉE dont le signal était néanmoins proche du seuil requis.
     * Seuil de bruit choisi : score/chevauchement au moins égal à la MOITIÉ du seuil requis
     * (jaccard_threshold et min_entity_overlap, voir Modules/News/config/fusion.php) - assez bas
     * pour capter les cas "presque regroupés" utiles à la calibration future des seuils, assez
     * haut pour ignorer les paires franchement sans rapport (bruit pur, ex. deux articles Apple
     * sur des sujets différents dont le seul point commun est le mot "Apple").
     * MCP: SELF (<5 lignes)
     * RAISON: seuil arbitraire mais explicite et documenté, jamais un des seuils de fusion.php
     * eux-mêmes (interdiction du chantier).
     */
    private const NEAR_MISS_NOISE_FACTOR = 0.5;

    /**
     * ACTION : plafond de lignes de quasi-regroupement journalisées par exécution - borne le
     * volume de logs indépendamment du nombre d'articles/paires comparées (contrainte de volume
     * Actus 2.0 : ~96 lignes/jour max toutes catégories confondues, cron horaire).
     * MCP: SELF (<5 lignes)
     */
    private const MAX_NEAR_MISSES = 3;

    /** @var int Compte TOTAL des quasi-regroupements détectés pendant cluster() (pas seulement les 3 gardés). */
    private int $nearMissTotal = 0;

    /**
     * ACTION : ne conserve QUE les meilleurs candidats (au plus MAX_NEAR_MISSES), jamais la
     * liste complète - la boucle de comparaison est déjà quadratique, cette structure reste de
     * taille bornée (≤ MAX_NEAR_MISSES + 1 le temps d'un tri) quel que soit le nombre de paires.
     * MCP: SELF (<5 lignes)
     * RAISON: contrainte de performance explicite - aucune requête additionnelle dans la boucle,
     * accumulation en mémoire de données légères seulement.
     *
     * @var array<int, array{title_a: string, title_b: string, jaccard: float, jaccard_threshold: float, entity_overlap: int, entity_threshold: int, reason: string, closeness: float}>
     */
    private array $nearMissTop = [];

    /**
     * @param  array<int, NewsArticle>  $articles
     * @return array{
     *     new_groups: array<int, array<int, NewsArticle>>,
     *     singletons: array<int, NewsArticle>,
     *     absorptions: array<int, array{digest: NewsArticle, members: array<int, NewsArticle>}>,
     *     near_misses: array{total: int, top: array<int, array<string, mixed>>}
     * }
     */
    public function cluster(array $articles): array
    {
        $this->nearMissTotal = 0;
        $this->nearMissTop = [];

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
            'near_misses' => [
                'total' => $this->nearMissTotal,
                'top' => $this->nearMissTop,
            ],
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
                } else {
                    $this->recordNearMissIfNoisy((string) $articles[$i]->title, (string) $articles[$j]->title, $result);
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

            $this->recordNearMissIfNoisy((string) $representative->title, (string) ($digest->seo_title ?? $digest->title), $result);
        }

        return null;
    }

    /**
     * ACTION (observabilité Actus 2.0, 2026-08-11) : enregistre un candidat REFUSÉ (pas de la
     * même grappe) uniquement s'il dépasse le seuil de bruit NEAR_MISS_NOISE_FACTOR (voir
     * docblock de la constante). Coût : aucune requête, une comparaison + insertion dans un
     * tableau borné à MAX_NEAR_MISSES éléments (tri léger, jamais toute la liste conservée).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: appelée depuis buildComponents() (quadratique) ET findMatchingDigest() (linéaire) -
     * factorisée pour ne pas dupliquer le calcul de seuil de bruit (DRY).
     *
     * @param  array{is_same_cluster: bool, score: float, reason: string, signals: array<string, bool>, entity_overlap: int}  $result
     */
    private function recordNearMissIfNoisy(string $titleA, string $titleB, array $result): void
    {
        $jaccardThreshold = (float) config('news.fusion.jaccard_threshold', 0.30);
        $minEntityOverlap = (int) config('news.fusion.min_entity_overlap', 2);

        $jaccard = $result['score'];
        $entityOverlap = $result['entity_overlap'];

        $noiseJaccardFloor = $jaccardThreshold * self::NEAR_MISS_NOISE_FACTOR;
        $noiseEntityFloor = max(1, (int) ceil($minEntityOverlap * self::NEAR_MISS_NOISE_FACTOR));

        $isNoisy = $jaccard >= $noiseJaccardFloor || $entityOverlap >= $noiseEntityFloor;

        if (! $isNoisy) {
            return;
        }

        $this->nearMissTotal++;

        $reasonParts = [];
        if ($jaccard < $jaccardThreshold) {
            $reasonParts[] = sprintf('jaccard %.3f<%.2f', $jaccard, $jaccardThreshold);
        }
        if ($entityOverlap < $minEntityOverlap) {
            $reasonParts[] = sprintf('entités %d<%d', $entityOverlap, $minEntityOverlap);
        }

        $candidate = [
            'title_a' => mb_substr($titleA, 0, 60),
            'title_b' => mb_substr($titleB, 0, 60),
            'jaccard' => $jaccard,
            'jaccard_threshold' => $jaccardThreshold,
            'entity_overlap' => $entityOverlap,
            'entity_threshold' => $minEntityOverlap,
            'reason' => $reasonParts !== [] ? implode(', ', $reasonParts) : 'seuils non atteints',
            'closeness' => max(
                $jaccardThreshold > 0.0 ? $jaccard / $jaccardThreshold : 0.0,
                $minEntityOverlap > 0 ? $entityOverlap / $minEntityOverlap : 0.0
            ),
        ];

        $this->nearMissTop[] = $candidate;
        usort($this->nearMissTop, static fn (array $a, array $b) => $b['closeness'] <=> $a['closeness']);

        if (count($this->nearMissTop) > self::MAX_NEAR_MISSES) {
            array_pop($this->nearMissTop);
        }
    }
}
