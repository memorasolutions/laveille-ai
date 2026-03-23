<?php

declare(strict_types=1);

namespace Modules\Dictionary\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

class TermAdminController extends Controller
{
    public function index(): View
    {
        $terms = Term::with('category')->orderBy('name->' . app()->getLocale())->paginate(20);

        return view('dictionary::admin.index', compact('terms'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('sort_order')->get();
        $types = ['acronym' => __('Acronyme'), 'ai_term' => __('Terme IA'), 'explainer' => __('Vulgarisation')];

        return view('dictionary::admin.create', compact('categories', 'types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'definition' => 'required|string',
            'type' => 'required|in:acronym,ai_term,explainer',
            'dictionary_category_id' => 'nullable|exists:dictionary_categories,id',
            'is_published' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $slug = Str::slug($validated['name']);
        $locale = app()->getLocale();
        Term::create([
            'name' => [$locale => $validated['name'], 'fr' => $validated['name']],
            'slug' => [$locale => $slug, 'fr' => $slug],
            'definition' => [$locale => $validated['definition'], 'fr' => $validated['definition']],
            'type' => $validated['type'],
            'dictionary_category_id' => $validated['dictionary_category_id'],
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.dictionary.index')->with('success', __('Terme créé.'));
    }

    public function edit(Term $term): View
    {
        $categories = Category::orderBy('sort_order')->get();
        $types = ['acronym' => __('Acronyme'), 'ai_term' => __('Terme IA'), 'explainer' => __('Vulgarisation')];

        return view('dictionary::admin.edit', compact('term', 'categories', 'types'));
    }

    public function update(Request $request, Term $term): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'definition' => 'required|string',
            'type' => 'required|in:acronym,ai_term,explainer',
            'dictionary_category_id' => 'nullable|exists:dictionary_categories,id',
            'is_published' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $locale = app()->getLocale();
        $term->setTranslation('name', $locale, $validated['name']);
        $term->setTranslation('name', 'fr', $validated['name']);
        $term->setTranslation('definition', $locale, $validated['definition']);
        $term->setTranslation('definition', 'fr', $validated['definition']);
        $term->type = $validated['type'];
        $term->dictionary_category_id = $validated['dictionary_category_id'];
        $term->is_published = $request->boolean('is_published');
        $term->sort_order = $validated['sort_order'] ?? 0;
        $term->save();

        return redirect()->route('admin.dictionary.index')->with('success', __('Terme mis à jour.'));
    }

    public function destroy(Term $term): RedirectResponse
    {
        $term->delete();

        return redirect()->route('admin.dictionary.index')->with('success', __('Terme supprimé.'));
    }
}
