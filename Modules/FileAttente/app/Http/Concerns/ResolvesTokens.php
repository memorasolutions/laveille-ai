<?php

declare(strict_types=1);

namespace Modules\FileAttente\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\FileAttente\Models\Board;
use Modules\FileAttente\Models\Participant;
use Modules\FileAttente\Services\BoardService;

/**
 * Résolution des jetons d'URL (hachés en base) vers leur tableau / participant. 404 si inconnu.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @property BoardService $boards
 */
trait ResolvesTokens
{
    protected function boardByManage(string $token): Board
    {
        return Board::where('manage_token_hash', $this->boards->hash($token))->firstOrFail();
    }

    protected function boardByDisplay(string $token): Board
    {
        return Board::where('display_token_hash', $this->boards->hash($token))->firstOrFail();
    }

    /** @return array{0: Board, 1: Participant} */
    protected function participantByToken(string $token): array
    {
        $p = Participant::where('token_hash', $this->boards->hash($token))->firstOrFail();

        return [Board::findOrFail($p->board_id), $p];
    }

    /** Version demandée (?v=N), ou null si absente. */
    protected function requestedVersion(Request $request): ?int
    {
        return $request->query('v') === null ? null : (int) $request->query('v');
    }

    protected function unchanged(): Response
    {
        return response()->noContent();
    }
}
