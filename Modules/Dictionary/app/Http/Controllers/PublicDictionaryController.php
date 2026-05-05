<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Dictionary\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;

class PublicDictionaryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Term::published()->orderBy('name->'.app()->getLocale());

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('letter')) {
            $letter = strtolower($request->letter);
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.".app()->getLocale()."')) LIKE ?", ["\"{$letter}%"]);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $locale = app()->getLocale();
            $query->where(function ($q) use ($search, $locale) {
                $q->where("name->{$locale}", 'like', "%{$search}%")
                    ->orWhere("definition->{$locale}", 'like', "%{$search}%");
            });
        }

        $terms = $query->get();
        $categories = Category::orderBy('sort_order')->get();
        $types = ['acronym' => __('Acronymes'), 'ai_term' => __('Termes IA'), 'explainer' => __('Vulgarisations')];

        return view('dictionary::public.index', compact('terms', 'categories', 'types'));
    }

    public function show(string $slug): View
    {
        $term = Term::published()
            ->where('slug->'.app()->getLocale(), $slug)
            ->firstOrFail();

        if (\Schema::hasColumn('dictionary_terms', 'views_count') && ! request()->isMethod('HEAD')) {
            try { $term->incrementQuietly('views_count'); } catch (\Throwable $e) {}
        }

        $relatedTerms = Term::published()
            ->where('id', '!=', $term->id)
            ->where('dictionary_category_id', $term->dictionary_category_id)
            ->limit(5)
            ->get();

        return view('dictionary::public.show', compact('term', 'relatedTerms'));
    }
}
