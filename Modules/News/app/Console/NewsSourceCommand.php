<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\SourceMarkdownFetcher;

/**
 * Récolte serveur de l'ORIGINAL pour le skill Claude Code local /actu2 (design doc "Actus -
 * composition manuelle assistée" 2026-08-15, section "Implémentation /actu2 - volet serveur
 * (2026-08-17)") : récupère l'URL de l'ORIGINAL trouvé par le skill (pas nécessairement l'URL déjà
 * collectée par le flux RSS - le skill peut avoir remonté jusqu'au communiqué, au post X ou à
 * l'étude source) et persiste son Markdown comme `internal_source_text`.
 *
 * Réutilise ENTIÈREMENT Modules\News\Services\SourceMarkdownFetcher::fetch() (JAMAIS de
 * duplication - même service que NewsCompositionController::fetchSource(), même garde SSRF, même
 * refus de paywall, même repli Puppeteer) et NewsArticle::sourceProvenanceUpdates() (même règle de
 * provenance que le contrôleur, extraite pour ce mandat précisément).
 *
 * Deuxième porte d'écriture bornée aux côtés de Modules\News\Console\NewsApplyCommand : celle-ci
 * n'écrit QUE 'internal_source_text' et 'source_acquisition' (+ la provenance dérivée) - jamais un
 * champ de la liste blanche de NewsApplyCommand, jamais is_published/published_at.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsSourceCommand extends Command
{
    protected $signature = 'news:source {article : id de la fiche news_articles} {url : URL de l\'ORIGINAL trouvé par le skill} {--replace : obligatoire si un texte source existe déjà, sinon refus explicite}';

    protected $description = 'Récupère l\'ORIGINAL à l\'URL donnée et persiste son Markdown comme texte source - porte serveur du skill /actu2.';

    public function handle(SourceMarkdownFetcher $fetcher): int
    {
        $articleId = (int) $this->argument('article');
        $article = NewsArticle::find($articleId);

        if (! $article) {
            $this->error("Fiche introuvable : {$articleId}.");

            return self::FAILURE;
        }

        // ACTION : même garde-fou que NewsApplyCommand - refus systématique sur une fiche déjà
        // publiée, AUCUNE exception, vérifiée avant toute autre logique.
        // MCP: SELF (<5 lignes)
        // RAISON: unique limite non négociable, cohérente avec la porte soeur.
        if ($article->is_published) {
            $this->error("La fiche {$article->id} est déjà publiée - news:source refuse d'écrire sur une fiche publiée.");

            return self::FAILURE;
        }

        $url = trim((string) $this->argument('url'));
        if ($url === '') {
            $this->error('URL vide.');

            return self::FAILURE;
        }

        // ACTION : même règle que NewsCompositionController::fetchSource() - refuse d'écraser un
        // texte source déjà présent sauf --replace explicite, pour ne jamais perdre en silence un
        // texte déjà récolté ou retouché.
        // MCP: SELF (<5 lignes)
        // RAISON: design doc, section "Implémentation /actu2 - volet serveur (2026-08-17)".
        if (filled($article->internal_source_text) && ! $this->option('replace')) {
            $this->error("Un texte source existe déjà pour la fiche {$article->id} - relance avec --replace pour le remplacer.");

            return self::FAILURE;
        }

        $result = $fetcher->fetch($url, $article->title);

        Log::channel('composition')->info('news:source - tentative', [
            'article_id' => $article->id,
            'url' => $url,
            'success' => $result['success'],
            'method' => $result['acquisition']['method'] ?? null,
            'http_status' => $result['acquisition']['http_status'] ?? null,
            'error' => $result['error'],
        ]);

        if (! $result['success']) {
            $this->error("Récupération impossible : {$result['error']}");

            return self::FAILURE;
        }

        $update = array_merge(
            [
                'internal_source_text' => $result['markdown'],
                'source_acquisition' => $result['acquisition'],
            ],
            $article->sourceProvenanceUpdates((string) $result['markdown'])
        );

        DB::transaction(function () use ($article, $update): void {
            $article->update($update);
        });

        $article->refresh();

        $this->line(json_encode([
            'success' => true,
            'article_id' => $article->id,
            'source_content_hash' => $article->source_content_hash,
            'updated_at' => $article->updated_at?->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
