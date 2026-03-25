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

class PublicDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tool::published()->with('categories', 'tags')->orderBy('sort_order');

        if ($request->filled('pricing')) {
            $query->where('pricing', $request->pricing);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $locale = app()->getLocale();
            $query->where("name->{$locale}", 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where("slug->" . app()->getLocale(), $request->category));
        }

        $tools = $query->get();
        $categories = Category::orderBy('sort_order')->get();
        $pricingOptions = ['free' => __('Gratuit'), 'freemium' => __('Freemium'), 'paid' => __('Payant'), 'open_source' => __('Open source'), 'enterprise' => __('Entreprise')];

        return view('directory::public.index', compact('tools', 'categories', 'pricingOptions'));
    }

    public function show(string $slug): View
    {
        $tool = Tool::published()
            ->where('slug->' . app()->getLocale(), $slug)
            ->with('categories', 'tags')
            ->firstOrFail();

        $tool->increment('clicks_count');

        $similarTools = Tool::published()
            ->where('id', '!=', $tool->id)
            ->whereHas('categories', function ($q) use ($tool) {
                $q->whereIn('directory_categories.id', $tool->categories->pluck('id'));
            })
            ->limit(4)
            ->get();

        return view('directory::public.show', compact('tool', 'similarTools'));
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
        if (! auth()->check()) {
            return response()->json(['auth_required' => true, 'redirect' => route('magic-link.request')], 401);
        }

        $validated = $request->validate([
            'url' => 'required|url',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'short_description' => 'nullable|string|max:255',
            'pricing' => 'required|in:free,freemium,paid,open_source,enterprise',
            'categories' => 'nullable|array',
            'screenshot' => 'nullable|url|max:500',
        ]);

        $locale = app()->getLocale();
        $tool = new Tool();
        $tool->url = $validated['url'];
        $tool->pricing = $validated['pricing'];
        $tool->status = 'pending';
        $tool->screenshot = $validated['screenshot'] ?? null;
        $tool->is_featured = false;

        $tool->setTranslation('name', $locale, $validated['name']);
        $tool->setTranslation('slug', $locale, Str::slug($validated['name']));
        $tool->setTranslation('description', $locale, $validated['description'] ?? '');
        $tool->setTranslation('short_description', $locale, $validated['short_description'] ?? Str::limit($validated['description'] ?? '', 200));
        $tool->save();

        if (! empty($validated['categories'])) {
            $tool->categories()->sync($validated['categories']);
        }

        return response()->json(['success' => true, 'message' => __('Merci ! Votre proposition sera examinée par notre équipe.')]);
    }
}
