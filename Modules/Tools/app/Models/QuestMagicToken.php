<?php

declare(strict_types=1);

namespace Modules\Tools\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class QuestMagicToken extends Model
{
    protected $table = 'quest_magic_tokens';

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at?->isFuture();
    }
}
