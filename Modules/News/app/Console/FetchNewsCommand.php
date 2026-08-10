<?php

declare(strict_types=1);

namespace Modules\News\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsDedupLog;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\ArchiveContextService;
use Modules\News\Services\ArticleClusteringService;
use Modules\News\Services\DedupService;
use Modules\News\Services\RssFetcherService;
use Modules\Settings\Facades\Settings;

class FetchNewsCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'news:fetch {--source= : ID source spécifique} {--force : Forcer même si kill switch actif}';

    protected $description = 'Récupère les articles RSS, score et génère les résumés structurés IA.';

    public function handle(RssFetcherService $fetcher, AiSummaryService $summarizer): int
    {
        if ($this->shouldSkipForKillSwitch('cron.news-fetch')) {
            return self::SUCCESS;
        }

        $query = NewsSource::active();

        if ($sourceId = $this->option('source')) {
            $query->where('id', $sourceId);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info('Aucune source active trouvée.');
            return 0;
        }

        $minScore = (int) Settings::get('news.min_relevance_score', 7);
        $maxIa = (int) Settings::get('news.max_ia_articles_per_day', 10);
        $maxTech = (int) Settings::get('news.max_tech_articles_per_day', 5);

        $totalFetched = 0;
        $totalPublished = 0;
        $totalFiltered = 0;

        // Compteurs du jour
        $todayIa = NewsArticle::where('feed_type', 'ia')
            ->where('is_published', true)
            ->whereDate('created_at', today())
            ->count();
        $todayTech = NewsArticle::where('feed_type', 'techno')
            ->where('is_published', true)
            ->whereDate('created_at', today())
            ->count();

        // ACTION: gate maître Actus 2.0, lu une seule fois. OFF (défaut) = $fusionCandidates
        // reste vide et la branche différée ci-dessous n'est jamais empruntée : le pipeline
        // reste bit à bit identique, zéro appel à ArticleClusteringService/ArchiveContextService.
        // MCP: SELF (<5 lignes)
        // RAISON: critère d'acceptation n°1 de la spec Actus 2.0 (drapeau maître).
        $fusionEnabled = (bool) config('news.fusion.enabled', false);
        $fusionCandidates = [];

        foreach ($sources as $source) {
            $this->info("Récupération : {$source->name}");
            $fetched = $fetcher->fetchSource($source);
            $totalFetched += $fetched;
            $this->line("  {$fetched} nouveaux articles");

            $feedType = $this->detectFeedType($source);

            // ACTION: borne la file aux articles récents - sans elle, les articles sautés par
            // quota s'accumulent sans fin (12 436 rechargés par run mesurés le 2026-08-09) et le
            // cron horaire meurt en épuisement mémoire (128 Mo CLI) à chaque exécution.
            // MCP: SELF (<5 lignes)
            // RAISON: une actualité créée il y a plus de 48 h n'a plus vocation à être résumée.
            $toProcess = NewsArticle::where('news_source_id', $source->id)
                ->whereNull('structured_summary')
                ->where('is_published', false)
                ->where('created_at', '>=', now()->subHours((int) config('news.fetch_backlog_hours', 48)))
                ->get();

            foreach ($toProcess as $article) {
                // Pré-filtre mots-clés (gratuit)
                if (! $summarizer->isRelevant($article->title, $article->description)) {
                    $article->update([
                        'is_published' => false,
                        'summary' => '[non pertinent - mots-clés]',
                        'relevance_score' => 0,
                        'feed_type' => $feedType,
                    ]);
                    $this->line("  ⊘ Filtré mots-clés : {$article->title}");
                    $totalFiltered++;
                    continue;
                }

                // Vérifier quota du jour
                if ($feedType === 'ia' && $todayIa >= $maxIa) {
                    $this->line("  ⏸ Quota IA atteint ({$maxIa}/jour)");
                    break;
                }
                if ($feedType === 'techno' && $todayTech >= $maxTech) {
                    $this->line("  ⏸ Quota techno atteint ({$maxTech}/jour)");
                    break;
                }

                // DEDUP-SKIP : evite resume IA sur doublons cross-source. TOUJOURS actif, drapeau
                // fusion ou pas (correctif 1, revue adversariale 2026-08-09) - le désactiver
                // globalement quand fusion.enabled=true était une faille réelle : une
                // republication quasi identique d'un article SINGLETON déjà publié, arrivée à une
                // exécution ultérieure du cron, ne matche ni le lot du run en cours
                // (buildComponents ne voit que les candidats de CE passage) ni
                // findMatchingDigest() (qui ne cherche que des fiches comparatives) - elle aurait
                // été publiée en double.
                // MCP: SELF (<5 lignes)
                // RAISON: DEDUP-SKIP reste le seul filet de sécurité pour ce cas précis ; le
                // routage vers l'absorption ci-dessous (au lieu du skip) ne s'applique que si le
                // doublon détecté touche une fiche comparative ou un de ses membres.
                if (config('news.dedup_skip_enabled', true) && class_exists(\Modules\News\Services\DedupService::class)) {
                    $isDuplicate = false;
                    try {
                        $candidates = NewsArticle::where('id', '!=', $article->id)
                            ->where('news_source_id', '!=', $article->news_source_id)
                            ->where('created_at', '>=', now()->subDays(2))
                            ->whereNotNull('structured_summary')
                            ->get(['id', 'title', 'url', 'pub_date', 'news_source_id', 'is_comparative_digest', 'is_potential_duplicate_of', 'is_published']);
                        foreach ($candidates as $cand) {
                            $signals = [];
                            $check = \Modules\News\Services\DedupService::isLikelyDuplicate(
                                ['url' => $article->url, 'title' => $article->title, 'published_at' => $article->pub_date?->toIso8601String(), 'source_language' => $source->language],
                                ['url' => $cand->url, 'title' => $cand->title, 'published_at' => $cand->pub_date?->toIso8601String(), 'source_language' => $source->language],
                                $signals
                            );
                            if ($check['is_duplicate']) {
                                // ACTION: correctif 1 - si l'original matché est lui-même une
                                // fiche comparative, OU un membre d'une fiche comparative (on
                                // remonte alors au digest parent), on ABSORBE l'article dans
                                // cette fiche plutôt que de le dépublier silencieusement.
                                // MCP: SELF (<5 lignes)
                                // RAISON: DEDUP-SKIP d'origine ne connaissait pas le concept de
                                // fiche comparative ; sans ce routage une republication touchant
                                // un digest/membre était jetée au lieu d'enrichir la fiche.
                                $absorbInto = null;
                                if ($fusionEnabled) {
                                    $digestId = $cand->is_comparative_digest ? $cand->id : $cand->is_potential_duplicate_of;
                                    if ($digestId !== null) {
                                        $candidateDigest = NewsArticle::find($digestId);
                                        // Garde de cohérence : jamais absorber dans un article qui
                                        // n'est pas réellement un digest (vestige DEDUP-SKIP hypothétique).
                                        $absorbInto = ($candidateDigest && $candidateDigest->is_comparative_digest) ? $candidateDigest : null;
                                    }
                                }

                                if ($absorbInto) {
                                    $this->absorbFusionMember($article, $absorbInto, $feedType);
                                    Log::info(sprintf('FUSION-ABSORB (DEDUP-SKIP): article #%d "%s" rattaché à la fiche comparative #%d via republication détectée', $article->id, mb_substr($article->title, 0, 60), $absorbInto->id));
                                    $this->line("  ⊕ Republication absorbée dans la fiche comparative : {$article->title}");
                                } else {
                                    Log::info(sprintf('DEDUP-SKIP: article #%d "%s" doublon de #%d (score=%.3f, reason=%s) [IA evitee]', $article->id, mb_substr($article->title, 0, 60), $cand->id, $check['score'], $check['reason']));
                                    $article->update(['is_published' => false, 'summary' => '[doublon detecte - IA evitee]', 'feed_type' => $feedType]);
                                    $this->line("  ⊕ Doublon skip IA : {$article->title}");
                                    $totalFiltered++;
                                }
                                $isDuplicate = true;
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('DEDUP-SKIP error: ' . $e->getMessage());
                    }
                    if ($isDuplicate) { continue; }
                }

                // ACTION: Actus 2.0 - si le drapeau est actif, l'article rejoint le lot de
                // candidats à regrouper APRÈS la boucle de récupération (section 3.2 du design
                // doc) au lieu de partir immédiatement en résumé IA individuel.
                // MCP: SELF (<5 lignes)
                // RAISON: flag OFF => cette branche n'est jamais empruntée, chemin existant
                // (lignes suivantes) strictement inchangé.
                if ($fusionEnabled) {
                    $fusionCandidates[] = ['article' => $article, 'feedType' => $feedType];
                    continue;
                }

                // Score + résumé IA (1 seul appel)
                $result = $summarizer->scoreAndSummarize($article->title, $article->description, $source->language);

                if (! $result) {
                    $article->update(['summary' => '[échec IA]', 'feed_type' => $feedType]);
                    $this->warn("  ✗ Échec IA : {$article->title}");
                    continue;
                }

                $score = (int) ($result['score'] ?? 0);

                $article->update([
                    'relevance_score' => $score,
                    'score_justification' => $result['score_justification'] ?? null,
                    'structured_summary' => $result,
                    'category_tag' => $result['category'] ?? null,
                    'impact_level' => $result['impact'] ?? null,
                    'feed_type' => $feedType,
                    'seo_title' => \Illuminate\Support\Str::limit($result['seo_title'] ?? '', 250, ''),
                    'meta_description' => \Illuminate\Support\Str::limit($result['meta_description'] ?? '', 250, ''),
                    'summary' => $result['hook'] ?? null,
                    'is_published' => $score >= $minScore,
                ]);

                if ($score >= $minScore) {
                    $totalPublished++;
                    if ($feedType === 'ia') $todayIa++;
                    else $todayTech++;
                    $this->line("  ✓ [{$score}/10] {$article->title}");
                } else {
                    $totalFiltered++;
                    $this->line("  ⊘ [{$score}/10] Non pertinent : {$article->title}");
                }
            }
        }

        // ACTION: Actus 2.0 - traite en un seul passage les candidats différés (clustering,
        // absorption dans une fiche existante, appel IA groupe, ou chemin singleton inchangé).
        // MCP: SELF (orchestration, code métier ci-dessous)
        // RAISON: n'existe et ne s'exécute que si $fusionEnabled (jamais vide sinon).
        if ($fusionEnabled && $fusionCandidates !== []) {
            [$fusionPublished, $fusionFiltered] = $this->processFusionCandidates(
                $fusionCandidates,
                $summarizer,
                $minScore,
                $maxIa,
                $maxTech,
                $todayIa,
                $todayTech
            );
            $totalPublished += $fusionPublished;
            $totalFiltered += $fusionFiltered;
        }

        $this->info("--- Bilan : {$totalFetched} récupérés, {$totalPublished} publiés, {$totalFiltered} filtrés ---");

        return 0;
    }

    /**
     * ACTION : Actus 2.0 (design doc section 3.2/14) - regroupe les candidats différés puis
     * traite chaque issue du clustering : absorptions (zéro appel IA), singletons (chemin
     * scoreAndSummarize() existant, inchangé), nouveaux groupes (UN appel IA via
     * scoreAndSummarizeGroup() pour tout le groupe, quota d'indexation fixe appliqué).
     * MCP: SELF (orchestration métier, pas de génération de contenu)
     * RAISON: cœur du chantier Actus 2.0, ne s'exécute jamais quand le drapeau est désactivé.
     *
     * Écart d'implémentation documenté (section 14) : max_ia_articles_per_day/max_tech_articles_per_day
     * continuent de gouverner is_published (indépendant du quota d'indexation, section 5) mais
     * sont appliqués ici à l'échelle du GROUPE (feed_type du premier membre) plutôt que par
     * article individuel, puisqu'un groupe produit une seule décision de publication.
     *
     * @param  array<int, array{article: NewsArticle, feedType: string}>  $fusionCandidates
     * @return array{0: int, 1: int} [totalPublished, totalFiltered]
     */
    private function processFusionCandidates(
        array $fusionCandidates,
        AiSummaryService $summarizer,
        int $minScore,
        int $maxIa,
        int $maxTech,
        int $todayIa,
        int $todayTech
    ): array {
        $totalPublished = 0;
        $totalFiltered = 0;

        $clusteringService = app(ArticleClusteringService::class);
        $archiveService = app(ArchiveContextService::class);

        $articles = array_map(static fn (array $c) => $c['article'], $fusionCandidates);
        $feedTypeByArticleId = [];
        foreach ($fusionCandidates as $c) {
            $feedTypeByArticleId[$c['article']->id] = $c['feedType'];
        }

        $clusters = $clusteringService->cluster($articles);

        // 1) Absorptions : rattache aux fiches comparatives existantes SANS appel IA
        //    (arbitrage section 14 : même mécanisme pour « 2e fiche le même jour » et
        //    « article tardif après création de la fiche »).
        foreach ($clusters['absorptions'] as $absorption) {
            $digest = $absorption['digest'];
            foreach ($absorption['members'] as $member) {
                $this->absorbFusionMember($member, $digest, $feedTypeByArticleId[$member->id] ?? 'techno');
            }
            Log::info(sprintf(
                'FUSION-ABSORB: %d article(s) rattaché(s) à la fiche comparative existante #%d "%s"',
                count($absorption['members']),
                $digest->id,
                mb_substr((string) $digest->title, 0, 60)
            ));
        }

        // 2) Singletons : chemin IA existant, strictement inchangé (mêmes champs, même logique
        //    de quota is_published que le chemin non-fusion ci-dessus).
        foreach ($clusters['singletons'] as $article) {
            $feedType = $feedTypeByArticleId[$article->id] ?? 'techno';

            $result = $summarizer->scoreAndSummarize($article->title, $article->description, $article->source->language ?? 'fr');

            if (! $result) {
                $article->update(['summary' => '[échec IA]', 'feed_type' => $feedType]);
                $this->warn("  ✗ Échec IA : {$article->title}");
                continue;
            }

            $score = (int) ($result['score'] ?? 0);

            $article->update([
                'relevance_score' => $score,
                'score_justification' => $result['score_justification'] ?? null,
                'structured_summary' => $result,
                'category_tag' => $result['category'] ?? null,
                'impact_level' => $result['impact'] ?? null,
                'feed_type' => $feedType,
                'seo_title' => Str::limit($result['seo_title'] ?? '', 250, ''),
                'meta_description' => Str::limit($result['meta_description'] ?? '', 250, ''),
                'summary' => $result['hook'] ?? null,
                'is_published' => $score >= $minScore,
            ]);

            if ($score >= $minScore) {
                $totalPublished++;
                $feedType === 'ia' ? $todayIa++ : $todayTech++;
                $this->line("  ✓ [{$score}/10] {$article->title}");
            } else {
                $totalFiltered++;
                $this->line("  ⊘ [{$score}/10] Non pertinent : {$article->title}");
            }
        }

        // 3) Nouveaux groupes (2+) : le plus gros groupe d'abord, à égalité le plus ancien
        //    pub_date (section 5 - jamais un score IA comme filtre de quota d'indexation).
        $orderedGroups = $clusters['new_groups'];
        usort($orderedGroups, static function (array $a, array $b): int {
            $sizeDiff = count($b) <=> count($a);
            if ($sizeDiff !== 0) {
                return $sizeDiff;
            }

            $oldestA = collect($a)->min(fn (NewsArticle $article) => $article->pub_date?->timestamp ?? PHP_INT_MAX);
            $oldestB = collect($b)->min(fn (NewsArticle $article) => $article->pub_date?->timestamp ?? PHP_INT_MAX);

            return $oldestA <=> $oldestB;
        });

        $maxIndexedDigests = (int) Settings::get(
            'news.fusion.max_indexed_digests_per_day',
            config('news.fusion.max_indexed_digests_per_day', 5)
        );
        $todayIndexedDigests = NewsArticle::where('is_comparative_digest', true)
            ->where('seo_status', 'index')
            ->whereDate('created_at', today())
            ->count();

        foreach ($orderedGroups as $group) {
            $feedType = $feedTypeByArticleId[$group[0]->id] ?? 'techno';

            if ($feedType === 'ia' && $todayIa >= $maxIa) {
                continue;
            }
            if ($feedType === 'techno' && $todayTech >= $maxTech) {
                continue;
            }

            usort($group, static fn (NewsArticle $a, NewsArticle $b) => ($a->pub_date?->timestamp ?? PHP_INT_MAX) <=> ($b->pub_date?->timestamp ?? PHP_INT_MAX));
            $digestArticle = $group[0];
            $members = array_slice($group, 1);

            $archiveContext = $archiveService->findRelevant(
                array_map(static fn (NewsArticle $a) => $a->title, $group),
                $digestArticle->id
            );

            $result = $summarizer->scoreAndSummarizeGroup(
                array_map(static fn (NewsArticle $a) => [
                    'title' => $a->title,
                    'url' => $a->resolved_url ?: $a->url,
                    'author' => $a->author,
                    'source_name' => $a->source->name ?? null,
                    'text' => $a->description,
                ], $group),
                $archiveContext
            );

            if (! $result) {
                foreach ($group as $a) {
                    $a->update(['summary' => '[échec IA groupe]', 'feed_type' => $feedTypeByArticleId[$a->id] ?? $feedType]);
                }
                $this->warn('  ✗ Échec IA groupe : '.$digestArticle->title);
                continue;
            }

            $score = (int) ($result['score'] ?? 0);
            $isPublished = $score >= $minScore;
            $indexed = $isPublished && $todayIndexedDigests < $maxIndexedDigests;

            $digestUpdate = [
                'relevance_score' => $score,
                'score_justification' => $result['score_justification'] ?? null,
                'structured_summary' => $result,
                'category_tag' => $result['category'] ?? null,
                'impact_level' => $result['impact'] ?? null,
                'feed_type' => $feedType,
                'seo_title' => Str::limit($result['seo_title'] ?? '', 250, ''),
                'meta_description' => Str::limit($result['meta_description'] ?? '', 250, ''),
                'summary' => $result['hook'] ?? null,
                'is_published' => $isPublished,
                'is_comparative_digest' => true,
            ];
            // seo_status ne suit le quota QUE si la fiche est publiée (article 5 : le quota
            // gouverne l'indexation, jamais la publication) - sinon on n'y touche pas, comme
            // le chemin singleton qui laisse seo_status à sa valeur par défaut ('index').
            if ($isPublished) {
                $digestUpdate['seo_status'] = $indexed ? 'index' : 'noindex';
            }

            $digestArticle->update($digestUpdate);

            foreach ($members as $member) {
                $signals = [];
                $clusterResult = DedupService::isSameStoryCluster(
                    ['title' => $digestArticle->title],
                    ['title' => $member->title],
                    $signals
                );
                $this->attachFusionMember(
                    $member,
                    $digestArticle,
                    $feedTypeByArticleId[$member->id] ?? $feedType,
                    $isPublished,
                    $clusterResult['score'],
                    $clusterResult['reason'] !== 'none' ? $clusterResult['reason'] : 'multi_core'
                );
            }

            if ($isPublished) {
                $totalPublished++;
                $feedType === 'ia' ? $todayIa++ : $todayTech++;
                if ($indexed) {
                    $todayIndexedDigests++;
                }
                $this->line("  ✓ [{$score}/10] Fiche comparative ({$this->pluralizeGroupSize(count($group))}) : {$digestArticle->title}");
            } else {
                $totalFiltered++;
                $this->line("  ⊘ [{$score}/10] Groupe non pertinent : {$digestArticle->title}");
            }

            Log::info(sprintf(
                'FUSION-GROUP: fiche comparative #%d "%s" créée depuis %d source(s), score=%d, publiée=%s, indexée=%s',
                $digestArticle->id,
                mb_substr((string) $digestArticle->title, 0, 60),
                count($group),
                $score,
                $isPublished ? 'oui' : 'non',
                $indexed ? 'oui' : 'non'
            ));
        }

        return [$totalPublished, $totalFiltered];
    }

    /**
     * ACTION : rattache un membre à une fiche comparative NOUVELLEMENT créée dans ce passage.
     * MCP: SELF (<5 lignes utiles, écritures DB directes)
     * RAISON: réutilisée par le chemin groupe ; distincte de absorbFusionMember() car le score
     * de similarité est déjà calculé par l'appelant (évite un doublon de calcul).
     */
    private function attachFusionMember(NewsArticle $member, NewsArticle $digest, string $feedType, bool $digestPublished, float $score, string $reason): void
    {
        $member->update([
            'feed_type' => $feedType,
            'is_potential_duplicate_of' => $digest->id,
            'dedup_score' => $score,
            'dedup_reason' => $reason,
            'seo_status' => 'noindex',
            'is_published' => $digestPublished,
        ]);

        NewsDedupLog::create([
            'new_article_id' => $digest->id,
            'matched_article_id' => $member->id,
            'score' => $score,
            'reason' => $reason,
            'signals' => ['fusion' => true],
            'action' => 'fusion_grouped',
        ]);
    }

    /**
     * ACTION : rattache un membre à une fiche comparative EXISTANTE (arbitrage absorption,
     * section 14) - jamais de régénération du texte de la fiche, zéro appel IA additionnel.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: mécanisme unique pour « 2e fiche le même jour » et « article tardif ».
     *
     * Correctif 2 (revue adversariale 2026-08-09) : updateQuietly() plutôt que update(). Un
     * membre absorbé passe souvent is_published false→true (il hérite du statut du digest), ce
     * qui déclenchait NewsArticleObserver::updated() - createShortUrlIfNeeded() (lien court
     * inutile pour une page satellite noindex) et dispatchAutoToolDetection() (job de détection
     * d'outils sur une page qui n'a pas vocation à être découverte). Vérifié : le seul autre
     * listener sur l'événement 'updated' (NewsArticle::booted(), ContentPublished) exige déjà
     * category_tag, jamais renseigné sur un membre - rien d'indispensable n'est perdu ici.
     */
    private function absorbFusionMember(NewsArticle $member, NewsArticle $digest, string $feedType): void
    {
        $signals = [];
        $result = DedupService::isSameStoryCluster(
            ['title' => $digest->title],
            ['title' => $member->title],
            $signals
        );
        $reason = $result['reason'] !== 'none' ? $result['reason'] : 'multi_core';

        $member->updateQuietly([
            'feed_type' => $feedType,
            'is_potential_duplicate_of' => $digest->id,
            'dedup_score' => $result['score'],
            'dedup_reason' => $reason,
            'seo_status' => 'noindex',
            'is_published' => (bool) $digest->is_published,
        ]);

        NewsDedupLog::create([
            'new_article_id' => $digest->id,
            'matched_article_id' => $member->id,
            'score' => $result['score'],
            'reason' => $reason,
            'signals' => ['fusion' => true, 'absorption' => true],
            'action' => 'fusion_grouped',
        ]);
    }

    private function pluralizeGroupSize(int $size): string
    {
        return $size.' '.($size > 1 ? 'sources' : 'source');
    }

    private function detectFeedType(NewsSource $source): string
    {
        $url = mb_strtolower($source->url);
        $name = mb_strtolower($source->name);

        if (str_contains($url, 'intelligence+artificielle') || str_contains($url, 'ai-artificial')
            || str_contains($name, 'ia') || str_contains($name, ' ai')) {
            return 'ia';
        }

        return 'techno';
    }
}
