<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FORUM - réponse (message) à un sujet de discussion (item « forum »). SoftDeletes :
 * une réponse supprimée est conservée (audit) et exclue par le scope par défaut.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academy\Models\LessonItem;

/**
 * @property int      $id
 * @property int      $topic_id
 * @property int|null $user_id
 * @property string   $body
 * @property int|null $video_timestamp_seconds
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ForumPost extends Model
{
    use SoftDeletes;

    protected $table = 'academy_forum_posts';

    protected $fillable = [
        'topic_id',
        'user_id',
        'body',
        // Discussion sociale par vidéo (dette D-video-discussion) : ancre facultative
        // à un instant précis de la vidéo, null si non renseignée ou non applicable.
        'video_timestamp_seconds',
    ];

    protected $casts = [
        'topic_id'                 => 'integer',
        'user_id'                  => 'integer',
        'video_timestamp_seconds'  => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Corps de la réponse rendu en HTML sûr (anti-XSS stockée) : même pipeline que
     * Announcement::renderedBody() - LessonItem::renderRichText (html_input=strip +
     * allow_unsafe_links=false). Le résultat peut être rendu via {!! ... !!} en toute
     * sûreté : aucun HTML brut de l'utilisateur ne survit.
     *
     * Note : AcademyNotificationService::forumReply() avait un fallback `e($post->body)`
     * (sûr) ; cette méthode assure la cohérence et permet de supprimer la dépendance
     * au `method_exists()` défensif dans le service.
     */
    public function renderedBody(): string
    {
        return LessonItem::renderRichText($this->body);
    }

    /**
     * Horodatage vidéo formaté « m:ss » (ou « h:mm:ss » au-delà d'une heure), ou
     * null si non renseigné. DRY : délègue à ForumService::formatTimestamp()
     * (source unique de formatage, partagée avec ForumTopic::formattedVideoTimestamp()).
     */
    public function formattedVideoTimestamp(): ?string
    {
        return \Modules\Academy\Services\ForumService::formatTimestamp($this->video_timestamp_seconds);
    }
}
