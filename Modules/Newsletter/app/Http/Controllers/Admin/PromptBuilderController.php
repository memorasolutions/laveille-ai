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
use Modules\Newsletter\Services\PromptBuilderSearchService;

class PromptBuilderController extends Controller
{
    public function __construct(
        private readonly NewsletterPromptBuilder $builder,
        private readonly PromptBuilderSearchService $searchService,
    ) {
    }

    public function index(): View
    {
        $presets = NewsletterPromptPreset::orderBy('name')->get();

        $defaultPreset = NewsletterPromptPreset::loadDefault();

        // Mapping des sections : source unique (sectionsMap()) exposée à la vue pour DRY
        $sectionsMeta = NewsletterPromptBuilder::sectionsMap();

        // Compagnies pour les facettes news (source de vérité = config)
        $companies = config('newsletter.companies', []);

        return view('newsletter::admin.prompt-builder.index', compact(
            'presets',
            'defaultPreset',
            'sectionsMeta',
            'companies',
        ));
    }

    /**
     * Endpoint AJAX de recherche pour les combobox DB du générateur de prompt.
     *
     * Paramètres GET :
     *   type      (news|tool|term|article|interactive_tool)
     *   q         texte libre
     *   date_from (YYYY-MM-DD, news uniquement)
     *   date_to   (YYYY-MM-DD, news uniquement)
     *   company   (texte, news uniquement)
     *
     * @return JsonResponse [{id, label, sublabel?}]
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'      => 'required|in:news,tool,term,article,interactive_tool',
            'q'         => 'nullable|string|max:200',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d',
            'company'   => 'nullable|string|max:200',
        ]);

        $results = $this->searchService->search(
            type:     $validated['type'],
            q:        $validated['q'] ?? '',
            dateFrom: $validated['date_from'] ?? null,
            dateTo:   $validated['date_to'] ?? null,
            company:  $validated['company'] ?? null,
        );

        return response()->json(['results' => $results]);
    }

    public function compile(Request $request): JsonResponse
    {
        $validated = $request->validate($this->blocksValidationRules());

        $blocks = $validated['blocks'] ?? [];

        // Limite totale anti-abus (les max: par champ protègent déjà le détail ;
        // cette garde globale couvre les cas limites d'accumulation).
        try {
            $totalLength = mb_strlen(json_encode($blocks, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            return response()->json(['error' => 'Contenu invalide (encodage JSON).'], 422);
        }

        if ($totalLength > 20000) {
            return response()->json(['error' => 'Contenu trop long (max 20 000 caractères).'], 422);
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
     * Structure attendue :
     *   blocks.subject      string  Objet du courriel
     *   blocks.test_email   email   Adresse de test
     *   blocks.extra_notes  string  Notes libres
     *   blocks.sections     array   Clé = section_key, valeur = {mode, value}
     *
     * @return array<string, mixed>
     */
    private function blocksValidationRules(): array
    {
        $validSectionKeys = array_keys(NewsletterPromptBuilder::sectionsMap());
        $keysIn           = implode(',', $validSectionKeys);

        $rules = [
            'blocks'            => 'required|array',
            'blocks.subject'    => 'nullable|string|max:45',
            'blocks.test_email' => 'nullable|email|max:254',
            'blocks.extra_notes'=> 'nullable|string|max:1000',
            'blocks.sections'   => 'nullable|array',
        ];

        // Règles par section : mode (auto|custom) + value texte libre
        foreach ($validSectionKeys as $key) {
            $rules['blocks.sections.' . $key]         = 'nullable|array';
            $rules['blocks.sections.' . $key . '.mode']  = 'nullable|in:auto,custom';
            $rules['blocks.sections.' . $key . '.value'] = 'nullable|string|max:2000';
        }

        return $rules;
    }
}
