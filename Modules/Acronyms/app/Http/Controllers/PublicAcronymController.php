<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Acronyms\Models\Acronym;
use Modules\Acronyms\Models\AcronymCategory;
use Modules\Settings\Facades\Settings;

class PublicAcronymController extends Controller
{
    public function index(Request $request): View
    {
        $query = Acronym::published()->ofDomain('education')->orderBy('acronym->'.app()->getLocale());

        if ($request->filled('letter')) {
            $letter = strtolower($request->letter);
            $query->whereRaw("LOWER(JSON_EXTRACT(acronym, '$.".app()->getLocale()."')) LIKE ?", ["\"{$letter}%"]);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $locale = app()->getLocale();
            $query->where(function ($q) use ($search, $locale) {
                $q->where("acronym->{$locale}", 'like', "%{$search}%")
                    ->orWhere("full_name->{$locale}", 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('acronym_category_id', $request->category);
        }

        $acronyms = $query->with('category')->get();
        $categories = AcronymCategory::orderBy('sort_order')->get();

        $locale = app()->getLocale();
        $acronymsJson = $acronyms->map(function ($a) use ($locale) {
            return [
                'id' => $a->id,
                'acronym' => $a->acronym,
                'full_name' => $a->full_name,
                'slug' => $a->getTranslation('slug', $locale),
                'icon' => $a->icon,
                'cat_id' => $a->acronym_category_id,
                'cat_name' => $a->category ? $a->category->name : __('Général'),
                'cat_color' => $a->category ? $a->category->color : '#6B7280',
                'vote_count' => method_exists($a, 'communityVoteCount') ? $a->communityVoteCount() : 0,
            ];
        });

        $categoriesJson = $categories->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'color' => $c->color,
            ];
        });

        return view('acronyms::public.index', compact('acronyms', 'categories', 'acronymsJson', 'categoriesJson'));
    }

    /**
     * Affiche une fiche acronyme par slug exact.
     * Si le slug ne correspond pas à une fiche mais à un sigle ambigu (N fiches),
     * redirige vers la page de désambiguïsation.
     * Si 1 seule fiche correspond au sigle → redirection 301 directe.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $locale = app()->getLocale();

        // 1. Cherche la fiche par slug exact (tous domaines confondus)
        $acronym = Acronym::published()
            ->where("slug->{$locale}", $slug)
            ->with('category')
            ->first();

        if ($acronym !== null) {
            $relatedAcronyms = Acronym::published()
                ->where('acronym_category_id', $acronym->acronym_category_id)
                ->where('id', '!=', $acronym->id)
                ->limit((int) Settings::get('acronyms.related_acronyms_limit', 6))
                ->get();

            return view('acronyms::public.show', compact('acronym', 'relatedAcronyms'));
        }

        // 2. Slug non trouvé — chercher si ce slug correspond à un sigle (ex. "ate" → "ATE")
        $sigle = strtoupper($slug);
        $fiches = Acronym::published()
            ->where("acronym->{$locale}", $sigle)
            ->with('category')
            ->get();

        $count = $fiches->count();

        if ($count === 0) {
            abort(404);
        }

        if ($count === 1) {
            return redirect()
                ->route('acronyms.show', $fiches->first()->getTranslation('slug', $locale))
                ->setStatusCode(301);
        }

        // N > 1 → page de désambiguïsation
        return view('acronyms::public.disambiguate', [
            'sigle' => $sigle,
            'fiches' => $fiches->sortBy(fn ($f) => $f->getTranslation('full_name', $locale, false) ?: $f->full_name),
        ]);
    }

    /**
     * Page de désambiguïsation pour un sigle ayant plusieurs significations.
     * Route : GET /acronymes-education/disambiguate/{sigle}
     */
    public function disambiguate(string $sigle): View|RedirectResponse
    {
        $locale = app()->getLocale();
        $sigle = strtoupper($sigle);

        $fiches = Acronym::published()
            ->where("acronym->{$locale}", $sigle)
            ->with('category')
            ->get();

        $count = $fiches->count();

        if ($count === 0) {
            abort(404);
        }

        if ($count === 1) {
            return redirect()
                ->route('acronyms.show', $fiches->first()->getTranslation('slug', $locale))
                ->setStatusCode(301);
        }

        return view('acronyms::public.disambiguate', compact('sigle', 'fiches'));
    }
}
