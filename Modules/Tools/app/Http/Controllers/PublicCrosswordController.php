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
use Modules\Core\Services\QrCodeService;
use Modules\Tools\Models\SavedCrosswordPreset;
use Modules\Tools\Services\CrosswordCsvService;
use Modules\Tools\Services\CrosswordGeneratorService;
use Modules\Tools\Services\CrosswordPdfService;

// S80 cleanup : CrosswordAiSuggestionService injection + aiSuggestPairs() méthode retirées (bouton UI retiré S79, dead code)
class PublicCrosswordController
{
    public function __construct(
        private CrosswordGeneratorService $generator,
        private CrosswordPdfService $pdf,
        private CrosswordCsvService $csv,
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pairs' => 'required|array|min:2|max:50',
            'pairs.*.clue' => 'required|string|max:250',
            'pairs.*.answer' => 'required|string|min:2|max:30',
            'seed' => 'nullable|integer|min:0', // S80 #51 : retiré max INT32, le service CrosswordGeneratorService clamp à 2147483647 si supérieur (cohérent mt_srand 32-bit)
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
                'seed' => 'nullable|integer|min:0', // S80 #51 : retiré max INT32, le service CrosswordGeneratorService clamp à 2147483647 si supérieur (cohérent mt_srand 32-bit)
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

    public function play(string $identifier): View|RedirectResponse
    {
        // 2026-05-05 #97 Phase 1 : accepte custom_slug OU public_id (BC garantie).
        // Priorité custom_slug puis fallback public_id.
        $preset = SavedCrosswordPreset::findByShareIdentifier($identifier);

        if (! $preset || ! $preset->is_public) {
            return redirect('/outils/mots-croises')
                ->with('error', 'Cette grille n\'existe pas ou n\'est pas publique.');
        }

        // 2026-05-05 #97 : si l'utilisateur arrive via le public_id mais qu'un custom_slug existe,
        // redirect 301 vers l'URL canonique (slug) — meilleur SEO + cohérence.
        if ($preset->custom_slug && $identifier === $preset->public_id) {
            return redirect('/jeumc/'.$preset->custom_slug, 301);
        }

        // 2026-05-05 #94 : tracking play_count rate-limité 1×/session pour éviter inflation
        if (\Schema::hasColumn('saved_crossword_presets', 'play_count')) {
            $sessionKey = 'cw_played_'.$preset->public_id;
            if (! session()->has($sessionKey)) {
                $preset->incrementQuietly('play_count');
                session()->put($sessionKey, now()->timestamp);
            }
        }

        return view('tools::public.tools.crossword.jeu', [
            'preset' => $preset,
            'pageTitle' => $preset->name.' — Jouer en ligne',
            'pageDescription' => 'Résolvez la grille de mots croisés "'.$preset->name.'" en ligne sur laveille.ai.',
        ]);
    }

    /**
     * 2026-05-05 #97 Phase 2 : QR code PNG personnalisable pour grille publique.
     * Route : GET /jeumc/{identifier}/qr.png?fg=&bg=&logo=0|1&ecc=&style=&size=&download=0|1
     * Cache 1h (immutable params dans URL).
     */
    public function qrPng(Request $request, string $identifier, QrCodeService $qr): Response
    {
        $preset = SavedCrosswordPreset::findByShareIdentifier($identifier);
        if (! $preset || ! $preset->is_public) {
            abort(404);
        }

        $stored = is_array($preset->qr_options) ? $preset->qr_options : [];

        // Priorité : query params > qr_options DB > defaults service.
        $opts = [
            'foreground' => $this->normalizeHex($request->query('fg', $stored['foreground'] ?? null)),
            'background' => $this->normalizeHex($request->query('bg', $stored['background'] ?? null)),
            'ecc' => strtoupper((string) $request->query('ecc', $stored['ecc'] ?? 'M')),
            'dot_style' => (string) $request->query('style', $stored['dot_style'] ?? 'square'),
            'size' => (int) $request->query('size', $stored['size'] ?? 400),
            'logo_path' => null,
        ];
        $opts = array_filter($opts, fn ($v) => $v !== null && $v !== '');

        $logo = $request->query('logo', $stored['logo'] ?? '0');
        if ($logo === '1' || $logo === 'true' || $logo === true) {
            $logoFile = public_path('images/logo-avatar.png');
            if (is_file($logoFile)) {
                $opts['logo_path'] = $logoFile;
            }
        }

        try {
            $url = url('/jeumc/'.$preset->share_slug);
            $png = $qr->generate($url, $opts);
        } catch (\InvalidArgumentException $e) {
            // Fallback options par défaut si paramètres user invalides (contraste KO).
            $png = $qr->generate(url('/jeumc/'.$preset->share_slug), []);
        }

        $headers = [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="qr-laveille-'.$preset->share_slug.'.png"';
        }

        return response($png, 200, $headers);
    }

    private function normalizeHex(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        $value = '#'.ltrim($value, '#');
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    /**
     * 2026-05-05 #94 : page index publique des grilles avec recherche, filtres, tri, pagination.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $difficulty = trim((string) $request->input('difficulty', ''));
        $sort = $request->input('sort', 'recent');
        $period = $request->input('period', '');

        $query = SavedCrosswordPreset::query()
            ->where('is_public', true)
            ->with('user:id,name');

        if ($search !== '') {
            $needle = '%'.$search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhere('params', 'like', $needle);
            });
        }

        if ($difficulty !== '' && in_array($difficulty, ['Facile', 'Moyen', 'Difficile'], true)) {
            $query->where('params', 'like', '%"difficulty":"'.$difficulty.'"%');
        }

        if ($period === '7d') {
            $query->where('updated_at', '>=', now()->subDays(7));
        } elseif ($period === '30d') {
            $query->where('updated_at', '>=', now()->subDays(30));
        }

        $hasPlayCount = \Schema::hasColumn('saved_crossword_presets', 'play_count');

        switch ($sort) {
            case 'popular':
                if ($hasPlayCount) {
                    $query->orderByDesc('play_count')->orderByDesc('updated_at');
                } else {
                    $query->orderByDesc('updated_at');
                }
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'recent':
            default:
                $query->orderByDesc('updated_at');
        }

        $presets = $query->paginate(12)->withQueryString();

        $totalPublic = SavedCrosswordPreset::where('is_public', true)->count();

        return view('tools::public.tools.crossword.index', [
            'presets' => $presets,
            'search' => $search,
            'difficulty' => $difficulty,
            'sort' => $sort,
            'period' => $period,
            'totalPublic' => $totalPublic,
            'pageTitle' => 'Grilles de mots croisés à jouer en ligne',
            'pageDescription' => 'Découvrez '.$totalPublic.' grilles de mots croisés gratuites créées par la communauté laveille.ai. Recherche par thème, niveau et popularité.',
        ]);
    }
}
