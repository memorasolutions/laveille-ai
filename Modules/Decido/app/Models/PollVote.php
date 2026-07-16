<?php

declare(strict_types=1);

namespace Modules\Decido\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    protected $table = 'decido_poll_votes';

    protected $fillable = [
        'poll_id',
        'option_id',
        'voter_token',
        'voter_pseudonym',
        'value',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'option_id');
    }
}
