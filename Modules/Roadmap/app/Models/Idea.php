<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Roadmap\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Roadmap\Database\Factories\IdeaFactory;
use Modules\Roadmap\Enums\IdeaStatus;

class Idea extends Model
{
    use \Modules\Core\Traits\HasModerationStatus;
    use HasFactory, SoftDeletes;

    protected $table = 'roadmap_ideas';

    protected $fillable = [
        'board_id',
        'user_id',
        'title',
        'slug',
        'description',
        'status',
        'category',
        'vote_count',
        'comment_count',
        'pinned',
        'merged_into_id',
        'category_id',
    ];

    protected $casts = [
        'status' => IdeaStatus::class,
        'pinned' => 'boolean',
        'vote_count' => 'integer',
        'comment_count' => 'integer',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function roadmapCategory(): BelongsTo
    {
        return $this->belongsTo(RoadmapCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IdeaComment::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'merged_into_id');
    }

    public function mergedIdeas(): HasMany
    {
        return $this->hasMany(Idea::class, 'merged_into_id');
    }

    public function changelogs(): HasMany
    {
        return $this->hasMany(Changelog::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBoard($query, $boardId)
    {
        return $query->where('board_id', $boardId);
    }

    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }

    public function scopeTrending($query)
    {
        return $query->orderByDesc('vote_count');
    }

    protected static function newFactory(): IdeaFactory
    {
        return IdeaFactory::new();
    }
}
