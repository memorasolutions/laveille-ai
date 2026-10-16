<?php

declare(strict_types=1);

namespace Modules\FileAttente\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\FileAttente\Http\Concerns\ResolvesTokens;
use Modules\FileAttente\Models\Board;
use Modules\FileAttente\Services\BoardService;

/**
 * Page d'AFFICHAGE (projection) et son JSON PUBLIC versionné. Aucun pouvoir d'écriture, jamais de full_name.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class DisplayController
{
    use ResolvesTokens;

    public function __construct(private readonly BoardService $boards) {}

    public function show(string $displayToken): View
    {
        $board = $this->boardByDisplay($displayToken);

        return view('fileattente::display', [
            'board' => $board,
            'token' => $displayToken,
            'state' => $this->boards->publicState($board),
            'cadence' => (int) config('file-attente.cadences.affichage', 2),
        ]);
    }

    /**
     * 204 si la version n'a pas bougé, SANS toucher MySQL (id du tableau et version tenus en cache).
     */
    public function etat(Request $request, string $displayToken): JsonResponse|Response
    {
        $hash = $this->boards->hash($displayToken);
        $mapKey = 'fa.disp.'.$hash;
        $id = Cache::remember($mapKey, 600, static fn () => Board::where('display_token_hash', $hash)->value('id'));
        abort_if($id === null, 404);

        $v = $this->requestedVersion($request);
        $versionKey = $this->boards->cacheKey((new Board)->forceFill(['id' => $id]));
        $cached = Cache::get($versionKey);
        if ($v !== null && $cached !== null && (int) $cached === $v) {
            return $this->unchanged();
        }

        $board = Board::find($id);
        if ($board === null) {
            Cache::forget($mapKey);
            abort(404);
        }
        Cache::forever($versionKey, (int) $board->version);
        if ($v !== null && $v === (int) $board->version) {
            return $this->unchanged();
        }

        return response()->json($this->boards->publicState($board));
    }
}
