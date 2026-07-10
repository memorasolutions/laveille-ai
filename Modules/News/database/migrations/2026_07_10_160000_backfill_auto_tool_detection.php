<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;

/**
 * Backfill : détection automatique (source=auto) des outils annuaire mentionnés dans les
 * actualités déjà publiées AVANT ce chantier (auto-détection à la publication, cf.
 * AutoDetectNewsToolsJob + NewsArticleObserver), qui n'ont encore aucun outil lié (ni
 * manuel ni auto). Réutilise NewsToolSyncAction::suggest()/attachAuto() - exactement le
 * même moteur de détection que le nouveau Job et le bouton manuel "Suggérer les outils
 * détectés" (lequel reste inchangé, source=manual via sync()).
 *
 * RÉVERSIBLE : down() supprime uniquement les liaisons marquées source=auto - jamais les
 * liaisons manuelles. Sûr même si rejoué, car aucun autre chantier ne pose source=auto
 * avant celui-ci.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! class_exists(NewsArticle::class) || ! class_exists(NewsToolSyncAction::class)) {
            return;
        }

        $action = app(NewsToolSyncAction::class);

        NewsArticle::published()
            ->whereDoesntHave('tools')
            ->chunkById(50, function ($articles) use ($action) {
                foreach ($articles as $article) {
                    $suggested = $action->suggest($article);

                    if ($suggested->isNotEmpty()) {
                        $count = $action->attachAuto($article, $suggested);
                        echo "[news-tools-backfill] article #{$article->id}: {$count} outil(s)\n";
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('news_article_tool')->where('source', 'auto')->delete();
    }
};
