<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 */

declare(strict_types=1);

namespace Modules\Journal\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Community\Traits\HasReports;
use Modules\Core\Traits\HasPublishedState;

class Journal extends Model
{
    use HasPublishedState;
    use HasReports;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'journal_date',
        'template',
        'cover_image',
        'is_published',
        'share_token',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'journal_date' => 'date',
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(JournalBlock::class)->orderBy('sort_order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
