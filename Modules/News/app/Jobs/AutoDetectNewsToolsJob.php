<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: détection automatique des outils annuaire mentionnés dans une actualité,
 * déclenchée à la publication (NewsArticleObserver). Attache les outils détectés en
 * source=auto SANS jamais écraser une sélection manuelle existante (délègue à
 * NewsToolSyncAction::attachAuto(), le bouton manuel garde sync()/source=manual intacts).
 *
 * RAISON: le cron FetchNewsCommand peut publier plusieurs dizaines d'articles d'affilée -
 * exécuter la détection en synchrone dans l'Observer ralentirait le run. Pattern calqué
 * ligne à ligne sur PurgeCloudflareCacheJob (module CloudflareCache).
 */

declare(strict_types=1);

namespace Modules\News\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;

class AutoDetectNewsToolsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    /** @var array<int,int> */
    public array $backoff = [30, 120];

    public function __construct(public NewsArticle $article)
    {
        // Trait Queueable declare public $queue (untyped sans default) — set via setter pour eviter
        // « define the same property differs and is considered incompatible » error PHP 8.2+
        $this->onQueue('news-tools');
    }

    public function handle(): void
    {
        if (! class_exists(NewsToolSyncAction::class)) {
            return;
        }

        $article = $this->article->fresh();

        if (! $article) {
            return;
        }

        $action = app(NewsToolSyncAction::class);

        $suggested = $action->suggest($article);

        if ($suggested->isEmpty()) {
            Log::info("AutoDetectNewsToolsJob: aucun outil détecté pour l'article #{$article->id}");

            return;
        }

        $count = $action->attachAuto($article, $suggested);

        Log::info("AutoDetectNewsToolsJob: {$count} outil(s) auto-lié(s) pour l'article #{$article->id}");

        if ($count > 0) {
            NewsToolSyncAction::invalidatePublicCache($article);
        }
    }

    public function failed(?\Throwable $exception = null): void
    {
        Log::error('AutoDetectNewsToolsJob: failed', [
            'article_id' => $this->article->id,
            'exception'  => $exception?->getMessage(),
        ]);
    }
}
