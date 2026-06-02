<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Newsletter\Models\NewsletterPromptPreset;
use Modules\Newsletter\Services\NewsletterPromptBuilder;

class PromptBuilderController extends Controller
{
    public function __construct(private readonly NewsletterPromptBuilder $builder)
    {
    }

    public function index(): View
    {
        $presets = NewsletterPromptPreset::orderBy('name')->get();

        $recentNewsArticles = collect();
        if (class_exists(\Modules\News\Models\NewsArticle::class)) {
            $recentNewsArticles = \Modules\News\Models\NewsArticle::where('pub_date', '>=', now()->subDays(30))
                ->orderByDesc('pub_date')
                ->limit(30)
                ->get(['id', 'title', 'seo_title', 'url', 'pub_date']);
        }

        $recentBlogArticles = collect();
        if (class_exists(\Modules\Blog\Models\Article::class)) {
            $recentBlogArticles = \Modules\Blog\Models\Article::where('published_at', '>=', now()->subDays(30))
                ->orderByDesc('published_at')
                ->limit(20)
                ->get(['id', 'title', 'slug', 'published_at']);
        }

        $defaultPreset = NewsletterPromptPreset::loadDefault();

        return view('newsletter::admin.prompt-builder.index', compact(
            'presets',
            'recentNewsArticles',
            'recentBlogArticles',
            'defaultPreset'
        ));
    }

    public function compile(Request $request): JsonResponse
    {
        $request->validate($this->blocksValidationRules());

        $blocks = $request->input('blocks', []);

        // Limite totale anti-abus
        try {
            $totalLength = mb_strlen(json_encode($blocks, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            return response()->json(['error' => 'Contenu invalide (encodage JSON).'], 422);
        }

        if ($totalLength > 8000) {
            return response()->json(['error' => 'Contenu trop long (max 8000 caractères).'], 422);
        }

        $prompt = $this->builder->compile($blocks);

        return response()->json(['prompt' => $prompt]);
    }

    public function storePreset(Request $request): RedirectResponse
    {
        // Le formulaire HTML envoie blocks comme chaîne JSON (input hidden).
        // On la décode en tableau avant la validation, qui attend 'required|array'.
        if ($request->has('blocks') && is_string($request->input('blocks'))) {
            $decoded = json_decode((string) $request->input('blocks'), associative: true);
            $request->merge(['blocks' => is_array($decoded) ? $decoded : []]);
        }

        $validated = $request->validate(array_merge(
            ['name' => 'required|string|max:150', 'is_default' => 'sometimes|boolean'],
            $this->blocksValidationRules()
        ));

        $preset = NewsletterPromptPreset::create([
            'name'    => $validated['name'],
            'blocks'  => $validated['blocks'],
        ]);

        if (! empty($validated['is_default'])) {
            $preset->setAsDefault();
        }

        return back()->with('success', 'Preset « ' . $preset->name . ' » enregistré.');
    }

    public function loadPreset(NewsletterPromptPreset $preset): JsonResponse
    {
        return response()->json(['preset' => $preset]);
    }

    public function setDefault(NewsletterPromptPreset $preset): RedirectResponse
    {
        $preset->setAsDefault();

        return back()->with('success', 'Preset « ' . $preset->name . ' » défini comme défaut.');
    }

    public function destroyPreset(NewsletterPromptPreset $preset): RedirectResponse
    {
        $name = $preset->name;
        $preset->delete();

        return back()->with('success', 'Preset « ' . $name . ' » supprimé.');
    }

    /**
     * Règles de validation communes aux blocs (réutilisées dans compile() et storePreset()).
     *
     * @return array<string, mixed>
     */
    private function blocksValidationRules(): array
    {
        return [
            'blocks'                           => 'required|array',
            'blocks.subject'                   => 'nullable|string|max:200',
            'blocks.angle'                     => 'nullable|string|max:300',
            'blocks.tone'                      => 'nullable|string|max:100',
            'blocks.audience'                  => 'nullable|string|max:200',
            'blocks.challenge_instruction'     => 'nullable|string|max:500',
            'blocks.challenge_duration'        => 'nullable|string|max:50',
            'blocks.word_count'                => ['nullable', 'in:300-500 mots,500-700 mots,700-900 mots'],
            'blocks.send_test_email'           => 'nullable|boolean',
            'blocks.test_email'                => 'nullable|email|max:254',
            'blocks.extra_notes'               => 'nullable|string|max:1000',
            'blocks.sections'                  => 'nullable|array|max:10',
            'blocks.sections.*.title'          => 'nullable|string|max:150',
            'blocks.sections.*.content'        => 'nullable|string|max:2000',
            'blocks.selected_articles'         => 'nullable|array|max:30',
            'blocks.selected_articles.*.title' => 'nullable|string|max:300',
            'blocks.selected_articles.*.url'   => 'nullable|url|max:500',
        ];
    }
}
