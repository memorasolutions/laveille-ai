<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Tools\Models\SavedCrosswordPreset;
use Modules\Tools\Services\CrosswordAiSuggestionService;
use Modules\Tools\Services\CrosswordGeneratorService;

class PublicCrosswordController
{
    public function __construct(
        private CrosswordGeneratorService $generator,
        private CrosswordAiSuggestionService $aiSuggester,
    ) {}

    public function aiSuggestPairs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => 'required|string|max:100',
            'count' => 'nullable|integer|min:5|max:15',
        ]);

        $pairs = $this->aiSuggester->generatePairsForTheme(
            $validated['theme'],
            (int) ($validated['count'] ?? 10),
        );

        if (empty($pairs)) {
            return response()->json([
                'success' => false,
                'error' => 'Impossible de générer des suggestions pour ce thème. Réessayez avec un thème plus précis.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'pairs' => $pairs,
            'count' => count($pairs),
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pairs' => 'required|array|min:2|max:50',
            'pairs.*.clue' => 'required|string|max:250',
            'pairs.*.answer' => 'required|string|min:2|max:30',
        ]);

        try {
            $result = $this->generator->generate($validated['pairs']);
            return response()->json($result);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function play(string $publicId): View|RedirectResponse
    {
        $preset = SavedCrosswordPreset::where('public_id', $publicId)
            ->where('is_public', true)
            ->first();

        if (! $preset) {
            return redirect()->route('tools.crossword.index')
                ->with('error', 'Cette grille n\'existe pas ou n\'est pas publique.');
        }

        return view('tools::public.tools.crossword.jeu', [
            'preset' => $preset,
            'pageTitle' => $preset->name.' — Jouer en ligne',
            'pageDescription' => 'Résolvez la grille de mots croisés "'.$preset->name.'" en ligne sur laveille.ai.',
        ]);
    }
}
