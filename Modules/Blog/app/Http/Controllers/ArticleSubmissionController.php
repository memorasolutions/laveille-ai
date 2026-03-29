<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;

class ArticleSubmissionController extends Controller
{
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('blog::submissions.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:200',
            'category_id' => 'required|exists:blog_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'author_bio' => 'required|string|min:50|max:1000',
            'author_url' => 'nullable|url|max:500',
            'sources' => 'required|string|min:10',
            'article_file' => 'nullable|file|mimes:md,doc,docx,pdf,txt|max:5120',
        ]);

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('article_file')) {
            $filePath = $request->file('article_file')->store('submissions', 'public');
        }

        $locale = app()->getLocale();
        $slug = Str::slug($validated['title']);

        // Append sources to content
        $contentWithSources = $validated['content']."\n\n---\n**Sources :**\n".$validated['sources'];

        $article = Article::create([
            'title' => [$locale => $validated['title'], 'fr' => $validated['title']],
            'slug' => [$locale => $slug, 'fr' => $slug],
            'content' => [$locale => $contentWithSources, 'fr' => $contentWithSources],
            'excerpt' => $validated['excerpt'] ? [$locale => $validated['excerpt'], 'fr' => $validated['excerpt']] : null,
            'category_id' => $validated['category_id'],
            'submitted_by' => auth()->id(),
            'submission_status' => 'pending',
            'user_id' => 1,
        ]);

        // Store extra fields in meta (author_bio, author_url, file_path, sources)
        if (method_exists($article, 'setMeta')) {
            $article->setMeta('author_bio', $validated['author_bio']);
            $article->setMeta('author_url', $validated['author_url'] ?? null);
            $article->setMeta('sources', $validated['sources']);
            if ($filePath) {
                $article->setMeta('submission_file', $filePath);
            }
        }

        return redirect()->route('blog.submissions.create')
            ->with('success', __('Merci ! Votre article a été soumis avec votre bio et vos sources. Notre équipe l\'examinera sous peu.'));
    }

    public function mySubmissions(): View
    {
        $submissions = Article::where('submitted_by', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('blog::submissions.my-submissions', compact('submissions'));
    }
}
