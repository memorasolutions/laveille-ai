<?php

declare(strict_types=1);

namespace Modules\Decido\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Decido\Database\Factories\PollOptionFactory;

class PollOption extends Model
{
    use HasFactory;

    protected $table = 'decido_poll_options';

    protected $fillable = [
        'poll_id',
        'label',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'option_id');
    }

    /**
     * @return array<string, int>
     */
    public function voteCounts(): array
    {
        return $this->votes()
            ->selectRaw('value, count(*) as total')
            ->groupBy('value')
            ->pluck('total', 'value')
            ->toArray();
    }

    protected static function newFactory(): PollOptionFactory
    {
        return PollOptionFactory::new();
    }
}
