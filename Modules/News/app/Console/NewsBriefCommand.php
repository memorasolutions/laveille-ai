<?php

declare(strict_types=1);

namespace Modules\News\Console;

use Illuminate\Console\Command;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\CompositionPromptBuilder;
use Modules\News\Services\NewsImageService;

/**
 * Point d'entrée LECTURE SEULE du skill Claude Code local /actu2 (design doc "Actus - composition
 * manuelle assistée" 2026-08-15, section "Implémentation /actu2 - volet serveur (2026-08-17)") :
 * sort un JSON canonique décrivant l'état courant d'une fiche, sur stdout, sans jamais écrire.
 * Le skill l'appelle en tout premier (avant toute décision de rédaction) pour connaître ce qui
 * est déjà en base et la version de politique de composition en vigueur.
 *
 * N'écrit RIEN - contrairement à Modules\News\Console\NewsApplyCommand (SEULE porte d'écriture
 * bornée) et Modules\News\Console\NewsSourceCommand (récolte de l'ORIGINAL) : cette commande ne
 * fait qu'un SELECT et un json_encode(), jamais un update().
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsBriefCommand extends Command
{
    protected $signature = 'news:brief {article : id de la fiche news_articles}';

    protected $description = 'Sort un JSON canonique (lecture seule) décrivant une fiche - point d\'entrée du skill /actu2.';

    public function __construct(private readonly NewsImageService $imageService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $articleId = (int) $this->argument('article');
        $article = NewsArticle::find($articleId);

        if (! $article) {
            $this->error("Fiche introuvable : {$articleId}.");

            return self::FAILURE;
        }

        $this->line(json_encode([
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'url' => $article->url,
            'resolved_url' => $article->resolved_url,
            'is_published' => (bool) $article->is_published,
            'source_content_hash' => $article->source_content_hash,
            'source_captured_at' => $article->source_captured_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
            'primary_sources' => $article->primary_sources ?? [],
            'nature_original' => $article->nature_original,
            'niveau_preuve' => $article->niveau_preuve,
            'has_image' => $this->imageService->exists($article->id),
            'policy_version' => CompositionPromptBuilder::PROMPT_TEMPLATE_VERSION,
            'site_url' => url('/actualites/'.$article->slug),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
