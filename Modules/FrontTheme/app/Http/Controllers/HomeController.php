<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Settings\Facades\Settings;
use Nwidart\Modules\Facades\Module;

class HomeController extends Controller
{
    public function index(): View
    {
        $locale = app()->getLocale();

        // Articles blog
        $articles = collect();
        $articleClass = 'Modules\\Blog\\Models\\Article';
        if (Module::has('Blog') && Module::find('Blog')?->isEnabled() && class_exists($articleClass)) {
            $articles = $articleClass::query()
                ->published()
                ->with(['user', 'submittedByUser', 'blogCategory'])
                ->latest('published_at')
                ->take((int) Settings::get('fronttheme.home_articles_limit', 12))
                ->get();
        }

        // Outils IA populaires (répertoire techno)
        $popularTools = collect();
        $toolClass = 'Modules\\Directory\\Models\\Tool';
        if (Module::has('Directory') && Module::find('Directory')?->isEnabled() && class_exists($toolClass)) {
            $popularTools = $toolClass::query()
                ->published()
                ->with('categories')
                ->orderBy("name->{$locale}")
                ->take((int) Settings::get('fronttheme.home_popular_tools_limit', 4))
                ->get();
        }

        // Termes IA à découvrir (glossaire)
        $featuredTerms = collect();
        $termClass = 'Modules\\Dictionary\\Models\\Term';
        if (Module::has('Dictionary') && Module::find('Dictionary')?->isEnabled() && class_exists($termClass)) {
            $featuredTerms = $termClass::query()
                ->published()
                ->with('category')
                ->latest()
                ->take((int) Settings::get('fronttheme.home_featured_terms_limit', 6))
                ->get();
        }

        // Acronymes éducation à la une
        $featuredAcronyms = collect();
        $acronymClass = 'Modules\\Acronyms\\Models\\Acronym';
        if (Module::has('Acronyms') && Module::find('Acronyms')?->isEnabled() && class_exists($acronymClass)) {
            $featuredAcronyms = $acronymClass::query()
                ->published()
                ->with('category')
                ->latest()
                ->take((int) Settings::get('fronttheme.home_featured_acronyms_limit', 4))
                ->get();
        }

        // Dernières actualités IA
        $latestNews = collect();
        $newsClass = 'Modules\\News\\Models\\NewsArticle';
        if (Module::has('News') && Module::find('News')?->isEnabled() && class_exists($newsClass)) {
            $latestNews = $newsClass::query()
                ->where('is_published', true)
                ->with('source')
                ->latest('pub_date')
                ->take((int) Settings::get('fronttheme.home_latest_news_limit', 4))
                ->get();
        }

        // Outils interactifs gratuits
        $interactiveTools = collect();
        $iToolClass = 'Modules\\Tools\\Models\\Tool';
        if (Module::has('Tools') && Module::find('Tools')?->isEnabled() && class_exists($iToolClass)) {
            $interactiveTools = $iToolClass::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->take((int) Settings::get('fronttheme.home_interactive_tools_limit', 4))
                ->get();
        }

        return view('fronttheme::home', compact(
            'articles',
            'popularTools',
            'featuredTerms',
            'featuredAcronyms',
            'interactiveTools',
            'latestNews'
        ));
    }
}
