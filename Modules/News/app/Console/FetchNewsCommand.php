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
use Modules\News\Services\ContentExtractor;
use Modules\News\Services\DedupService;
use Modules\News\Services\RssFetcherService;
use Modules\Settings\Facades\Settings;

class FetchNewsCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'news:fetch {--source= : ID source spécifique} {--force : Forcer même si kill switch actif}';

    protected $description = 'Récupère les articles RSS, score et génère les résumés structurés IA.';

    // ACTION : drapeau maître de publication automatique (2026-08-14), lu une seule fois en
    // début de handle() et conservé pour toute la durée de l'exécution - jamais un config() par
    // point d'écriture. Consommé exclusivement par resolvePublicationState().
    // MCP: SELF (<5 lignes)
    // RAISON: propriété plutôt que paramètre thread à travers processFusionCandidates() - la
    // collecte, le scoring et la fusion restent inchangés, seule l'écriture is_published change.
    private bool $autopublishEnabled = false;

    // ACTION : compteur diagnostic SÉPARÉ (2026-08-14, correctif effet de bord round 1) - nombre
    // d'articles/groupes qui ATTEIGNENT le seuil de pertinence mais restent non publiés parce
    // que le drapeau est désactivé. Jamais mélangé à totalPublished/totalFiltered : incrémenté
    // uniquement par resolvePublicationState(), affiché dans le bilan seulement si le drapeau
    // est désactivé (sinon toujours à zéro, aucun intérêt à l'afficher).
    // MCP: SELF (<5 lignes)
    // RAISON: distinguer « refusé pour manque de pertinence » (totalFiltered, inchangé) de
    // « pertinent mais retenu par la politique de publication » - deux causes différentes.
    private int $totalEligibleNonPublies = 0;

    // ACTION : drapeau maître de génération machine des résumés (2026-08-17, décision du
    // fondateur), lu une seule fois en début de handle() - même doctrine que
    // $autopublishEnabled ci-dessus (propriété plutôt que paramètre thread à travers
    // processFusionCandidates()).
    // MCP: SELF (<5 lignes)
    // RAISON: OFF (défaut) = les 3 points d'appel à AiSummaryService::scoreAndSummarize/
    // scoreAndSummarizeGroup ne sont jamais atteints - la collecte, le pré-filtre mots-clés et
    // la déduplication restent inchangés.
    private bool $machineSummaryEnabled = false;

    // ACTION : compteur diagnostic (2026-08-17) - nombre d'articles/groupes collectés SANS
    // résumé machine parce que le drapeau est désactivé. Distinct de totalFiltered (non
    // pertinent) et de totalEligibleNonPublies (pertinent mais publication suspendue) : ici
    // aucune pertinence n'a même été évaluée par IA.
    // MCP: SELF (<5 lignes)
    // RAISON: alimente la ligne de bilan « résumés machine : désactivés ».
    private int $totalMachineSummarySkipped = 0;

    public function handle(RssFetcherService $fetcher, AiSummaryService $summarizer): int
    {
        if ($this->shouldSkipForKillSwitch('cron.news-fetch')) {
            return self::SUCCESS;
        }

        $this->autopublishEnabled = (bool) config('news.autopublish.enabled', false);
        $this->machineSummaryEnabled = (bool) config('news.machine_summary.enabled', false);

        if (! $this->autopublishEnabled) {
            // ACTION : ligne de journalisation obligatoire (spec 2026-08-14) - canal dédié 'fusion'
            // (config/logging.php), niveau fixé en dur à 'info', indépendant de LOG_LEVEL=error en
            // production (sinon cette ligne serait jetée avant écriture, piège déjà documenté 3 fois
            // sur ce projet).
            // MCP: SELF (<5 lignes)
            // RAISON: rend visible en prod que la collecte continue mais que la publication est
            // suspendue - sans quoi un run "normal en apparence" masquerait la décision du fondateur.
            Log::channel('fusion')->info('AUTOPUBLISH-OFF: publication automatique suspendue - les articles sont collectés en brouillon (is_published=false).');
        }

        if (! $this->machineSummaryEnabled) {
            // ACTION : même pattern qu'AUTOPUBLISH-OFF ci-dessus - canal dédié 'fusion', niveau
            // fixé en dur à 'info', indépendant de LOG_LEVEL=error en production.
            // MCP: SELF (<5 lignes)
            // RAISON: rend visible en prod que la génération machine des résumés est
            // désactivée (décision du fondateur 2026-08-17) - sans cette ligne, un run
            // "normal en apparence" masquerait qu'aucun texte d'article n'est envoyé au
            // fournisseur de modèle pendant la collecte.
            Log::channel('fusion')->info('MACHINE-SUMMARY-OFF: génération machine des résumés désactivée - le contenu vient exclusivement du flux /actu2 ; la collecte continue (titres/liens/dédup/pertinence mots-clés), structured_summary reste null, aucun texte d\'article n\'est envoyé au fournisseur de modèle.');
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

        // ACTION : le texte source ne transite plus jamais par la colonne description (design
        // doc "Actus - zéro copie du texte source", 2026-08-13, section 4.1) - RssFetcherService
        // le retourne par article, on le garde ici en mémoire pour CETTE exécution et on le
        // passe explicitement en argument au service de résumé plus bas.
        // MCP: SELF (<5 lignes utiles)
        // RAISON: aucune propriété du modèle ne doit servir de véhicule au texte source.
        $textsByArticleId = [];

        foreach ($sources as $source) {
            $this->info("Récupération : {$source->name}");
            $fetchResult = $fetcher->fetchSource($source);
            $fetched = $fetchResult['count'];
            $totalFetched += $fetched;
            $textsByArticleId += $fetchResult['texts'];
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
                // ACTION : texte source de CET article - en mémoire depuis cette exécution, sinon
                // re-téléchargé (design doc section 4.1 : « toute reprise exige de
                // re-télécharger »). Jamais lu depuis $article->description.
                // MCP: SELF (<5 lignes utiles, appel à resolveArticleText())
                // RAISON: couvre l'article déjà créé lors d'une exécution précédente (quota
                // atteint, jamais résumé) - son GUID existe déjà, il ne repasse jamais par
                // RssFetcherService::fetchSource().
                $text = $this->resolveArticleText($article, $textsByArticleId);

                // Pré-filtre mots-clés (gratuit)
                if (! $summarizer->isRelevant($article->title, $text)) {
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
                                    Log::channel('fusion')->info(sprintf('FUSION-ABSORB (DEDUP-SKIP): article #%d "%s" rattaché à la fiche comparative #%d via republication détectée', $article->id, mb_substr($article->title, 0, 60), $absorbInto->id));
                                    $this->line("  ⊕ Republication absorbée dans la fiche comparative : {$article->title}");
                                } else {
                                    Log::channel('fusion')->info(sprintf('DEDUP-SKIP: article #%d "%s" doublon de #%d (score=%.3f, reason=%s) [IA evitee]', $article->id, mb_substr($article->title, 0, 60), $cand->id, $check['score'], $check['reason']));
                                    $article->update(['is_published' => false, 'summary' => '[doublon detecte - IA evitee]', 'feed_type' => $feedType]);
                                    $this->line("  ⊕ Doublon skip IA : {$article->title}");
                                    $totalFiltered++;
                                }
                                $isDuplicate = true;
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::channel('fusion')->warning('DEDUP-SKIP error: ' . $e->getMessage());
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
                    $fusionCandidates[] = ['article' => $article, 'feedType' => $feedType, 'text' => $text];
                    continue;
                }

                // ACTION : génération machine du résumé éteinte (2026-08-17, décision du
                // fondateur) - article collecté normalement (titre, url, dédup, pertinence
                // mots-clés déjà passés ci-dessus) mais structured_summary reste null et AUCUN
                // appel au fournisseur de modèle n'a lieu.
                // MCP: SELF (<5 lignes, garde de configuration)
                // RAISON: le contenu des fiches vient désormais exclusivement du flux /actu2.
                if (! $this->machineSummaryEnabled) {
                    $article->update(['feed_type' => $feedType, 'is_published' => false]);
                    $this->totalMachineSummarySkipped++;
                    $this->line("  ⏭ Résumé machine désactivé (flux /actu2 uniquement) : {$article->title}");
                    continue;
                }

                // Score + résumé IA (1 seul appel) - pub_date transmise pour le contrôle de
                // cohérence des années de SummaryQualityGate (2026-08-13).
                $result = $summarizer->scoreAndSummarize($article->title, $text, $source->language, $article->pub_date);

                if (! $result) {
                    $article->update(['summary' => '[échec IA]', 'feed_type' => $feedType]);
                    $this->warn("  ✗ Échec IA : {$article->title}");
                    continue;
                }

                $score = (int) ($result['score'] ?? 0);
                // ACTION : correctif 2026-08-14 (effet de bord round 1) - $wouldPublish et
                // $published sont désormais deux variables distinctes ; TOUS les consommateurs
                // (compteurs, quotas quotidiens, ligne console) lisent $published (l'état RÉEL
                // écrit en base), plus jamais $score >= $minScore brut. Sinon les quotas
                // journaliers seraient entamés par des brouillons jamais publiés - piège différé
                // au jour où la publication sera réactivée.
                // MCP: SELF (<5 lignes)
                // RAISON: demande explicite du superviseur - le bilan ne doit jamais annoncer une
                // publication qui n'a pas eu lieu.
                $wouldPublish = $score >= $minScore;
                $published = $this->resolvePublicationState($wouldPublish);

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
                    'is_published' => $published,
                ]);

                if ($published) {
                    $totalPublished++;
                    if ($feedType === 'ia') $todayIa++;
                    else $todayTech++;
                    $this->line("  ✓ [{$score}/10] {$article->title}");
                } elseif ($wouldPublish) {
                    // Pertinent (score >= seuil) mais retenu par le drapeau, jamais compté dans
                    // totalFiltered (réservé aux articles réellement sous le seuil) ni dans les
                    // quotas quotidiens is_published-only.
                    $this->line("  ⏳ [{$score}/10] Collecté en brouillon (publication suspendue) : {$article->title}");
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

        // ACTION : correctif 2026-08-14 (effet de bord round 1) - segment additionnel du bilan,
        // affiché UNIQUEMENT quand le drapeau est désactivé (sinon $totalEligibleNonPublies
        // reste toujours à zéro, rien à afficher). Distinct de "filtrés" (non pertinents).
        // MCP: SELF (<5 lignes)
        // RAISON: demande explicite du superviseur - le bilan doit dire la vérité sur ce qui
        // s'est réellement passé, jamais laisser croire qu'un brouillon a été publié.
        $bilan = "--- Bilan : {$totalFetched} récupérés, {$totalPublished} publiés, {$totalFiltered} filtrés";
        if (! $this->autopublishEnabled && $this->totalEligibleNonPublies > 0) {
            $bilan .= ", {$this->totalEligibleNonPublies} admissibles non publiés (drapeau désactivé)";
        }
        // ACTION : segment de bilan obligatoire (2026-08-17, décision du fondateur) - affiché
        // dès que le drapeau est désactivé, même si $totalMachineSummarySkipped vaut 0 (aucun
        // article éligible ce run) : c'est un état de configuration, pas seulement un
        // compteur.
        // MCP: SELF (<5 lignes)
        // RAISON: rend visible en sortie de commande que la génération machine est éteinte,
        // même en lecture rapide du run.
        if (! $this->machineSummaryEnabled) {
            $bilan .= ", résumés machine : désactivés ({$this->totalMachineSummarySkipped} article(s)/groupe(s) collecté(s) sans résumé)";
        }
        $bilan .= ' ---';
        $this->info($bilan);

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
     * @param  array<int, array{article: NewsArticle, feedType: string, text: string}>  $fusionCandidates
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
        // ACTION : texte source par article, gardé en mémoire depuis la boucle de récupération
        // (design doc section 4.1) - jamais relu depuis $article->description ci-dessous.
        // MCP: SELF (<5 lignes utiles)
        // RAISON: alimente le chemin singleton ET le chemin groupe de cette même méthode.
        $textsByArticleId = [];
        foreach ($fusionCandidates as $c) {
            $feedTypeByArticleId[$c['article']->id] = $c['feedType'];
            $textsByArticleId[$c['article']->id] = $c['text'] ?? '';
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
            Log::channel('fusion')->info(sprintf(
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

            // ACTION : génération machine du résumé éteinte (2026-08-17, décision du fondateur)
            // - même garde que le chemin non-fusion, voir plus haut dans handle().
            // MCP: SELF (<5 lignes, garde de configuration)
            // RAISON: DRY - un seul comportement pour les deux chemins singleton.
            if (! $this->machineSummaryEnabled) {
                $article->update(['feed_type' => $feedType, 'is_published' => false]);
                $this->totalMachineSummarySkipped++;
                $this->line("  ⏭ Résumé machine désactivé (flux /actu2 uniquement) : {$article->title}");
                continue;
            }

            $result = $summarizer->scoreAndSummarize($article->title, $textsByArticleId[$article->id] ?? '', $article->source->language ?? 'fr', $article->pub_date);

            if (! $result) {
                $article->update(['summary' => '[échec IA]', 'feed_type' => $feedType]);
                $this->warn("  ✗ Échec IA : {$article->title}");
                continue;
            }

            $score = (int) ($result['score'] ?? 0);
            // ACTION : correctif 2026-08-14 (effet de bord round 1) - même parade que le chemin
            // non-fusion : $published (état réel écrit) alimente seul compteurs/quotas/console,
            // jamais $score >= $minScore brut.
            // MCP: SELF (<5 lignes)
            // RAISON: demande explicite du superviseur.
            $wouldPublish = $score >= $minScore;
            $published = $this->resolvePublicationState($wouldPublish);

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
                'is_published' => $published,
            ]);

            if ($published) {
                $totalPublished++;
                $feedType === 'ia' ? $todayIa++ : $todayTech++;
                $this->line("  ✓ [{$score}/10] {$article->title}");
            } elseif ($wouldPublish) {
                $this->line("  ⏳ [{$score}/10] Collecté en brouillon (publication suspendue) : {$article->title}");
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

            // ACTION : génération machine du résumé éteinte (2026-08-17, décision du fondateur)
            // - même garde que les chemins singleton ci-dessus, appliquée AVANT le calcul du
            // contexte d'archives (évite un travail inutile puisqu'aucun appel IA groupe n'aura
            // lieu). Chaque membre du groupe reste collecté normalement, feed_type renseigné,
            // is_published=false ; aucun n'est fusionné avec un digest (l'écriture des relations
            // de fusion dépend elle-même d'un digest résumé, jamais créé ici).
            // MCP: SELF (<5 lignes, garde de configuration)
            // RAISON: DRY - même comportement que les chemins singleton, adapté au groupe.
            if (! $this->machineSummaryEnabled) {
                foreach ($group as $groupedArticle) {
                    $groupedArticle->update([
                        'feed_type' => $feedTypeByArticleId[$groupedArticle->id] ?? $feedType,
                        'is_published' => false,
                    ]);
                }
                $this->totalMachineSummarySkipped += count($group);
                $this->line('  ⏭ Résumé machine désactivé (flux /actu2 uniquement) : groupe de '.count($group).' article(s) : '.$digestArticle->title);
                continue;
            }

            $archiveContext = $archiveService->findRelevant(
                array_map(static fn (NewsArticle $a) => $a->title, $group),
                $digestArticle->id
            );

            // Référence temporelle du groupe = pub_date la plus récente parmi ses membres, pour
            // le contrôle de cohérence des années de SummaryQualityGate (2026-08-13) - la
            // synthèse porte sur le développement le plus récent du sujet couvert.
            $groupReferenceDate = collect($group)->max(fn (NewsArticle $a) => $a->pub_date);

            $result = $summarizer->scoreAndSummarizeGroup(
                array_map(static fn (NewsArticle $a) => [
                    'title' => $a->title,
                    'url' => $a->resolved_url ?: $a->url,
                    'author' => $a->author,
                    'source_name' => $a->source->name ?? null,
                    'text' => $textsByArticleId[$a->id] ?? '',
                ], $group),
                $archiveContext,
                'fr',
                $groupReferenceDate
            );

            if (! $result) {
                foreach ($group as $a) {
                    $a->update(['summary' => '[échec IA groupe]', 'feed_type' => $feedTypeByArticleId[$a->id] ?? $feedType]);
                }
                $this->warn('  ✗ Échec IA groupe : '.$digestArticle->title);
                continue;
            }

            $score = (int) ($result['score'] ?? 0);
            $wouldPublish = $score >= $minScore;
            $isPublished = $this->resolvePublicationState($wouldPublish);
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
            } elseif ($wouldPublish) {
                $this->line("  ⏳ [{$score}/10] Groupe collecté en brouillon (publication suspendue) : {$digestArticle->title}");
            } else {
                $totalFiltered++;
                $this->line("  ⊘ [{$score}/10] Groupe non pertinent : {$digestArticle->title}");
            }

            Log::channel('fusion')->info(sprintf(
                'FUSION-GROUP: fiche comparative #%d "%s" créée depuis %d source(s), score=%d, publiée=%s, indexée=%s',
                $digestArticle->id,
                mb_substr((string) $digestArticle->title, 0, 60),
                count($group),
                $score,
                $isPublished ? 'oui' : 'non',
                $indexed ? 'oui' : 'non'
            ));
        }

        $this->logFusionRunSynthesis(count($fusionCandidates), $clusters);

        return [$totalPublished, $totalFiltered];
    }

    /**
     * ACTION : Actus 2.0 - trace de synthèse de CHAQUE exécution du clustering (une ligne),
     * puis au plus 3 lignes de « quasi-regroupements » (paires/absorptions refusées dont le
     * score était proche du seuil - déjà bornées à 3 par ArticleClusteringService::cluster()).
     * MCP: SELF (<5 lignes utiles, formatage de log)
     * RAISON: rend le clustering observable en prod (canal 'fusion', voir config/logging.php)
     * sans jamais journaliser une ligne par paire comparée - volume borné, indépendant du nombre
     * d'articles traités (voir docblock de ArticleClusteringService::cluster()).
     *
     * @param  array{new_groups: array<int, array<int, NewsArticle>>, singletons: array<int, NewsArticle>, absorptions: array<int, array{digest: NewsArticle, members: array<int, NewsArticle>}>, near_misses: array{total: int, top: array<int, array<string, mixed>>}}  $clusters
     */
    private function logFusionRunSynthesis(int $totalArticles, array $clusters): void
    {
        $absorbedCount = array_sum(array_map(
            static fn (array $absorption) => count($absorption['members']),
            $clusters['absorptions']
        ));

        Log::channel('fusion')->info(sprintf(
            'FUSION-SYNTHESE: %d article(s) fenêtre, %d groupe(s), %d singleton(s), %d absorption(s), %d quasi-regroupement(s)',
            $totalArticles,
            count($clusters['new_groups']),
            count($clusters['singletons']),
            $absorbedCount,
            $clusters['near_misses']['total']
        ));

        foreach ($clusters['near_misses']['top'] as $nearMiss) {
            Log::channel('fusion')->info(sprintf(
                'FUSION-QUASI: "%s" vs "%s" - jaccard=%.3f/%.2f, entités=%d/%d, motif=%s',
                $nearMiss['title_a'],
                $nearMiss['title_b'],
                $nearMiss['jaccard'],
                $nearMiss['jaccard_threshold'],
                $nearMiss['entity_overlap'],
                $nearMiss['entity_threshold'],
                $nearMiss['reason']
            ));
        }
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

    /**
     * ACTION : point d'écriture UNIQUE de la décision de publication (2026-08-14) - appelé aux
     * 4 endroits qui calculent fraîchement un statut de publication (chemin non-fusion, chemin
     * fusion singleton, fiche comparative/digest, membre rattaché à un digest nouvellement créé).
     * Le 5e endroit historique (absorbFusionMember, republication absorbée dans une fiche
     * comparative EXISTANTE) hérite déjà de $digest->is_published, une valeur elle-même
     * résolue par cette méthode au moment de la création du digest - aucun appel supplémentaire
     * n'y est nécessaire.
     * MCP: SELF (<5 lignes)
     * RAISON: DRY strict (consigne explicite) - une seule méthode plutôt que 5 conditions
     * copiées-collées ; le scoring, la porte de qualité et la fusion restent des décisions
     * indépendantes de ce drapeau, qui ne fait que court-circuiter l'écriture finale.
     *
     * Correctif 2026-08-14 (effet de bord round 1) : incrémente aussi le compteur diagnostic
     * $totalEligibleNonPublies quand un article/groupe ATTEINT le seuil mais reste non publié à
     * cause du drapeau - jamais l'inverse (score insuffisant), qui reste compté ailleurs comme
     * "filtré". Centralisé ici : les 3 sites appelants (chemin non-fusion, fusion-singleton,
     * fiche comparative) héritent du comptage correct sans le dupliquer.
     */
    private function resolvePublicationState(bool $wouldPublish): bool
    {
        $published = $wouldPublish && $this->autopublishEnabled;

        if ($wouldPublish && ! $published) {
            $this->totalEligibleNonPublies++;
        }

        return $published;
    }

    /**
     * ACTION : texte source pour le scoring de CET article - jamais lu depuis
     * $article->description (design doc "Actus - zéro copie du texte source", 2026-08-13,
     * section 4.1). Priorité au texte déjà extrait CETTE exécution (fourni par
     * RssFetcherService::fetchSource()) ; à défaut, re-téléchargé à la volée.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: bloc réutilisable UNIQUE pour ce besoin - seul appelant du couple
     * ContentExtractor::extract()/résolution d'URL pour un article déjà en base dans cette
     * commande (le chemin singleton, les candidats de fusion et les groupes s'y alimentent
     * tous via $textsByArticleId, jamais une deuxième implémentation).
     *
     * @param  array<int, string>  $textsByArticleId
     */
    private function resolveArticleText(NewsArticle $article, array $textsByArticleId): string
    {
        if (isset($textsByArticleId[$article->id])) {
            return $textsByArticleId[$article->id];
        }

        $url = $article->resolved_url ?: $article->url;
        $extracted = app(ContentExtractor::class)->extract($url);

        return $extracted['content'] ?? '';
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
