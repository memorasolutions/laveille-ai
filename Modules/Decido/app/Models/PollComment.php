<?php

declare(strict_types=1);

namespace Modules\Decido\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 5) : UN commentaire libre facultatif
 * par participant et par sondage (jamais un par créneau). Voir le commentaire de la migration
 * decido_poll_comments pour la justification du choix (table dédiée, contrainte unique
 * poll_id+voter_token).
 *
 * Risque traité (texte libre saisi par n'importe qui disposant du lien, affiché à d'autres, sans
 * aucune modération humaine possible) : le champ `comment` est nettoyé de toute balise HTML via
 * strip_tags() AVANT écriture (PublicPollController::sanitizeComment()) - défense en profondeur
 * en plus de l'échappement Blade {{ }} systématique à l'affichage (jamais {!! !!} sur ce champ,
 * nulle part). Aucune conversion en lien cliquable n'est appliquée nulle part sur ce champ (pas de
 * linkifier) : une URL collée par un participant reste du texte brut, jamais un <a href>.
 */
class PollComment extends Model
{
    protected $table = 'decido_poll_comments';

    protected $fillable = [
        'poll_id',
        'voter_token',
        'voter_pseudonym',
        'comment',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }
}
