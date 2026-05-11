<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcentreBuilderRun extends Model
{
    protected $fillable = [
        'user_id',
        'week_start',
        'week_end',
        'selected_news_ids',
        'manual_urls',
        'generated_prompt',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'selected_news_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
