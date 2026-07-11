<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 */

declare(strict_types=1);

namespace Modules\Journal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalBlock extends Model
{
    protected $fillable = [
        'journal_id',
        'type',
        'source_type',
        'source_id',
        'payload',
        'sort_order',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
