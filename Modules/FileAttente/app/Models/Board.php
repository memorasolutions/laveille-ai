<?php

declare(strict_types=1);

namespace Modules\FileAttente\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class Board extends Model
{
    protected $table = 'fa_boards';

    protected $fillable = [
        'manage_token_hash', 'display_token_hash', 'display_token_cipher', 'title', 'version', 'mask_mode',
        'scan_mode', 'allow_self_leave', 'last_activity_at', 'purge_after',
    ];

    protected $hidden = ['manage_token_hash', 'display_token_hash', 'display_token_cipher'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'allow_self_leave' => 'boolean',
            'last_activity_at' => 'datetime',
            'purge_after' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'board_id');
    }
}
