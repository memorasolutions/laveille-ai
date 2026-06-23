<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FORUM - sujet de discussion d'un item de leçon « forum » (type Moodle « Forum »).
 * Porte le 1er message (body) et ses réponses (hasMany ForumPost). is_pinned /
 * is_locked sont des drapeaux de modération (gérant). SoftDeletes : un sujet
 * supprimé est conservé (audit) et exclu des listes par le scope par défaut.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int      $id
 * @property int      $lesson_item_id
 * @property int|null $user_id
 * @property string   $title
 * @property string   $body
 * @property bool     $is_pinned
 * @property bool     $is_locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ForumTopic extends Model
{
    use SoftDeletes;

    protected $table = 'academy_forum_topics';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
        'title',
        'body',
        'is_pinned',
        'is_locked',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'user_id'        => 'integer',
        'is_pinned'      => 'boolean',
        'is_locked'      => 'boolean',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'topic_id');
    }
}
