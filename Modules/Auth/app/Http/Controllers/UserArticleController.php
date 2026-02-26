<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Core\Shared\Traits\ParsesTags;

class UserArticleController extends Controller
{
    use ParsesTags;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $articles = Article::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('auth::articles.index', compact('articles'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();

        return view('auth::articles.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'category_id' => 'nullable|exists:blog_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'tags_input' => 'nullable|string',
        ]);

        $tags = $this->parseTagsInput($validated['tags_input'] ?? '');

        Article::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'content' => $validated['content'],
            'status' => $validated['status'],
            'category_id' => $validated['category_id'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'tags' => $tags,
        ]);

        return redirect()->route('user.articles.index')->with('success', 'Article créé avec succès.');
    }

    public function edit(Article $article)
    {
        abort_if($article->user_id !== auth()->id(), 403);

        $categories = Category::where('is_active', true)->get();

        return view('auth::articles.edit', compact('article', 'categories'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        abort_if($article->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
            'category_id' => 'nullable|exists:blog_categories,id',
            'excerpt' => 'nullable|string|max:500',
            'tags_input' => 'nullable|string',
        ]);

        $tags = $this->parseTagsInput($validated['tags_input'] ?? '');

        $article->update([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'content' => $validated['content'],
            'status' => $validated['status'],
            'category_id' => $validated['category_id'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'tags' => $tags,
        ]);

        return redirect()->route('user.articles.index')->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        abort_if($article->user_id !== auth()->id(), 403);

        $article->delete();

        return redirect()->route('user.articles.index')->with('success', 'Article supprimé.');
    }
}
