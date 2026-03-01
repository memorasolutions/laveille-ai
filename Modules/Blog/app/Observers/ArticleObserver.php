<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Observers;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleRevision;
use Modules\Blog\Services\ArticleRevisionService;
use Modules\Settings\Facades\Settings;

class ArticleObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Article $article): void
    {
        activity()->performedOn($article)->log("Article {$article->title} créé");
    }

    public function updated(Article $article): void
    {
        app(ArticleRevisionService::class)->createRevision($article);

        // Auto-cleanup des anciennes révisions (configurable via Settings)
        try {
            $autoCleanup = Settings::get('blog.revision_auto_cleanup', 'true') === 'true';
            $maxCount = (int) Settings::get('blog.revision_max_count', '50');

            if ($autoCleanup && $maxCount > 0) {
                $keepIds = $article->revisions()->orderByDesc('revision_number')->limit($maxCount)->pluck('id');
                ArticleRevision::where('article_id', $article->id)->whereNotIn('id', $keepIds)->delete();
            }
        } catch (\Throwable) {
            // Settings table might not exist in tests
        }

        activity()->performedOn($article)->withProperties(['changes' => $article->getChanges()])->log("Article {$article->title} modifié");
    }

    public function deleted(Article $article): void
    {
        activity()->performedOn($article)->log("Article {$article->title} supprimé");
    }
}
