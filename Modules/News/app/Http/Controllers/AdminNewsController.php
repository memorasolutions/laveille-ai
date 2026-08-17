<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\ScreenshotUploadService;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\RssFetcherService;
use Modules\Directory\Models\Tool;
use Modules\Settings\Facades\Settings;

class AdminNewsController extends Controller
{
    public function index(): View
    {
        $sources = NewsSource::withCount('articles')->paginate((int) Settings::get('news.admin_per_page', 20));

        return view('news::admin.sources.index', compact('sources'));
    }

    public function create(): View
    {
        return view('news::admin.sources.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|unique:news_sources',
            'category' => 'nullable|string|max:255',
            'language' => ['nullable', 'string', Rule::in(['fr', 'en'])],
            'active' => 'boolean',
        ]);

        NewsSource::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'category' => $validated['category'] ?? null,
            'language' => $validated['language'] ?? 'fr',
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.news.sources.index')->with('success', __('Source RSS créée.'));
    }

    public function edit(NewsSource $source): View
    {
        return view('news::admin.sources.edit', compact('source'));
    }

    public function update(Request $request, NewsSource $source): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => ['required', 'url', Rule::unique('news_sources')->ignore($source->id)],
            'category' => 'nullable|string|max:255',
            'language' => ['nullable', 'string', Rule::in(['fr', 'en'])],
            'active' => 'boolean',
        ]);

        $source->update([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'category' => $validated['category'] ?? null,
            'language' => $validated['language'] ?? 'fr',
            'active' => $request->boolean('active', true),
        ]);

        return redirect()->route('admin.news.sources.index')->with('success', __('Source RSS mise à jour.'));
    }

    public function toggleActive(NewsSource $source): RedirectResponse
    {
        $source->update(['active' => ! $source->active]);

        return back()->with('success', __('Statut modifié.'));
    }

    public function destroy(NewsSource $source): RedirectResponse
    {
        $source->delete();

        return redirect()->route('admin.news.sources.index')->with('success', __('Source supprimée.'));
    }

    public function fetchNow(NewsSource $source, RssFetcherService $fetcher): RedirectResponse
    {
        // ACTION : fetchSource() retourne désormais ['count' => int, 'texts' => array] (design
        // doc "Actus - zéro copie du texte source", 2026-08-13, section 4.1) - seul le compteur
        // sert ici, cette action ne score rien.
        // SELF: correction <5 lignes
        $count = $fetcher->fetchSource($source)['count'];

        return back()->with('success', __(':count articles récupérés pour :name.', ['count' => $count, 'name' => $source->name]));
    }

    // ── Articles ──

    public function articles(Request $request): View
    {
        $query = NewsArticle::with('source')->latest('pub_date');

        // Filtres
        if ($request->filled('status')) {
            if ($request->status === 'published') $query->where('is_published', true);
            elseif ($request->status === 'filtered') $query->where('is_published', false);
        }
        if ($request->filled('category')) {
            $query->where('category_tag', $request->category);
        }
        if ($request->filled('feed')) {
            $query->where('feed_type', $request->feed);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $articles = $query->paginate(30)->appends($request->all());

        $categories = NewsArticle::whereNotNull('category_tag')
            ->distinct()->pluck('category_tag')->sort();

        $stats = [
            'total' => NewsArticle::count(),
            'published' => NewsArticle::where('is_published', true)->count(),
            'filtered' => NewsArticle::where('is_published', false)->count(),
            'today' => NewsArticle::whereDate('created_at', today())->where('is_published', true)->count(),
        ];

        return view('news::admin.articles.index', compact('articles', 'categories', 'stats'));
    }

    public function toggleArticle(NewsArticle $article): RedirectResponse
    {
        if (! $article->is_published) {
            // ACTION : bascule rapide → même règle « publier = purger » que le bouton
            // Publier-et-purger de l'écran de composition (addendum "purge garantie sur tous
            // les chemins de publication", 2026-08-17) - DRY sur
            // NewsArticle::publishAndPurgeSource(), aucune fiche publiée ne doit pouvoir garder
            // son texte source intégral, quel que soit le chemin emprunté pour la publier.
            // MCP: SELF (<5 lignes)
            // RAISON: exigence explicite du propriétaire, une seule implémentation de la purge.
            $article->publishAndPurgeSource();
        } else {
            // Dépublication : ne touche à rien d'autre - le texte source est déjà parti dès la
            // première publication (ou n'a jamais existé), et une republication future ne le
            // fait pas renaître (design doc section 5.2 : la purge n'est jamais réversible).
            $article->update(['is_published' => false]);
        }

        return back()->with('success', $article->is_published
            ? __('Article publié.')
            : __('Article dépublié.'));
    }

    public function editArticle(NewsArticle $article): View
    {
        $article->load('source', 'tools');

        $locale = app()->getLocale();

        // ACTION: charger tous les outils publiés pour TomSelect
        // MCP: SELF (<5 lignes)
        // RAISON: sélection manuelle par l'admin
        $allTools = Tool::published()
            ->orderByRaw("JSON_EXTRACT(name, '$.\"{$locale}\"') ASC")
            ->get(['id', 'name', 'slug'])
            ->map(fn (Tool $t) => [
                'id' => $t->id,
                'label' => $t->getTranslation('name', $locale, false) ?: (is_array($t->name) ? reset($t->name) : (string) $t->name),
            ]);

        $linkedToolIds = $article->tools->pluck('id')->all();

        return view('news::admin.articles.edit', compact('article', 'allTools', 'linkedToolIds'));
    }

    public function updateArticle(Request $request, NewsArticle $article): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news_articles')->ignore($article->id)],
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'category_tag' => 'nullable|string|max:50',
            'summary' => 'nullable|string|max:2000',
            'tool_ids' => 'nullable|array',
            'tool_ids.*' => 'integer|exists:directory_tools,id',
        ]);

        if ($request->filled('slug')) {
            $validated['slug'] = Str::slug($request->input('slug'));
        } else {
            unset($validated['slug']);
        }

        // ACTION: synchroniser les outils liés via NewsToolSyncAction (DRY)
        // MCP: SELF (<5 lignes)
        // RAISON: curation admin manuelle
        app(NewsToolSyncAction::class)->sync($article, $validated['tool_ids'] ?? []);

        unset($validated['tool_ids']);
        $article->update($validated);

        return redirect()->route('admin.news.articles.index')->with('success', __('Article mis à jour.'));
    }

    /**
     * Suggère les outils détectés automatiquement dans le contenu de l'actualité.
     * Les outils de TOOL_NEVER_AUTO sont inclus uniquement si leur nom apparaît
     * en mot entier (insensible à la casse) dans le TITRE de l'article.
     * Retourne une liste d'ids JSON (sans enregistrer - l'admin valide puis Enregistre).
     */
    public function suggestTools(NewsArticle $article): JsonResponse
    {
        // ACTION: déléguer la suggestion à NewsToolSyncAction (DRY)
        // MCP: SELF (<5 lignes)
        // RAISON: suggestion automatique pour accélérer la curation
        $allIds = app(NewsToolSyncAction::class)->suggest($article);

        return response()->json(['tool_ids' => $allIds]);
    }

    public function rescoreArticle(NewsArticle $article, AiSummaryService $aiService): RedirectResponse
    {
        // ACTION : la colonne description ne véhicule plus jamais le texte source (design doc
        // "Actus - zéro copie du texte source", 2026-08-13, section 4.1) - rescorer une fiche
        // déjà existante exige de re-télécharger sa source, gardée en mémoire le temps de cet
        // appel seulement, exactement comme news:reprocess.
        // MCP: SELF (<5 lignes utiles)
        // RAISON: sans ce re-téléchargement, cette action générerait un résumé à partir du
        // titre seul, silencieusement - le risque exact que le design doc met en garde.
        $sourceUrl = $article->resolved_url ?: $article->url;
        $extracted = app(\Modules\News\Services\ContentExtractor::class)->extract($sourceUrl);
        $sourceText = $extracted['content'] ?? '';

        // pub_date transmise pour le contrôle de cohérence des années de SummaryQualityGate
        // (2026-08-13).
        $result = $aiService->scoreAndSummarize($article->title, $sourceText, 'fr', $article->pub_date);

        if ($result) {
            $article->update([
                'relevance_score' => $result['score'] ?? $article->relevance_score,
                'score_justification' => $result['score_justification'] ?? $article->score_justification,
                'structured_summary' => $result,
                'category_tag' => $result['category'] ?? $article->category_tag,
                'impact_level' => $result['impact'] ?? $article->impact_level,
                'seo_title' => Str::limit($result['seo_title'] ?? $article->seo_title, 250, ''),
                'meta_description' => Str::limit($result['meta_description'] ?? $article->meta_description, 250, ''),
                'summary' => $result['hook'] ?? $article->summary,
            ]);

            return back()->with('success', __('Article re-score : :score/10', ['score' => $result['score'] ?? '?']));
        }

        return back()->with('error', __('Erreur lors du re-scoring IA.'));
    }

    public function destroyArticle(NewsArticle $article): RedirectResponse
    {
        $article->delete();

        return back()->with('success', __('Article supprimé.'));
    }

    public function uploadArticleImage(Request $request, NewsArticle $article, ScreenshotUploadService $uploader)
    {
        $request->validate(['screenshot' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);

        $wantsJson = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
        $slug = $article->slug ?: (string) $article->id;
        $result = $uploader->upload($request->file('screenshot'), "news-screenshots/{$slug}.jpg", $article, 'image_url');

        if ($result['ok']) {
            return $wantsJson
                ? response()->json(['ok' => true, 'message' => $result['message'], 'screenshot_url' => $result['url']])
                : back()->with('success', $result['message']);
        }

        return $wantsJson
            ? response()->json(['ok' => false, 'message' => $result['message']], 422)
            : back()->with('error', $result['message']);
    }

    /**
     * Marque une actualité comme "déjà publiée" sur LinkedIn/Facebook (point rouge admin).
     * Superadmin strict (aligné sur le gate d'affichage du menu de partage, cf. show.blade.php
     * et article-action-bar.blade.php : auth()->user()?->isSuperAdmin()). Idempotent : re-cliquer
     * met simplement à jour le timestamp, ne plante jamais.
     */
    public function markShared(Request $request, NewsArticle $article, string $platform): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        if (! in_array($platform, ['linkedin', 'facebook'], true)) {
            abort(404);
        }

        $article->forceFill(["{$platform}_shared_at" => now()])->save();

        return response()->json([
            'ok' => true,
            'platform' => $platform,
            'shared_at' => $article->{"{$platform}_shared_at"}->toIso8601String(),
        ]);
    }
}
