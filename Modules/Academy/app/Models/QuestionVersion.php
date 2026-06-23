<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F17 - Instantane (version) d'une question de banque. Une ligne = l'etat PRECEDENT
 * d'une question juste avant une edition (prompt/payload/explanation/type). Lecture
 * seule cote metier : aucune note/quiz ne s'y rattache. Owner-scope direct.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $question_id
 * @property int         $owner_id
 * @property int         $version
 * @property string      $prompt
 * @property array|null  $payload
 * @property string|null $explanation
 * @property string      $type
 * @property \Illuminate\Support\Carbon|null $snapshot_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class QuestionVersion extends Model
{
    protected $table = 'academy_question_versions';

    protected $fillable = [
        'question_id',
        'owner_id',
        'version',
        'prompt',
        'payload',
        'explanation',
        'type',
        'snapshot_at',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'owner_id'    => 'integer',
        'version'     => 'integer',
        'payload'     => 'array',
        'snapshot_at' => 'datetime',
    ];

    /** Question d'origine. */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    /** Formateur proprietaire. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Scope : versions appartenant a un proprietaire donne. */
    public function scopeOwned(Builder $query, $user): Builder
    {
        $ownerId = $user instanceof User ? $user->getKey() : (int) $user;

        return $query->where('owner_id', $ownerId);
    }
}
