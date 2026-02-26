<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleRevision;

class ArticleRevisionService
{
    public function createRevision(Article $article): ?ArticleRevision
    {
        $tracked = ['title', 'content', 'excerpt', 'status', 'meta'];
        $changed = array_intersect_key($article->getChanges(), array_flip($tracked));

        if (empty($changed)) {
            return null;
        }

        $lastRevNumber = (int) $article->revisions()->max('revision_number');

        $encodeIfArray = fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;

        return ArticleRevision::create([
            'article_id' => $article->id,
            'user_id' => auth()->id() ?? $article->user_id,
            'title' => $encodeIfArray($article->getOriginal('title')),
            'content' => $encodeIfArray($article->getOriginal('content')),
            'excerpt' => $encodeIfArray($article->getOriginal('excerpt')),
            'status' => (string) $article->getOriginal('status'),
            'meta' => $article->getOriginal('meta'),
            'revision_number' => $lastRevNumber + 1,
        ]);
    }

    public function restore(Article $article, ArticleRevision $revision): Article
    {
        $article->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'excerpt' => $revision->excerpt,
            'meta' => $revision->meta,
        ]);

        return $article->fresh();
    }

    /** @phpstan-ignore-next-line */
    public function getRevisions(Article $article, int $limit = 20): Collection
    {
        return $article->revisions()->with('user')->latest()->limit($limit)->get();
    }
}
