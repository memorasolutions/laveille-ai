<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\ArticleRevision;
use Modules\Blog\Services\ArticleRevisionService;
use Modules\Blog\Services\DiffService;

class ArticleRevisionController extends Controller
{
    public function __construct(private ArticleRevisionService $service) {}

    public function index(Article $article): View
    {

        $revisions = $this->service->getRevisions($article);

        return view('blog::admin.revisions.index', compact('article', 'revisions'));
    }

    public function show(Article $article, ArticleRevision $revision): View
    {

        return view('blog::admin.revisions.show', compact('article', 'revision'));
    }

    public function diff(Article $article, ArticleRevision $revision): View
    {
        $diffService = new DiffService;

        $revTitle = $this->decodeTranslatable($revision->title);
        $revContent = strip_tags($this->decodeTranslatable($revision->content));

        $diffTitle = $diffService->diffHtml($revTitle, (string) $article->title);
        $diffContent = $diffService->diffHtml($revContent, strip_tags((string) $article->content));

        return view('blog::admin.revisions.diff', compact('article', 'revision', 'diffTitle', 'diffContent'));
    }

    private function decodeTranslatable(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $locale = app()->getLocale();

            return $decoded[$locale] ?? $decoded[array_key_first($decoded)] ?? $value;
        }

        return $value;
    }

    public function restore(Article $article, ArticleRevision $revision): RedirectResponse
    {

        $this->service->restore($article, $revision);

        return redirect()->route('admin.blog.articles.edit', $article)
            ->with('success', "Article restauré à la révision #{$revision->revision_number}.");
    }
}
