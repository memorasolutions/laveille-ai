<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F18 - COMMENTAIRE sur un item de leçon (parité Moodle « comments »). SoftDeletes :
 * un commentaire supprimé est conservé (audit/modération) et exclu par le scope par
 * défaut. Le corps est rendu en HTML sûr (anti-XSS) via renderedBody().
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int      $id
 * @property int      $lesson_item_id
 * @property int|null $user_id
 * @property string   $body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ItemComment extends Model
{
    use SoftDeletes;

    protected $table = 'academy_item_comments';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
        'body',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'user_id'        => 'integer',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Corps du commentaire rendu en HTML sûr (anti-XSS stockée) : même pipeline que
     * ForumPost::renderedBody() - LessonItem::renderRichText (html_input=strip +
     * allow_unsafe_links=false). Le résultat peut être rendu via {!! ... !!} en toute
     * sûreté : aucun HTML brut de l'utilisateur ne survit.
     */
    public function renderedBody(): string
    {
        return LessonItem::renderRichText($this->body);
    }
}
