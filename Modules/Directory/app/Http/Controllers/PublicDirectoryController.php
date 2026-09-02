<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\MetaScraperService;
use Modules\Core\Services\TranslationService;
use Modules\Core\Services\ViewCounterService;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingReport;
use Modules\Directory\Services\DuplicateDetectorService;
use Modules\Directory\Services\EcosystemCountService;
use Modules\Directory\Services\EcosystemResolverService;
use Modules\Directory\Support\PricingCategories;
use Modules\Settings\Facades\Settings;

class PublicDirectoryController extends Controller
{
    /**
     * Longueur minimale (caractères) sous laquelle la description « en bref » est considérée
     * comme un fragment plutôt qu'un vrai résumé - audit AdSense « contenu de faible valeur »,
     * spec 2026-08-20. Seuil de jugement (aucune convention existante trouvée dans le projet
     * pour ce champ) : une phrase courte réelle dépasse largement 40 caractères (ex. seeder
     * DirectorySession127ToolsSeeder : 60-90 caractères par outil).
     * Public : réutilisé tel quel par Modules/SEO/app/Http/Controllers/SitemapController.php
     * pour que le sitemap exclue exactement les mêmes fiches minces que le noindex (DRY, une
     * seule source de vérité pour ce seuil).
     */
    public const THIN_SHORT_DESCRIPTION_MAX_LENGTH = 40;

