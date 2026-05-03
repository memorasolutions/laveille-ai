<?php

declare(strict_types=1);

namespace Modules\Tools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Tools\Models\SavedCrosswordPreset;
use Modules\Tools\Services\CrosswordAiSuggestionService;
use Modules\Tools\Services\CrosswordCsvService;
use Modules\Tools\Services\CrosswordGeneratorService;
use Modules\Tools\Services\CrosswordPdfService;

class PublicCrosswordController
{
    public function __construct(
        private CrosswordGeneratorService $generator,
        private CrosswordAiSuggestionService $aiSuggester,
        private CrosswordPdfService $pdf,
        private CrosswordCsvService $csv,
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
                'error' => 'Tous les modèles IA gratuits sont temporairement saturés. Réessayez dans 1-2 minutes, ou saisissez vos paires manuellement plus bas.',
            ], 503);
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
            'seed' => 'nullable|integer|min:0|max:2147483647',
        ]);

        try {
            $result = $this->generator->generate($validated['pairs'], $validated['seed'] ?? null);
            return response()->json($result);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function pdfBlank(Request $request): Response|JsonResponse
    {
        return $this->pdfResponse($request, blank: true);
    }

    public function pdfSolution(Request $request): Response|JsonResponse
    {
        return $this->pdfResponse($request, blank: false);
    }

    private function pdfResponse(Request $request, bool $blank): Response|JsonResponse
    {
        // S80 #58 debug : canal dédié crossword pour tracer chaque appel.
        Log::channel('crossword')->info('pdfResponse HIT', [
            'blank' => $blank,
            'user_email' => optional($request->user())->email,
            'user_id' => optional($request->user())->id,
            'accept' => $request->header('Accept'),
            'content_type' => $request->header('Content-Type'),
            'has_csrf_header' => $request->hasHeader('X-CSRF-TOKEN'),
            'csrf_match' => $request->hasHeader('X-CSRF-TOKEN') && $request->header('X-CSRF-TOKEN') === csrf_token(),
            'pairs_count' => is_array($request->input('pairs')) ? count($request->input('pairs')) : 0,
            'title' => $request->input('title'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);

        try {
            $validated = $request->validate([
                'pairs' => 'required|array|min:2|max:50',
                'pairs.*.clue' => 'required|string|max:250',
                'pairs.*.answer' => 'required|string|min:2|max:30',
                'seed' => 'nullable|integer|min:0|max:2147483647',
                'title' => 'nullable|string|max:120',
                'inactive_style' => 'nullable|in:black,gray,border',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            Log::channel('crossword')->error('pdfResponse validation FAIL', [
                'errors' => $ve->errors(),
                'first_pair_sample' => is_array($request->input('pairs')) ? ($request->input('pairs.0') ?? null) : null,
            ]);
            // Force JSON response 422 même si Accept != json (sinon Laravel redirect 302 back, fetch suit, reçoit HTML)
            return response()->json(['success' => false, 'errors' => $ve->errors(), 'error' => 'Données invalides : '.collect($ve->errors())->flatten()->first()], 422);
        }

        try {
            $result = $this->generator->generate($validated['pairs'], $validated['seed'] ?? null);
            if (empty($result['success'])) {
                return response()->json(['success' => false, 'error' => 'Aucun mot placable.'], 422);
            }
            $userTitle = trim((string) ($validated['title'] ?? ''));
            // Détection titre générique : vide OU 'Mots croisés/croises' avec ou sans accents
            $normalized = strtolower(strtr($userTitle, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'É' => 'e']));
            $isGeneric = $userTitle === '' || in_array($normalized, ['mots croises', 'mot croises'], true);
            $title = $isGeneric ? 'Mots croisés' : $userTitle;
            $inactiveStyle = $validated['inactive_style'] ?? 'black';
            $bin = $blank
                ? $this->pdf->renderBlank($result, $title, null, $inactiveStyle)
                : $this->pdf->renderSolution($result, $title.' — Corrigé', null, $inactiveStyle);
            $titleForFile = $isGeneric
                ? 'mots-croises-'.now()->format('Y-m-d')
                : trim(strtolower(preg_replace('/[^a-z0-9_-]/i', '-', $userTitle)), '-');
            $filename = 'laveille-'.$titleForFile.'-'.($blank ? 'vierge' : 'corrige').'.pdf';

            return response($bin, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-store',
            ]);
        } catch (\Throwable $e) {
            Log::channel('crossword')->error('pdfResponse exception', [
                'msg' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function csvExport(Request $request): Response
    {
        $validated = $request->validate([
            'pairs' => 'required|array|min:1|max:50',
            'pairs.*.clue' => 'required|string|max:250',
            'pairs.*.answer' => 'required|string|min:1|max:30',
        ]);
        $csv = $this->csv->generateCsv($validated['pairs']);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mots-croises.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function csvImport(Request $request): JsonResponse
    {
        $request->validate([
            'csv' => 'nullable|string|max:50000',
            'file' => 'nullable|file|mimes:csv,txt|max:512',
        ]);
        $content = '';
        if ($request->hasFile('file')) {
            $content = file_get_contents($request->file('file')->getRealPath()) ?: '';
        } elseif ($request->filled('csv')) {
            $content = (string) $request->input('csv');
        }
        if ($content === '') {
            return response()->json(['success' => false, 'error' => 'Aucune donnée CSV reçue.'], 422);
        }
        $pairs = $this->csv->parseCsv($content);
        if (count($pairs) < 2) {
            return response()->json(['success' => false, 'error' => 'CSV doit contenir au moins 2 lignes valides (Indice;Mot).'], 422);
        }

        return response()->json(['success' => true, 'pairs' => $pairs, 'count' => count($pairs)]);
    }

    public function csvTemplate(): Response
    {
        $csv = $this->csv->generateTemplate();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele-mots-croises.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function play(string $publicId): View|RedirectResponse
    {
        $preset = SavedCrosswordPreset::where('public_id', $publicId)
            ->where('is_public', true)
            ->first();

        if (! $preset) {
            return redirect('/outils/mots-croises')
                ->with('error', 'Cette grille n\'existe pas ou n\'est pas publique.');
        }

        return view('tools::public.tools.crossword.jeu', [
            'preset' => $preset,
            'pageTitle' => $preset->name.' — Jouer en ligne',
            'pageDescription' => 'Résolvez la grille de mots croisés "'.$preset->name.'" en ligne sur laveille.ai.',
        ]);
    }
}
