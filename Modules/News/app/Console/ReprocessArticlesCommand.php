<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\ContentExtractor;
use Modules\News\Services\GoogleNewsResolver;
use Modules\News\Services\NewsImageService;

class ReprocessArticlesCommand extends Command
{
    protected $signature = 'news:reprocess {--google-only : Uniquement les articles Google News} {--unresolved-only : Uniquement ceux sans resolved_url} {--limit=10 : Nombre max d\'articles} {--dry-run}';

    protected $description = 'Re-traiter les articles News existants avec le nouveau pipeline';

    public function handle(): int
    {
        // ACTION : le reprocess est un outil du pipeline MACHINE - il ne touche JAMAIS les
        // fiches de soumission manuelle ni les fiches composées par /actu2 (incident 2026-08-18 :
        // la photo générée de la fiche 33558 a été écrasée par la vignette de marque).
        // MCP: SELF (<5 lignes utiles)
        // RAISON: les fiches curatées appartiennent au flux /actu2, jamais au pipeline machine.
        $query = NewsArticle::with('source')
            ->whereDoesntHave('source', fn ($q) => $q->where('url', 'manuel://soumission-directe'))
            ->where(function ($q) {
                $q->whereNull('structured_summary')
                    ->orWhere('structured_summary', 'not like', '%"composed":true%');
            })
            ->orderByDesc('id')
            ->limit((int) $this->option('limit'));

        if ($this->option('google-only')) {
            $googleSourceIds = NewsSource::where('name', 'like', '%Google%')->pluck('id');
            $query->whereIn('news_source_id', $googleSourceIds);
        }

        if ($this->option('unresolved-only')) {
            $query->whereNull('resolved_url');
        }

        $articles = $query->get();
        $this->info("Articles a traiter : {$articles->count()}");

        $updated = 0;
        $errors = 0;

        foreach ($articles as $article) {
            $this->line("[{$article->id}] {$article->title}");

            // Résolution Google News
            $resolvedUrl = $article->resolved_url;
            if (GoogleNewsResolver::isGoogleNewsUrl($article->url) && ! $resolvedUrl) {
                $resolvedUrl = app(GoogleNewsResolver::class)->resolve($article->url);
                if ($resolvedUrl && ! GoogleNewsResolver::isGoogleNewsUrl($resolvedUrl) && ! $this->option('dry-run')) {
                    $article->update(['resolved_url' => $resolvedUrl]);
                }
                $this->line("  resolved: " . ($resolvedUrl && ! GoogleNewsResolver::isGoogleNewsUrl($resolvedUrl) ? Str::limit($resolvedUrl, 60) : 'FAIL'));
            }

            // Extraction contenu
            $extractUrl = $resolvedUrl ?? $article->url;
            $extracted = app(ContentExtractor::class)->extract($extractUrl);

            if (! $extracted) {
                $this->warn("  extraction FAIL");
                $errors++;

                continue;
            }

            $this->line("  content: {$extracted['word_count']} mots, image: " . ($extracted['image'] ? 'OUI' : 'NON'));

            if ($this->option('dry-run')) {
                $updated++;

                continue;
            }

            $updateData = [];

            // Image : télécharger si disponible - JAMAIS par-dessus une image curatée
            // (posée avec crédit par la porte news:apply --image), défense en profondeur.
            if ($extracted['image'] && ! $article->hasCuratedImage()) {
                $newImage = app(NewsImageService::class)->processFromUrl($extracted['image'], $article->id);
                if ($newImage) {
                    $updateData['image_url'] = $newImage;
                    $this->line("  image: OK");
                }
            }

            // ACTION : le texte extrait n'est plus jamais écrit dans description (design doc
            // "Actus - zéro copie du texte source", 2026-08-13, section 4.1) - il reste en
            // mémoire ($extracted['content']) et transite uniquement en argument vers le
            // service de résumé ci-dessous.
            // MCP: SELF (<5 lignes, suppression)
            // RAISON: la colonne ne doit jamais véhiculer le texte source, même partiellement.

            // Résumé IA si contenu suffisant
            if ($extracted['word_count'] > 200) {
                // ACTION : génération machine du résumé éteinte (2026-08-17, décision du
                // fondateur) - refus EXPLICITE plutôt qu'un échec silencieux : le reste du
                // pipeline de cette commande (résolution Google News, image) continue d'agir
                // normalement, seul le résumé structuré est refusé.
                // MCP: SELF (<5 lignes, garde de configuration)
                // RAISON: le contenu des fiches vient désormais exclusivement du flux /actu2 ;
                // aucun chemin ne doit pouvoir régénérer un résumé machine drapeau éteint.
                if (! (bool) config('news.machine_summary.enabled', false)) {
                    $this->warn('  résumé machine REFUSÉ (drapeau news.machine_summary.enabled désactivé - flux /actu2 uniquement)');
                } else {
                    // pub_date transmise pour le contrôle de cohérence des années de
                    // SummaryQualityGate (2026-08-13).
                    $aiResult = app(AiSummaryService::class)->scoreAndSummarize(
                        $extracted['title'] ?: $article->title,
                        $extracted['content'],
                        'fr',
                        $article->pub_date
                    );

                    if ($aiResult && isset($aiResult['score'])) {
                        $updateData['relevance_score'] = $aiResult['score'];
                        $updateData['structured_summary'] = $aiResult;
                        $updateData['category_tag'] = mb_substr((string) ($aiResult['category'] ?? $article->category_tag ?? ''), 0, 50);
                        $updateData['impact_level'] = mb_substr((string) ($aiResult['impact'] ?? $article->impact_level ?? ''), 0, 10);
                        $updateData['seo_title'] = mb_substr((string) ($aiResult['seo_title'] ?? $article->seo_title ?? ''), 0, 200);
                        $updateData['meta_description'] = mb_substr((string) ($aiResult['meta_description'] ?? $article->meta_description ?? ''), 0, 200);
                        $updateData['summary'] = $aiResult['hook'] ?? $article->summary;
                        $cat = $aiResult['category'] ?? '?';
                        $this->info("  IA: score={$aiResult['score']} cat={$cat}");
                    }
                }
            }

            if (! empty($updateData)) {
                $article->update($updateData);
                $updated++;
            }
        }

        $mode = $this->option('dry-run') ? 'DRY RUN' : 'APPLIED';
        $this->info("{$mode}: {$updated} mis a jour, {$errors} erreurs sur {$articles->count()} articles");

        return self::SUCCESS;
    }
}
