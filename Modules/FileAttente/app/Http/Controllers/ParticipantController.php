<?php

declare(strict_types=1);

namespace Modules\FileAttente\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\FileAttente\Http\Concerns\ResolvesTokens;
use Modules\FileAttente\Services\BoardService;

/**
 * Parcours du participant. Le GET (scan du QR) n'a AUCUN effet : rejoindre / quitter passent par un POST explicite.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class ParticipantController
{
    use ResolvesTokens;

    public function __construct(private readonly BoardService $boards) {}

    public function show(string $participantToken): View
    {
        [$board, $p] = $this->participantByToken($participantToken);

        return view('fileattente::scan', [
            'board' => $board,
            'token' => $participantToken,
            'state' => $this->boards->participantState($board, $p),
            'cadence' => (int) config('file-attente.cadences.participant', 15),
        ]);
    }

    public function join(Request $request, string $participantToken): JsonResponse|RedirectResponse
    {
        [$board] = $this->participantByToken($participantToken);
        $p = $this->boards->join($board, $participantToken);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'label' => $p->display_label, 'status' => $p->status]);
        }

        return redirect()->route('fileattente.scan', $participantToken);
    }

    public function leave(Request $request, string $participantToken): JsonResponse|RedirectResponse
    {
        [$board] = $this->participantByToken($participantToken);
        abort_unless($board->allow_self_leave, 403, __('fileattente::ui.errors.self_leave_disabled'));
        $this->boards->leave($board, $participantToken);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('fileattente.scan', $participantToken);
    }

    public function etat(Request $request, string $participantToken): JsonResponse|Response
    {
        [$board, $p] = $this->participantByToken($participantToken);
        $v = $this->requestedVersion($request);
        if ($v !== null && $v === (int) $board->version) {
            return $this->unchanged();
        }

        return response()->json($this->boards->participantState($board, $p));
    }
}
