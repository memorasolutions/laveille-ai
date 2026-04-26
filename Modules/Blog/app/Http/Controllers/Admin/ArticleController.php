<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\States\DraftArticleState;
use Modules\Blog\States\PublishedArticleState;
use Modules\Core\Shared\Traits\ParsesTags;

class ArticleController extends Controller
{
    use ParsesTags;

    public function index(): View
    {
        return view('blog::admin.articles.index');
    }

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $existingTags = Article::whereNotNull('tags')->pluck('tags')->flatten()->unique()->sort()->values()->toArray();

        return view('blog::admin.articles.create', compact('categories', 'existingTags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'video_url' => 'nullable|url|max:500',
            'status' => 'nullable|in:draft,pending_review,published,archived',
            'category_id' => 'nullable|integer|exists:blog_categories,id',
            'tags_input' => 'nullable|string',
            'published_at' => 'nullable|date',
            'format' => 'nullable|in:standard,video,gallery,audio,quote,link',
            'is_featured' => 'nullable',
            'content_password' => 'nullable|string|max:100',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['user_id'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['tags'] = $this->parseTagsInput($validated['tags_input'] ?? '');
        unset($validated['tags_input']);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('articles', 'public');
        }

        Article::create($validated);

        return redirect()->route('admin.blog.articles.index')
            ->with('success', 'Article créé avec succès.');
    }

    public function show(Article $article): View
    {
        return view('blog::admin.articles.show', compact('article'));
    }

    public function edit(Article $article): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $existingTags = Article::whereNotNull('tags')->pluck('tags')->flatten()->unique()->sort()->values()->toArray();

        return view('blog::admin.articles.edit', compact('article', 'categories', 'existingTags'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'video_url' => 'nullable|url|max:500',
            'status' => 'nullable|in:draft,pending_review,published,archived',
            'category_id' => 'nullable|integer|exists:blog_categories,id',
            'tags_input' => 'nullable|string',
            'published_at' => 'nullable|date',
            'format' => 'nullable|in:standard,video,gallery,audio,quote,link',
            'is_featured' => 'nullable',
            'content_password' => 'nullable|string|max:100',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['tags'] = $this->parseTagsInput($validated['tags_input'] ?? '');
        unset($validated['tags_input']);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('articles', 'public');
        } else {
            unset($validated['featured_image']);
        }

        $article->update($validated);

        $this->syncFaqs($article, $request->input('faqs', []));

        return redirect()->route('admin.blog.articles.index')
            ->with('success', 'Article mis à jour.');
    }

    private function syncFaqs(Article $article, array $faqsInput): void
    {
        $locale = 'fr_CA';
        $keepIds = [];

        foreach (array_values($faqsInput) as $position => $faqData) {
            $question = trim((string) ($faqData['question'] ?? ''));
            $answer   = trim((string) ($faqData['answer'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            $isPublished = ! empty($faqData['is_published']);
            $faqId       = isset($faqData['id']) && $faqData['id'] !== '' ? (int) $faqData['id'] : null;

            if ($faqId) {
                $faq = $article->faqs()->find($faqId);
                if ($faq) {
                    $faq->setTranslation('question', $locale, $question);
                    $faq->setTranslation('answer', $locale, $answer);
                    $faq->position     = $position;
                    $faq->is_published = $isPublished;
                    $faq->save();
                    $keepIds[] = $faq->id;
                }
            } else {
                $newFaq = $article->faqs()->create([
                    'question'     => [$locale => $question],
                    'answer'       => [$locale => $answer],
                    'position'     => $position,
                    'is_published' => $isPublished,
                ]);
                $keepIds[] = $newFaq->id;
            }
        }

        $article->faqs()->whereNotIn('id', $keepIds)->delete();
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.blog.articles.index')
            ->with('success', 'Article supprimé.');
    }

    public function preview(Article $article): View
    {
        $article->load(['blogCategory', 'user', 'tagsRelation']);

        $comments = collect();
        $relatedArticles = collect();
        $recentArticles = collect();
        $isPreview = true;

        return view('blog::public.show', compact(
            'article', 'comments', 'relatedArticles', 'recentArticles', 'isPreview'
        ));
    }

    public function publish(Article $article): RedirectResponse
    {
        $article->status->transitionTo(PublishedArticleState::class);

        return back()->with('success', 'Article publié.');
    }

    public function unpublish(Article $article): RedirectResponse
    {
        $article->status->transitionTo(DraftArticleState::class);

        return back()->with('success', 'Article dépublié.');
    }

    public function youtubeSummary(Article $article): JsonResponse
    {
        if (! class_exists(\Modules\AI\Services\YouTubeService::class)) {
            return response()->json(['error' => 'Module AI non disponible'], 422);
        }

        if (empty($article->video_url)) {
            return response()->json(['error' => 'Aucune URL YouTube'], 422);
        }

        $service = app(\Modules\AI\Services\YouTubeService::class);
        $result = $service->extractTranscript($article->video_url);

        if (! $result) {
            return response()->json(['error' => 'Impossible d\'extraire la transcription'], 422);
        }

        $summary = $service->summarize($result['transcript'], $result['video_id']);

        if (! $summary) {
            return response()->json(['error' => 'Impossible de générer le résumé'], 422);
        }

        $article->update(['video_summary' => $summary]);

        return response()->json(['summary' => $summary]);
    }

    public function regenerateSeo(Article $article): JsonResponse
    {
        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return response()->json(['success' => false, 'message' => __('Module IA non disponible')], 422);
        }
        $service = app(\Modules\AI\Services\AiService::class);
        $seoData = $service->generateSeoMeta($article->title, strip_tags($article->content));

        if (class_exists(\Modules\SEO\Models\MetaTag::class)) {
            \Modules\SEO\Models\MetaTag::updateOrCreate(
                ['url_pattern' => '/blog/'.$article->slug],
                array_merge($seoData, ['is_active' => true])
            );
        }

        return response()->json(['success' => true, 'message' => __('SEO généré avec succès')]);
    }

    public function analyzeContent(Article $article): JsonResponse
    {
        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return response()->json(['success' => false, 'message' => __('Module IA non disponible')], 422);
        }
        $service = app(\Modules\AI\Services\AiService::class);
        $analysis = $service->analyzeContent($article->title, $article->content, app()->getLocale());

        return response()->json(['success' => true, 'message' => __('Analyse générée avec succès'), 'analysis' => $analysis]);
    }

    public function regenerateSummary(Article $article): JsonResponse
    {
        if (! class_exists(\Modules\AI\Services\AiService::class)) {
            return response()->json(['success' => false, 'message' => __('Module IA non disponible')], 422);
        }
        $service = app(\Modules\AI\Services\AiService::class);
        $summary = $service->generateSummary($article->content, app()->getLocale());

        $article->setTranslation('excerpt', app()->getLocale(), $summary);
        $article->saveQuietly();

        return response()->json(['success' => true, 'message' => __('Résumé généré avec succès'), 'summary' => $summary]);
    }

    public function translateArticle(Article $article, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_locale' => 'required|in:en,fr',
        ]);

        $targetLocale = $validated['target_locale'];
        $sourceLocale = $targetLocale === 'en' ? 'fr' : 'en';

        try {
            if (! class_exists(\Modules\AI\Services\AiService::class)) {
                return response()->json(['success' => false, 'message' => __('Module IA non disponible')], 422);
            }
            $service = app(\Modules\AI\Services\AiService::class);

            foreach (['title', 'content', 'excerpt'] as $field) {
                $original = $article->getTranslation($field, $sourceLocale, false);

                if (! empty($original)) {
                    $translated = $service->translateContent($original, $sourceLocale, $targetLocale);
                    $article->setTranslation($field, $targetLocale, $translated);
                }
            }

            $translatedTitle = $article->getTranslation('title', $targetLocale, false);
            if (! empty($translatedTitle)) {
                $article->setTranslation('slug', $targetLocale, Str::slug($translatedTitle));
            }

            $article->save();

            return response()->json(['success' => true, 'message' => __('Article traduit avec succès')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __('Échec de la traduction.')], 500);
        }
    }

    public function autosave(Request $request, Article $article): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
        ]);

        $article->fill(array_filter($validated, fn ($v) => $v !== null));

        if ($article->isDirty()) {
            $article->saveQuietly();

            return response()->json([
                'success' => true,
                'saved_at' => now()->toDateTimeString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'saved_at' => null,
        ]);
    }
}
