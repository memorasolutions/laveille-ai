<?php

declare(strict_types=1);

namespace Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolResource extends Model
{
    use \Modules\Core\Traits\HasModerationStatus;
    use \Modules\Voting\Traits\HasCommunityVotes;

    protected $table = 'directory_resources';

    protected $fillable = [
        'directory_tool_id', 'user_id', 'url', 'title',
        'type', 'language', 'level', 'thumbnail', 'video_id',
        'video_summary', 'duration_seconds', 'channel_name',
        'channel_url', 'is_approved',
    ];

    public static function detectLevel(string $title): string
    {
        $t = mb_strtolower($title);

        $advanced = ['avancé', 'advanced', 'pro tips', 'expert', 'master', 'deep dive', 'optimisation', 'architecture', 'scaling', 'enterprise', 'astuces pro'];
        foreach ($advanced as $kw) {
            if (str_contains($t, $kw)) {
                return 'advanced';
            }
        }

        $beginner = ['débutant', 'beginner', 'getting started', 'premiers pas', 'introduction', 'basics', 'fundamentals', '101', 'pour commencer', 'débuter', 'apprendre', 'learn', 'easy', 'simple', 'guide complet', 'from scratch', 'de zéro'];
        foreach ($beginner as $kw) {
            if (str_contains($t, $kw)) {
                return 'beginner';
            }
        }

        return 'intermediate';
    }

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
