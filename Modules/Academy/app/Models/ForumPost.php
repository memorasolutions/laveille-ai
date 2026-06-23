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

/**
 * @property int      $id
 * @property int      $topic_id
 * @property int|null $user_id
 * @property string   $body
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
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'user_id'  => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
