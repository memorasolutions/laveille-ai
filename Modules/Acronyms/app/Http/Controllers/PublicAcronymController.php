<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Http\Controllers;

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
                'logo_url' => $a->logo_url ? (str_starts_with($a->logo_url, 'http') ? $a->logo_url : asset($a->logo_url)) : null,
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

    public function show(string $slug): View
    {
        $acronym = Acronym::published()
            ->where('slug->'.app()->getLocale(), $slug)
            ->firstOrFail();

        $relatedAcronyms = Acronym::published()
            ->where('id', '!=', $acronym->id)
            ->where('acronym_category_id', $acronym->acronym_category_id)
            ->limit((int) Settings::get('acronyms.related_acronyms_limit', 6))
            ->get();

        return view('acronyms::public.show', compact('acronym', 'relatedAcronyms'));
    }
}
