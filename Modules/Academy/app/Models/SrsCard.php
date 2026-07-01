<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Carte de révision espacée (SRS) d'un apprenant. Porte son état SM-2 complet
 * (ease_factor, interval_days, repetitions, due_at). Aucune logique d'algorithme
 * ici (elle vit dans SrsService, testable/déterministe) : le modèle expose
 * relations, scopes et accessors purs.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $course_id
 * @property int         $lesson_id
 * @property string      $source_type
 * @property int         $source_id
 * @property string      $front
 * @property string|null $back
 * @property float       $ease_factor
 * @property int         $interval_days
 * @property int         $repetitions
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property \Illuminate\Support\Carbon|null $last_reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SrsCard extends Model
{
    protected $table = 'academy_srs_cards';

    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'source_type',
        'source_id',
        'front',
        'back',
        'ease_factor',
        'interval_days',
        'repetitions',
        'due_at',
        'last_reviewed_at',
    ];

    protected $casts = [
        'ease_factor'      => 'float',
        'interval_days'    => 'integer',
        'repetitions'      => 'integer',
        'due_at'           => 'datetime',
        'last_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** Scope : cartes DUES d'un utilisateur (due_at passée ou nulle = nouvelle carte). */
    public function scopeDueFor(Builder $query, int $userId): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->where(function (Builder $q): void {
                $q->whereNull('due_at')->orWhere('due_at', '<=', now());
            });
    }
}
