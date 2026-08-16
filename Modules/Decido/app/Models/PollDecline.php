<?php

declare(strict_types=1);

namespace Modules\Decido\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LOT 1 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 3) : un votant qui déclare
 * qu'AUCUNE date/option ne lui convient, distinct d'un simple silence. Voir le commentaire de la
 * migration decido_poll_declines pour la justification du choix (table dédiée plutôt qu'un "no"
 * sur chaque option).
 */
class PollDecline extends Model
{
    protected $table = 'decido_poll_declines';

    protected $fillable = [
        'poll_id',
        'voter_token',
        'voter_pseudonym',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }
}
