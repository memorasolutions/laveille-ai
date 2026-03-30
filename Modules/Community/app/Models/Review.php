<?php

declare(strict_types=1);

namespace Modules\Community\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Traits\HasModerationStatus;

class Review extends Model
{
    use HasModerationStatus;

    protected $fillable = ['reviewable_type', 'reviewable_id', 'user_id', 'guest_name', 'rating', 'content', 'status'];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }
}
