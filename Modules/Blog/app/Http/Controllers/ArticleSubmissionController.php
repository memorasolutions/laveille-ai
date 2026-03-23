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
        ]);

        $locale = app()->getLocale();
        $slug = Str::slug($validated['title']);

        Article::create([
            'title' => [$locale => $validated['title'], 'fr' => $validated['title']],
            'slug' => [$locale => $slug, 'fr' => $slug],
            'content' => [$locale => $validated['content'], 'fr' => $validated['content']],
            'excerpt' => $validated['excerpt'] ? [$locale => $validated['excerpt'], 'fr' => $validated['excerpt']] : null,
            'category_id' => $validated['category_id'],
            'submitted_by' => auth()->id(),
            'submission_status' => 'pending',
            'user_id' => 1,
        ]);

        return redirect()->route('blog.submissions.create')
            ->with('success', __('Merci ! Votre article a été soumis et sera examiné par notre équipe.'));
    }

    public function mySubmissions(): View
    {
        $submissions = Article::where('submitted_by', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('blog::submissions.my-submissions', compact('submissions'));
    }
}
