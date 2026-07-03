<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) — un message d'une conversation privée. `body` est
 * TOUJOURS du texte brut (jamais de HTML stocké ni interprété ; l'affichage
 * échappe systématiquement, voir direct-message-thread.blade.php).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int    $id
 * @property int    $conversation_id
 * @property int    $sender_id
 * @property int    $recipient_id
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DirectMessage extends Model
{
    use SoftDeletes;

    protected $table = 'academy_dm_messages';

    /** Longueur max d'un message (validation applicative — cohérent avec la migration `text`). */
    public const MAX_LENGTH = 4000;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'recipient_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DirectMessageConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function isReadBy(User $user): bool
    {
        return (int) $user->id === (int) $this->recipient_id && $this->read_at !== null;
    }
}