    public function index(Request $request): View
    {
        // 2026-05-05 #137 : cache outils archived par defaut (S43 cleanup HN/blog/video crawler errone).
        // 2026-08-06 #1645 : toggle ?show_archived=1 reserve aux moderateurs - le public ne voit jamais les archives.
        $showArchived = $request->boolean('show_archived') && ($request->user()?->can('moderate_tools') ?? false);

        $query = Tool::published()->with('categories', 'tags')
            ->when(! $showArchived, fn ($q) => $q->notArchived())
            ->withCount(['resources as tutorials_count' => function ($q) {
                $q->where('is_approved', 1)
                  ->whereIn('type', ['youtube', 'video', 'tutorial', 'formation']);
            }]);

        $query = match (\Modules\Settings\Facades\Settings::get('directory.default_sort', 'random')) {
            'popular' => $query->orderByDesc('clicks_count'),
            'recent' => $query->orderByDesc('created_at'),
            'name' => $query->orderBy('name->fr_CA'),
            default => $query->inRandomOrder(),
        };

        $tools = $this->applyDirectoryFilters($query, $request)->get();

        $categories = Category::orderBy('sort_order')->get();
        $pricingOptions = \Modules\Directory\Support\PricingCategories::optionsWithEducation();

        // 2026-07-23 S135 : comptes agrégés par écosystème (badge "Éditeur · N produits" sur
        // les cartes) + libellés affichables. Logique métier au contrôleur (pas en vue),
        // service caché indéfiniment et invalidé par ToolObserver — une seule requête agrégée
        // par requête HTTP, jamais recalculée dans la vue.
        $ecosystemCounts = app(EcosystemCountService::class)->counts();
        $ecosystemLabels = config('ecosystems.labels', []);

        // 2026-05-05 #135 : eager-load tutorials_count pour featured + topVoted + recent + popular (DRY closure)
        $tutorialsCountClosure = fn ($q) => $q->where('is_approved', 1)->whereIn('type', ['youtube', 'video', 'tutorial', 'formation']);

        $featuredQuery = Tool::published()->featured()->with('categories')
            ->when(! $showArchived, fn ($q) => $q->notArchived())
            ->withCount(['resources as tutorials_count' => $tutorialsCountClosure])
            ->orderBy('sort_order');
        $featuredTools = $this->applyDirectoryFilters($featuredQuery, $request)->get();
        $recentTools = Tool::published()->with('categories')
            ->when(! $showArchived, fn ($q) => $q->notArchived())
            ->withCount(['resources as tutorials_count' => $tutorialsCountClosure])
            ->orderByDesc('created_at')->distinct()->limit((int) Settings::get('directory.recent_tools_limit', 6))->get();
        $recentIds = $recentTools->pluck('id')->toArray();
        $popularTools = Tool::published()->with('categories')
            ->when(! $showArchived, fn ($q) => $q->notArchived())
            ->withCount(['resources as tutorials_count' => $tutorialsCountClosure])
            ->whereNotIn('id', $recentIds)->orderByDesc('clicks_count')->distinct()->limit((int) Settings::get('directory.popular_tools_limit', 6))->get();

        // 2026-05-05 #137 : count des outils archived pour afficher dans le toggle.
        // 2026-08-06 #1645 : 0 pour le public - la vue masque alors le lien « Voir les X outils archivés ».
        $archivedCount = ($request->user()?->can('moderate_tools') ?? false)
            ? Tool::published()->where('lifecycle_status', 'archived')->count()
            : 0;

        // Plus votés par la communauté (si module Voting actif)
        //
        // Mandat #1939 (2026-08-31) : ->having('community_votes_count', ...) référençait un
        // ALIAS de withCount() sans GROUP BY - MySQL l'accepte (extension non standard), sqlite
        // le refuse ("HAVING clause on a non-aggregate query"). Ce n'était pas une fonction
        // manquante mais une dépendance à la permissivité MySQL : /annuaire (directory.index)
        // était donc IMPOSSIBLE à atteindre en HTTP dans la suite de tests (sqlite :memory:,
        // phpunit.xml), sur CETTE requête précise seulement - published()/notArchived()/
        // withCount() seuls sont portables. ->has('communityVotes', '>', 0) exprime le même
        // filtre ("au moins un vote") via un WHERE EXISTS-style portable, déjà l'idiome retenu
        // plus bas dans ce même fichier (compare(), Category::...->has('tools')) - jamais un
        // second mécanisme. community_votes_count reste sélectionné par withCount() pour
        // l'affichage et le tri (orderByDesc), inchangé.
        $topVoted = collect();
        if (trait_exists(\Modules\Voting\Traits\HasCommunityVotes::class)) {
            $topVoted = Tool::published()->with('categories')
                ->when(! $showArchived, fn ($q) => $q->notArchived())
                ->withCount(['communityVotes', 'resources as tutorials_count' => $tutorialsCountClosure])
                ->has('communityVotes', '>', 0)
                ->orderByDesc('community_votes_count')
                ->limit((int) Settings::get('directory.top_voted_tools_limit', 6))->get();
        }

        $userCollections = collect();
        if (auth()->check() && class_exists(\Modules\Directory\Models\ToolCollection::class)) {
            $userCollections = \Modules\Directory\Models\ToolCollection::forUser((int) auth()->id())
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'is_public']);
        }

        return view('directory::public.index', compact('tools', 'categories', 'pricingOptions', 'featuredTools', 'recentTools', 'popularTools', 'topVoted', 'userCollections', 'showArchived', 'archivedCount', 'ecosystemCounts', 'ecosystemLabels'));
    }

    public function educationPricing(Request $request): View
    {
        $allowedAudiences = ['K12', 'higher_ed', 'district', 'homeschool', 'individual_teacher'];

        $audience = $request->input('audience');

        if ($audience && ! in_array($audience, $allowedAudiences, true)) {
            $audience = null;
        }

        $query = Tool::published()
            ->where(function ($q) {
                $q->where('pricing', 'education')->orWhere('has_education_pricing', true);
            })
            ->with('categories')
            ->orderByDesc('clicks_count');

        if ($audience) {
            $query->whereJsonContains('education_target_audience', $audience);
        }

        $tools = $query->get();

        return view('directory::public.tarifs-education', compact('tools', 'audience'));
    }

    public function compare(\Illuminate\Http\Request $request, ?string $categorySlug = null): \Illuminate\Contracts\Support\Renderable
    {
        $service = app(\Modules\Directory\Services\ToolComparisonService::class);
        $rawIds = $request->query('ids', '');
        $ids = $service->validateIds(is_array($rawIds) ? $rawIds : (string) $rawIds);

        $category = null;
        $allCategories = Category::orderBy('sort_order')->has('tools')->get();

        if (empty($ids) && $categorySlug) {
            $category = Category::where('slug->fr_CA', $categorySlug)->firstOrFail();
            $ids = $category->tools()->published()->notArchived()
                ->orderByDesc('clicks_count')
                ->limit(\Modules\Directory\Services\ToolComparisonService::MAX_TOOLS)
                ->pluck('directory_tools.id')
                ->all();
        }

        $tools = $service->loadTools($ids);
        $criteria = $service->getCriteriaSchema();
        $pricingLabels = \Modules\Directory\Support\PricingCategories::labels();
        $mismatch = $service->computeMismatch($tools);
        $classification = $tools->count() >= 2 ? $service->classifyCriteria($tools, $criteria) : [];

        return view('directory::public.compare', compact('tools', 'category', 'allCategories', 'pricingLabels', 'criteria', 'service', 'mismatch', 'classification'));
    }

    public function show(string $slug): View|RedirectResponse
    {
        $tool = Tool::published()
            ->where('slug->'.app()->getLocale(), $slug)
            ->with('categories', 'tags')
            ->firstOrFail();

        // Doublon fusionné : rediriger définitivement vers l'outil canonique (évite le contenu dupliqué)
        if ($tool->lifecycle_status === 'archived' && $tool->lifecycle_replacement_tool_id) {
            $canonical = Tool::published()->find($tool->lifecycle_replacement_tool_id);
            if ($canonical && $canonical->lifecycle_status !== 'archived') {
                return redirect($canonical->getPublicUrl(), 301);
            }
        }

        // 2026-08-06 #1645 : fiche archivée sans remplaçant valide = invisible au public (404),
        // seuls les modérateurs peuvent encore la consulter (les données restent intactes en base).
        if ($tool->lifecycle_status === 'archived' && ! (request()->user()?->can('moderate_tools') ?? false)) {
            abort(404);
        }

        // 2026-08-28 - incident 2026-08-13 (recoupement GA4) : increment() brut comptait les
        // robots (jusqu'à 652x le trafic humain réel selon la fiche). Délégué au service partagé
        // (tri anti-robot + déduplication courte fenêtre), déjà utilisé par Tools/Authors/News/
        // Dictionary - incrémente aussi clicks_count_verified, le compteur "propre" jumeau.
        ViewCounterService::record($tool, 'clicks_count');

        $limit = (int) Settings::get('directory.similar_tools_limit', 8);

        $alternatives = $tool->allAlternatives()
            ->where('id', '!=', $tool->id);

        $similarByCategory = Tool::published()
            ->where('id', '!=', $tool->id)
            ->whereNotIn('id', $alternatives->pluck('id'))
            ->whereHas('categories', function ($q) use ($tool) {
                $q->whereIn('directory_categories.id', $tool->categories->pluck('id'));
            })
            ->limit($limit)
            ->get();

        $similarTools = $alternatives
            ->merge($similarByCategory)
            ->unique('id')
            ->take($limit)
            ->values();

        // withCount : evite un count(*) par bouton de vote rendu (42 mesures le 2026-08-26).
        $resources = $tool->resources()
            ->withCount('communityVotes')
            ->where('is_approved', true)
            ->orderByRaw("FIELD(language, 'fr', 'en') ASC")
            ->orderByDesc('created_at')
            ->get();

        $relatedCollections = collect();
        if (class_exists(\Modules\Directory\Models\ToolCollection::class)) {
            $relatedCollections = \Modules\Directory\Models\ToolCollection::public()
                ->whereHas('tools', fn ($q) => $q->where('directory_tools.id', $tool->id))
                ->withCount('tools')
                ->limit(6)
                ->get();
        }

        // ACTION: charger les actualités liées publiées pour l'onglet Actualités
        // MCP: SELF (<5 lignes)
        // RAISON: garde-fou class_exists (portabilité module News), booléen $hasMoreNews évite le count() dans la vue
        $toolNewsArticles = collect();
        $hasMoreNews = false;
        if (class_exists(\Modules\News\Models\NewsArticle::class) && Schema::hasTable('news_article_tool')) {
            $toolNewsArticles = $tool->newsArticles()->published()->latest('pub_date')->limit(13)->get();
            $hasMoreNews = $toolNewsArticles->count() === 13;
            if ($hasMoreNews) {
                $toolNewsArticles = $toolNewsArticles->take(12);
            }
        }

        // ACTION: expose la presence du master + sa hauteur reelle pour le bouton public "Recadrer"
        // (volet B, focal-cropper). Meme lecture disque que DirectoryAdminController::edit() -
        // jamais de nouvelle colonne, jamais de requete supplementaire hors getimagesize().
        // MCP: SELF (< 5 lignes utiles, simple lecture disque)
        // RAISON: design doc 2026-08-10 (recadrage frontend), volet B - le master ne doit JAMAIS
        // etre charge par un visiteur, ces variables restent gatees @can('moderate_tools') en vue.
        $slug = $tool->getTranslation('slug', 'fr_CA') ?: $tool->slug;
        $screenshotMasterRelative = $slug ? "screenshots/masters/{$slug}.jpg" : null;
        $hasScreenshotMaster = $screenshotMasterRelative && File::exists(public_path($screenshotMasterRelative));
        $screenshotMasterUrl = $hasScreenshotMaster ? asset($screenshotMasterRelative) : null;
        $screenshotMasterHeight = $hasScreenshotMaster
            ? (int) (@getimagesize(public_path($screenshotMasterRelative))[1] ?? 630)
            : 630;

        // ACTION: critère de « substance » pour noindex conditionnel (audit AdSense 2026-08-20,
        // fiches longue traîne minces). Une fiche RESTE indexable dès qu'UN seul signal de
        // richesse réelle est présent (catégorie assignée, description « en bref » substantielle,
        // avis, tutoriel ou screenshot approuvé) - seule l'intersection de tous les signaux
        // absents déclenche le noindex.
        // MCP: SELF (<5 lignes utiles, simples comptages sur relations déjà chargées/filtrées)
        // RAISON: aucune donnée modifiée, seulement la balise robots au rendu (DRY avec
        // page_noindex, cf. Modules/FrontTheme/resources/views/layouts/master.blade.php).
        $shortDescriptionLength = mb_strlen(trim(strip_tags((string) ($tool->short_description ?? ''))));
        $isThinTool = $tool->categories->isEmpty()
            && $shortDescriptionLength < self::THIN_SHORT_DESCRIPTION_MAX_LENGTH
            && $tool->reviews()->approved()->count() === 0
            && $resources->count() === 0
            && $tool->screenshots()->approved()->count() === 0;

        return view('directory::public.show', compact('tool', 'similarTools', 'resources', 'relatedCollections', 'toolNewsArticles', 'hasMoreNews', 'hasScreenshotMaster', 'screenshotMasterUrl', 'screenshotMasterHeight', 'isThinTool'));
    }

    /**
     * Tracking de clic sortant réel (distinct de clicks_count, qui compte les VUES de la fiche
     * dans show()). Incrémente outbound_clicks_count puis redirige vers getVisitUrl() (URL
     * d'affiliation si affiliate_url est renseigné, sinon URL directe — fallback automatique
     * inchangé). Même pattern de résolution du slug que show() (aucun repli de locale non plus
     * dans show() à ce jour — vérifié avant d'écrire cette méthode).
     */
    public function visit(string $slug): RedirectResponse
    {
        $tool = Tool::published()
            ->where('slug->'.app()->getLocale(), $slug)
            ->firstOrFail();

        $tool->increment('outbound_clicks_count');

        return redirect($tool->getVisitUrl());
    }

    /**
     * Page de divulgation des liens d'affiliation — calquée sur TakedownController::policy().
     */
    public function affiliationPolicy(): View
    {
        return view('directory::public.affiliation-policy');
    }

    /**
     * API : scrape une URL + detecte doublons (appele en AJAX depuis le wizard).
     */
    public function scrapeAndDetect(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url']);

        try {
            $scraped = MetaScraperService::scrape($request->url);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $screenshot = MetaScraperService::captureScreenshot($request->url, $scraped);
        $name = $scraped['og_title'] ?? $scraped['title'] ?? '';

        $duplicates = DuplicateDetectorService::findDuplicates($request->url, $name);

        // Traduire la description si en anglais
        $description = $scraped['og_description'] ?? $scraped['description'] ?? '';
        $translatedDescription = $description;
        if ($description && class_exists(TranslationService::class)) {
            $translatedDescription = TranslationService::translate($description, 'en', 'fr');
        }

        // Traduire le titre si en anglais
        $translatedName = $name;
        if ($name && class_exists(TranslationService::class)) {
            $translatedName = TranslationService::translate($name, 'en', 'fr');
        }

        return response()->json([
            'scraped' => $scraped,
            'screenshot' => $screenshot,
            'translated_name' => $translatedName,
            'translated_description' => $translatedDescription,
            'original_name' => $name,
            'original_description' => $description,
            'duplicates' => $duplicates->map(fn ($d) => [
                'id' => $d['tool']->id,
                'name' => $d['tool']->name,
                'url' => $d['tool']->url,
                'slug' => $d['tool']->slug,
                'confidence' => $d['confidence'],
            ])->values(),
        ]);
    }

    /**
     * Soumission d'un nouvel outil par un utilisateur connecte.
     */
    public function storeSubmission(Request $request): JsonResponse
    {
        // Auth requise via middleware — ce check est un fallback de sécurité
        if (! auth()->check()) {
            abort(401);
        }

        // Ticket #1868 - Cloudflare Turnstile, couche SUPPLÉMENTAIRE (le vrai trou était la
        // publication sans relecture, déjà bouché par la porte de modération plus bas dans
        // cette méthode). Service RÉUTILISÉ tel quel (Modules\Authors\Services\
        // TurnstileVerificationService, déjà branché sur l'infolettre auteur) - jamais réécrit
        // ici. class_exists() car Directory ne dépend normalement pas du module Authors : un
        // Authors désactivé ne doit jamais bloquer une soumission d'outil.
        //
        // Deux niveaux de bypass, tous deux graceful (le formulaire fonctionne toujours) :
        //   1) directory.turnstile.enabled (config/config.php de ce module) - coupe-circuit
        //      DÉDIÉ, indépendant des clés Cloudflare : à poser sur false en cas de panne
        //      Cloudflare ou de mauvaise configuration, sans toucher aux clés ni redéployer.
        //   2) TurnstileVerificationService::isEnabled() - vrai seulement si les deux clés
        //      Cloudflare sont configurées (absentes en local ET en production au 2026-08-31 -
        //      ce correctif est donc livré INACTIF tant que Stéphane n'a pas créé le widget
        //      côté Cloudflare, voir .env.example).
        if (
            config('directory.turnstile.enabled', true)
            && class_exists(\Modules\Authors\Services\TurnstileVerificationService::class)
        ) {
            $turnstile = app(\Modules\Authors\Services\TurnstileVerificationService::class);

            if ($turnstile->isEnabled()) {
                $turnstileToken = $request->input('cf-turnstile-response');

                if (! $turnstile->verify($turnstileToken, $request->ip())) {
                    // Canal dédié 'directory_antibot' (config/logging.php) : LOG_LEVEL=error
                    // en production avale les messages 'info' du canal par défaut - un refus
                    // anti-abus sans trace ne peut ni être réglé ni être disculpé.
                    \Illuminate\Support\Facades\Log::channel('directory_antibot')->info('directory.submit.turnstile_rejected', [
                        'user_id' => auth()->id(),
                        'has_token' => ! empty($turnstileToken),
                        'ip' => $request->ip(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => __('Validation anti-robot échouée. Réessaie en rafraîchissant la page.'),
                    ], 422);
                }
            }
        }

        $validated = $request->validate([
            'url' => 'required|url',
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:2000',
            'short_description' => 'nullable|string|max:160',
            'pricing' => 'required|in:free,freemium,paid,open_source,enterprise',
            'categories' => 'nullable|array',
            'screenshot' => 'nullable|url|max:500',
            'has_education_pricing' => 'nullable|boolean',
            'education_pricing_type' => 'nullable|in:free,discount',
            'education_pricing_details' => 'nullable|string|max:500',
            'education_pricing_url' => 'nullable|url|max:500',
            'collection_ids' => 'nullable|array',
            'collection_ids.*' => 'integer',
            'new_collection_name' => 'nullable|string|max:100',
        ], [
            'name.max' => 'Le nom doit tenir en 80 caractères. Évite les taglines (exemple : "Wooclap", pas "Plateforme de présentation interactive Wooclap").',
            'short_description.max' => 'Le résumé court doit tenir en 160 caractères (format Twitter).',
        ]);

        $locale = app()->getLocale();

        // 2026-08-28 - une soumission publique publiait TOUJOURS en direct, sans relecture
        // (incident constaté : 6 fiches d'un même compte à valider en lot). Même mécanisme de
        // droit que le reste de ce contrôleur (lignes 47, 95, 190 : $request->user()?->can('moderate_tools'))
        // et que la porte d'admin (Modules/Directory/routes/web.php:89, 'can:moderate_tools') -
        // un modérateur garde la publication directe, les autres passent par la file de
        // modération déjà notifiée plus bas (ToolSubmittedNotification) et visible sur
        // /admin/directory?status=pending.
        $canPublishDirectly = $request->user()?->can('moderate_tools') ?? false;

        $tool = new Tool;
        $tool->url = $validated['url'];
        $tool->pricing = $validated['pricing'];
        $tool->status = $canPublishDirectly ? 'published' : 'pending';
        $tool->screenshot = $validated['screenshot'] ?? null;
        $tool->is_featured = false;
        $tool->submitted_by = auth()->id();

        // Pré-remplissage auto de l'écosystème (ex. "openai") depuis l'URL soumise — reste
        // modifiable ensuite via l'admin, ne bloque jamais la soumission si non détecté.
        $tool->ecosystem_tag = app(EcosystemResolverService::class)->resolve($validated['url']);

        $tool->setTranslation('name', $locale, $validated['name']);
        $tool->setTranslation('slug', $locale, Str::slug($validated['name']));
        $tool->setTranslation('description', $locale, $validated['description'] ?? '');
        $tool->setTranslation('short_description', $locale, $validated['short_description'] ?? Str::limit($validated['description'] ?? '', 200));

        if (! empty($validated['has_education_pricing'])) {
            $tool->has_education_pricing = true;
            $tool->education_pricing_type = $validated['education_pricing_type'] ?? null;
            $tool->education_pricing_url = $validated['education_pricing_url'] ?? null;
            if (! empty($validated['education_pricing_details'])) {
                $tool->setTranslation('education_pricing_details', $locale, $validated['education_pricing_details']);
            }
        }

        $tool->save();

        if (! empty($validated['categories'])) {
            $tool->categories()->sync($validated['categories']);
        }

        // Attachement aux collections utilisateur (module désactivable)
        if (class_exists(\Modules\Directory\Models\ToolCollection::class)) {
            try {
                $userId = (int) auth()->id();

                if (! empty($validated['collection_ids'])) {
                    $safeIds = array_map('intval', $validated['collection_ids']);
                    $collections = \Modules\Directory\Models\ToolCollection::forUser($userId)
                        ->whereIn('id', $safeIds)
                        ->get();

                    foreach ($collections as $collection) {
                        if ((int) $collection->user_id === $userId) {
                            $collection->addTool($tool->id);
                        }
                    }
                }

                if (! empty($validated['new_collection_name'])) {
                    $newCollection = \Modules\Directory\Models\ToolCollection::create([
                        'user_id' => $userId,
                        'name' => $validated['new_collection_name'],
                        'is_public' => false,
                    ]);
                    $newCollection->addTool($tool->id);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    '[Directory] Échec attachement collection lors de la soumission',
                    ['tool_id' => $tool->id, 'error' => $e->getMessage()]
                );
            }
        }

        // Notifier les admins
        if (class_exists(\Modules\Directory\Notifications\ToolSubmittedNotification::class)) {
            $admins = \App\Models\User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->get();
            foreach ($admins as $admin) {
                $admin->notify(new \Modules\Directory\Notifications\ToolSubmittedNotification($tool, auth()->user()));
            }
        }

        $message = $canPublishDirectly
            ? __('Merci! Votre outil a été ajouté au répertoire.')
            : __('Merci! Votre outil a été soumis et sera publié dès sa validation par l\'équipe.');

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function storePricingReport(Request $request, string $slug): RedirectResponse
    {
        // #1985 - Tool::published() manquait ici : une fiche brouillon/en attente/archivée était
        // atteignable par qui devine ou connaît son slug (même scope que show()/visit() plus haut
        // dans ce contrôleur). Groupé pour que le published() s'applique aux DEUX branches du OR.
        $tool = Tool::query()
            ->published()
            ->where(function ($query) use ($slug) {
                $query->where('slug->fr_CA', $slug)
                    ->orWhere('slug', $slug);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'reported_pricing' => ['required', 'string', Rule::in(PricingCategories::values())],
            'evidence_url' => ['nullable', 'url', 'max:500'],
            'user_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ToolPricingReport::create([
            'tool_id' => $tool->id,
            'user_id' => auth()->id(),
            'reported_pricing' => $validated['reported_pricing'],
            'current_pricing_snapshot' => $tool->pricing,
            'evidence_url' => $validated['evidence_url'] ?? null,
            'user_notes' => $validated['user_notes'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Merci pour votre signalement. Il sera examiné par un administrateur.'));
    }

    private function applyDirectoryFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('pricing')) {
            if ($request->pricing === 'education') {
                $query->where('has_education_pricing', true);
            } else {
                $query->where('pricing', $request->pricing);
            }
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $locale = app()->getLocale();
            $query->where("name->{$locale}", 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug->'.app()->getLocale(), $request->category));
        }

        return $query;
    }
}
