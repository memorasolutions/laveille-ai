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
use Modules\Roadmap\Database\Factories\IdeaCommentFactory;

class IdeaComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'idea_id',
        'user_id',
        'content',
        'is_official',
        'is_internal',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'is_internal' => 'boolean',
    ];

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): IdeaCommentFactory
    {
        return IdeaCommentFactory::new();
    }
}
