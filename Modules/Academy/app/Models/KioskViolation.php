<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Mode kiosque — un incident consigné pendant une tentative de quiz surveillée.
 * Lecture seule pour le formateur (voir KioskViolationService::forAttempt()) ;
 * l'écriture passe exclusivement par KioskViolationService::record(), jamais
 * directement depuis un contrôleur, afin que la liste blanche de types et les
 * vérifications d'ownership restent centralisées à un seul endroit (DRY).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property int                             $quiz_attempt_id
 * @property int                             $user_id
 * @property int                             $lesson_item_id
 * @property string                          $type
 * @property \Illuminate\Support\Carbon      $occurred_at
 * @property array|null                      $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class KioskViolation extends Model
{
    protected $table = 'academy_kiosk_violations';

    protected $fillable = [
        'quiz_attempt_id',
        'user_id',
        'lesson_item_id',
        'type',
        'occurred_at',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta'        => 'array',
    ];

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }
}
