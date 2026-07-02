<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Un statement xAPI léger (actor-verb-object, standard 1EdTech/ADL). Table
 * append-only : un statement n'est jamais modifié ni retiré, il REFORMATE un
 * événement pédagogique déjà persisté ailleurs (voir XapiRecorderService pour
 * le vocabulaire de verbes/objets contrôlé et les points d'émission).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $user_id
 * @property string      $verb
 * @property string      $object_type
 * @property int         $object_id
 * @property array|null  $result
 * @property array|null  $context
 * @property array       $raw_payload
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class XapiStatement extends Model
{
    /** Pas de colonne updated_at (append-only, jamais modifié après écriture). */
    public const UPDATED_AT = null;

    protected $table = 'academy_xapi_statements';

    protected $fillable = [
        'user_id',
        'verb',
        'object_type',
        'object_id',
        'result',
        'context',
        'raw_payload',
        'occurred_at',
    ];

    protected $casts = [
        'user_id'     => 'integer',
        'object_id'   => 'integer',
        'result'      => 'array',
        'context'     => 'array',
        'raw_payload' => 'array',
        'occurred_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Scope anti-IDOR : ne renvoie que les statements d'un utilisateur donné. */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /** Scope : ne renvoie que les statements portant sur un verbe donné. */
    public function scopeForVerb(Builder $query, string $verb): Builder
    {
        return $query->where('verb', $verb);
    }

    /** Scope : ne renvoie que les statements portant sur un objet donné (type + id). */
    public function scopeForObject(Builder $query, string $objectType, int $objectId): Builder
    {
        return $query->where('object_type', $objectType)->where('object_id', $objectId);
    }
}
