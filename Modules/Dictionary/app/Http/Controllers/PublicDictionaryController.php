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
use Modules\Core\Services\ViewCounterService;
use Modules\Dictionary\Models\Category;
use Modules\Dictionary\Models\Term;
use Modules\Dictionary\Support\CoverageTerms;

class PublicDictionaryController extends Controller
{
    public function index(Request $request): View
    {
        // S89 #68 — tri alphabétique CASE-INSENSITIVE par défaut (uppercase + lowercase mêlés)
        // orderBy MySQL sur JSON_EXTRACT est case-sensitive par défaut (binaire)
        // ce qui plaçait AGI/AI Act/API avant Affinage. LOWER() force tri naturel.
        $locale = app()->getLocale();
        $query = Term::published()
            ->orderByRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"".$locale."\"'))) ASC");

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('letter')) {
            // Mandat #1939 (2026-08-31) : JSON_EXTRACT nu (sans UNQUOTE) va contre la convention
            // du projet (memory/feedback_json_extract_unquote_translatable.md) - sous MySQL, un
            // JSON_EXTRACT scalaire reste encadré de guillemets JSON, d'où le `"{$letter}%` de
            // l'ancien motif LIKE pour matcher ce guillemet littéral. Sous sqlite, json_extract()
            // natif renvoie déjà la valeur SANS guillemets : le motif ne matchait donc jamais rien
            // (silencieux, 0 résultat, aucune erreur - trouvé par PublicDictionaryIndexPageTest,
            // pas par une exception). JSON_UNQUOTE(JSON_EXTRACT(...)) - même patron que le
            // orderByRaw juste au-dessus - retire ce guillemet des DEUX côtés, portable.
            $letter = strtolower($request->letter);
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"".$locale."\"'))) LIKE ?", ["{$letter}%"]);
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

        // Colonne views_count jamais activée sur ce module (Schema::hasColumn gardé côté
        // service) - ViewCounterService reste un no-op silencieux tant qu'elle n'existe pas.
        ViewCounterService::record($term, 'views_count');

        $relatedTerms = Term::published()
            ->where('id', '!=', $term->id)
            ->where('dictionary_category_id', $term->dictionary_category_id)
            ->limit(5)
            ->get();

        return view('dictionary::public.show', compact('term', 'relatedTerms'));
    }

    /**
     * Ticket #2531 (étape 4) : page de couverture d'un terme - liste TOUTES les actualités qui le
     * mentionnent (contrairement à la section « Dans l'actualité » de show(), plafonnée à 5).
     *
     * Défense en profondeur : la contrainte de route (routes/web.php, CoverageTerms::routePattern())
     * empêche déjà tout slug hors liste d'atteindre ce contrôleur, mais on revérifie ici même,
     * jamais de confiance aveugle dans un seul filtre - un terme retiré de la liste demain ne doit
     * pas non plus dépendre d'un redéploiement du cache de routes pour cesser de répondre.
     *
     * Actualités : uniquement les liaisons APPROUVÉES (pivot news_article_term.is_approved, même
     * doctrine « désapprouver, jamais supprimer » que Term::approvedNewsArticles()) et les articles
     * PUBLIÉS (NewsArticle::scopePublished()), les plus récents d'abord, paginées comme le fait
     * CollectionController::index() (Modules\Directory) - même mécanique Eloquent paginate() +
     * Blade ->links(), taille de page alignée sur celle du module News lui-même (PublicNewsController
     * ::index(), paginate(20)) puisque cette page-ci liste des actualités, pas des outils. Colonnes
     * réduites au strict nécessaire de l'affichage (titre, date, lien) - jamais un select complet.
     */
    public function coverage(string $slug): View
    {
        if (! in_array($slug, CoverageTerms::SLUGS, true)) {
            abort(404);
        }

        $term = Term::published()
            ->where('slug->'.app()->getLocale(), $slug)
            ->firstOrFail();

        $newsArticles = $term->approvedNewsArticles()
            ->published()
            ->orderByDesc('news_articles.pub_date')
            ->paginate(20, ['news_articles.id', 'news_articles.slug', 'news_articles.title', 'news_articles.seo_title', 'news_articles.pub_date']);

        return view('dictionary::public.coverage', compact('term', 'newsArticles'));
    }
}
