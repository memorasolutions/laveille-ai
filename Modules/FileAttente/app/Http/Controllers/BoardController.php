<?php

declare(strict_types=1);

namespace Modules\FileAttente\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\FileAttente\Exceptions\StaleBoardVersionException;
use Modules\FileAttente\Http\Concerns\ResolvesTokens;
use Modules\FileAttente\Models\Participant;
use Modules\FileAttente\Services\BoardService;
use Modules\FileAttente\Services\CreationQuota;
use Modules\FileAttente\Services\QrSheetService;

/**
 * Création du tableau et pages/actions de l'animateur (jeton de gestion).
 * Toutes les actions sont des POST classiques protégés CSRF; le rafraîchissement passe par l'endpoint JSON versionné.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class BoardController
{
    use ResolvesTokens;

    public function __construct(
        private readonly BoardService $boards,
        private readonly QrSheetService $sheets,
        private readonly CreationQuota $quota,
    ) {}

    public function create(): View
    {
        return view('fileattente::create');
    }

    public function store(Request $request): View|RedirectResponse
    {
        if (! $this->quota->allows($request->session())) {
            return back()->withInput()->withErrors(['names' => __('fileattente::ui.errors.quota', ['max' => $this->quota->limit()])]);
        }

        $max = (int) config('file-attente.max_name_len', 40);
        $maxN = (int) config('file-attente.max_participants', 60);
        $data = $request->validate([
            'names' => ['required', 'string', 'max:20000'],
            'title' => ['nullable', 'string', 'max:120'],
            'mask_mode' => ['required', Rule::in(['initial', 'pseudo', 'numero'])],
            'scan_mode' => ['required', Rule::in(['borne', 'telephone'])],
            'allow_self_leave' => ['nullable', 'boolean'],
        ]);

        $names = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $data['names']) ?: []),
            static fn (string $n): bool => $n !== '',
        ));
        if ($names === []) {
            return back()->withInput()->withErrors(['names' => __('fileattente::ui.errors.names_required')]);
        }
        foreach ($names as $n) {
            if (mb_strlen($n) > $max) {
                return back()->withInput()->withErrors(['names' => __('fileattente::ui.errors.name_too_long', ['max' => $max, 'sample' => mb_substr($n, 0, 20)])]);
            }
        }
        if (count($names) > $maxN) {
            return back()->withInput()->withErrors(['names' => __('fileattente::ui.errors.too_many', ['max' => $maxN, 'count' => count($names)])]);
        }

        try {
            $made = $this->boards->createBoard($names, [
                'title' => $data['title'] ?? null,
                'mask_mode' => $data['mask_mode'],
                'scan_mode' => $data['scan_mode'],
                'allow_self_leave' => $request->boolean('allow_self_leave'),
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['names' => $e->getMessage()]);
        }

        $this->quota->record($request->session());

        // Les jetons en clair ne s'affichent qu'ici, une seule fois.
        return view('fileattente::created', [
            'manageUrl' => route('fileattente.manage', $made['manage_token']),
            'displayUrl' => route('fileattente.display', $made['display_token']),
            'pdfUrl' => route('fileattente.pdf', $made['manage_token']),
            'count' => count($made['participants']),
        ]);
    }

    public function manage(string $manageToken): View
    {
        $board = $this->boardByManage($manageToken);

        // Dégradation propre : tableau antérieur à la migration ou clé changée → message, jamais de 500.
        try {
            $displayUrl = route('fileattente.display', $this->boards->displayPlainToken($board));
        } catch (\RuntimeException) {
            $displayUrl = null;
        }

        return view('fileattente::manage', [
            'displayUrl' => $displayUrl,
            'board' => $board,
            'token' => $manageToken,
            'state' => $this->boards->animatorState($board),
            'cadence' => (int) config('file-attente.cadences.animateur', 3),
        ]);
    }

    public function etat(Request $request, string $manageToken): JsonResponse|Response
    {
        $board = $this->boardByManage($manageToken);
        $v = $this->requestedVersion($request);
        if ($v !== null && $v === (int) $board->version) {
            return $this->unchanged();
        }

        return response()->json($this->boards->animatorState($board));
    }

    public function add(Request $request, string $manageToken): RedirectResponse
    {
        $board = $this->boardByManage($manageToken);
        $data = $request->validate(['name' => ['required', 'string', 'max:'.(int) config('file-attente.max_name_len', 40)]]);
        try {
            $this->boards->animatorAdd($board, $data['name']);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('fileattente.manage', $manageToken)->with('fa_error', $e->getMessage());
        }

        return redirect()->route('fileattente.manage', $manageToken);
    }

    public function arrive(Request $request, string $manageToken, int $id): JsonResponse|RedirectResponse
    {
        $board = $this->boardByManage($manageToken);
        $p = Participant::where('board_id', $board->id)->findOrFail($id);
        $this->boards->animatorArrive($board, $p);

        return $this->done($request, $manageToken, $board->refresh());
    }

    public function remove(Request $request, string $manageToken, int $id): JsonResponse|RedirectResponse
    {
        $board = $this->boardByManage($manageToken);
        $p = Participant::where('board_id', $board->id)->findOrFail($id);
        $this->boards->animatorRemove($board, $p);

        return $this->done($request, $manageToken, $board->refresh());
    }

    public function reorder(Request $request, string $manageToken): JsonResponse
    {
        $board = $this->boardByManage($manageToken);
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'afterSortKey' => ['nullable', 'integer'],
            'expectedVersion' => ['required', 'integer'],
        ]);
        $p = Participant::where('board_id', $board->id)
            ->whereIn('status', [Participant::IN_LIST, Participant::CALLED])
            ->findOrFail($data['id']);

        try {
            $this->boards->reorder($board, $p, $data['afterSortKey'] ?? null, (int) $data['expectedVersion']);
        } catch (StaleBoardVersionException) {
            return response()->json(['error' => 'stale', 'version' => (int) $board->refresh()->version], 409);
        }

        return response()->json(['ok' => true, 'version' => (int) $board->refresh()->version]);
    }

    public function callNext(Request $request, string $manageToken): JsonResponse|RedirectResponse
    {
        $board = $this->boardByManage($manageToken);
        $called = $this->boards->callNext($board);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'called' => $called === null ? null : ['id' => $called->id, 'full_name' => $called->full_name, 'label' => $called->display_label],
                'version' => (int) $board->refresh()->version,
            ]);
        }

        return redirect()->route('fileattente.manage', $manageToken);
    }

    public function undoCall(Request $request, string $manageToken, int $id): JsonResponse|RedirectResponse
    {
        $board = $this->boardByManage($manageToken);
        $p = Participant::where('board_id', $board->id)->findOrFail($id);
        $this->boards->undoCall($board, $p);

        return $this->done($request, $manageToken, $board->refresh());
    }

    /** « Terminer et supprimer » : purge immédiate du tableau et de tous ses noms. */
    public function terminate(string $manageToken): RedirectResponse
    {
        $board = $this->boardByManage($manageToken);
        $displayHash = $board->display_token_hash;
        $key = $this->boards->cacheKey($board);

        DB::transaction(function () use ($board): void {
            Participant::where('board_id', $board->id)->delete();
            $board->delete();
        });
        Cache::forget($key);
        Cache::forget('fa.disp.'.$displayHash);

        return redirect()->route('fileattente.create')->with('fa_notice', __('fileattente::ui.manage.terminated'));
    }

    public function pdf(string $manageToken): Response
    {
        $board = $this->boardByManage($manageToken);
        try {
            $binary = $this->sheets->render($board);
        } catch (\RuntimeException $e) {
            return response($e->getMessage(), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="file-attente-cartes-qr.pdf"',
        ]);
    }

    private function done(Request $request, string $manageToken, \Modules\FileAttente\Models\Board $board): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'version' => (int) $board->version]);
        }

        return redirect()->route('fileattente.manage', $manageToken);
    }
}
