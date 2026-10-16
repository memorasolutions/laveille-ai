<?php

declare(strict_types=1);

namespace Modules\FileAttente\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class Participant extends Model
{
    public const PENDING = 'pending';

    public const IN_LIST = 'in_list';

    public const CALLED = 'called';

    public const REMOVED = 'removed';

    protected $table = 'fa_participants';

    protected $fillable = [
        'board_id', 'full_name', 'display_label', 'token_hash', 'token_cipher', 'status',
        'joined_seq', 'sort_key', 'joined_at',
    ];

    // Le nom complet ne sort JAMAIS d'un tableau/JSON : l'animateur le lit via l'attribut, explicitement.
    protected $hidden = ['full_name', 'token_hash', 'token_cipher'];

    protected function casts(): array
    {
        return [
            'joined_seq' => 'integer',
            'sort_key' => 'integer',
            'joined_at' => 'datetime',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id');
    }
}
