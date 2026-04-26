<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationLog extends Model
{
    protected $table = 'moderation_logs';

    protected $fillable = [
        'tool_id',
        'moderator_id',
        'action',
        'old_status',
        'new_status',
        'reason',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'moderator_id');
    }

    public function scopeForTool($query, int $toolId)
    {
        return $query->where('tool_id', $toolId)->orderBy('created_at', 'desc');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
