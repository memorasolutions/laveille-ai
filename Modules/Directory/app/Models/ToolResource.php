<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolResource extends Model
{
    use \Modules\Voting\Traits\HasCommunityVotes;

    protected $table = 'directory_resources';

    protected $fillable = [
        'directory_tool_id', 'user_id', 'url', 'title',
        'type', 'language', 'thumbnail', 'video_id',
        'video_summary', 'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class, 'directory_tool_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }
}
