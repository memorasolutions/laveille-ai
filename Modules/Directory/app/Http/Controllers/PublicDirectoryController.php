<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\Services\MetaScraperService;
use Modules\Core\Services\TranslationService;
use Modules\Directory\Models\Category;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\DuplicateDetectorService;
use Modules\Settings\Facades\Settings;

class PublicDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tool::published()->with('categories', 'tags')->orderByDesc('clicks_count');

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

        $tools = $query->get();
        $categories = Category::orderBy('sort_order')->get();
        $pricingOptions = ['free' => __('Gratuit'), 'freemium' => __('Freemium'), 'paid' => __('Payant'), 'open_source' => __('Open source'), 'enterprise' => __('Entreprise'), 'education' => __('🎓 Tarif éducation')];

        $featuredTools = Tool::published()->featured()->with('categories')->orderBy('sort_order')->get();
        $recentTools = Tool::published()->with('categories')->orderByDesc('created_at')->distinct()->limit((int) Settings::get('directory.recent_tools_limit', 6))->get();
        $recentIds = $recentTools->pluck('id')->toArray();
        $popularTools = Tool::published()->with('categories')->whereNotIn('id', $recentIds)->orderByDesc('clicks_count')->distinct()->limit((int) Settings::get('directory.popular_tools_limit', 6))->get();

        // Plus votés par la communauté (si module Voting actif)
        $topVoted = collect();
        if (trait_exists(\Modules\Voting\Traits\HasCommunityVotes::class)) {
            $topVoted = Tool::published()->with('categories')
                ->withCount('communityVotes')
                ->having('community_votes_count', '>', 0)
                ->orderByDesc('community_votes_count')
                ->limit((int) Settings::get('directory.top_voted_tools_limit', 6))->get();
        }

        $userCollections = collect();
        if (auth()->check() && class_exists(\Modules\Directory\Models\ToolCollection::class)) {
            $userCollections = \Modules\Directory\Models\ToolCollection::forUser((int) auth()->id())
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'is_public']);
        }

        return view('directory::public.index', compact('tools', 'categories', 'pricingOptions', 'featuredTools', 'recentTools', 'popularTools', 'topVoted', 'userCollections'));
    }

    public function compare(string $categorySlug): View
    {
        $category = Category::where('slug->fr_CA', $categorySlug)->firstOrFail();
        $tools = $category->tools()->published()->with('categories')->orderByDesc('clicks_count')->get();
        $allCategories = Category::orderBy('sort_order')->has('tools')->get();
        $pricingLabels = ['free' => __('Gratuit'), 'freemium' => 'Freemium', 'paid' => __('Payant'), 'open_source' => 'Open source', 'enterprise' => 'Enterprise'];

        return view('directory::public.compare', compact('category', 'tools', 'allCategories', 'pricingLabels'));
    }

    public function show(string $slug): View
    {
        $tool = Tool::published()
            ->where('slug->'.app()->getLocale(), $slug)
            ->with('categories', 'tags')
            ->firstOrFail();

        $tool->increment('clicks_count');

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

        $resources = $tool->resources()
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

        return view('directory::public.show', compact('tool', 'similarTools', 'resources', 'relatedCollections'));
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
        $tool = new Tool;
        $tool->url = $validated['url'];
        $tool->pricing = $validated['pricing'];
        $tool->status = 'published';
        $tool->screenshot = $validated['screenshot'] ?? null;
        $tool->is_featured = false;
        $tool->submitted_by = auth()->id();

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

        return response()->json(['success' => true, 'message' => __('Merci ! L\'outil a été ajouté au répertoire.')]);
    }
}
