<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Événement de calendrier manuel (V5-b). Les événements dérivés des devoirs
 * (Assignment.due_at) sont CALCULÉS À LA VOLÉE par CalendarService - jamais
 * stockés dans cette table. Soft-delete pour permettre aux formateurs de
 * retrouver par erreur un événement supprimé via l'admin superadmin.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $course_id
 * @property int|null    $lesson_item_id
 * @property string      $title
 * @property string|null $description
 * @property string      $type
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int         $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CalendarEvent extends Model
{
    use SoftDeletes;

    /** Types d'événements valides (liste blanche). */
    public const TYPES = ['due', 'exam', 'live', 'manual'];

    protected $table = 'academy_calendar_events';

    protected $fillable = [
        'course_id',
        'lesson_item_id',
        'title',
        'description',
        'type',
        'starts_at',
        'ends_at',
        // created_by : toujours assigné explicitement côté serveur
        // (Auth::id() dans CourseCalendar::save()) avant l'appel à create().
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Filtre sur un cours précis. Utilisé par CalendarService.
     */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }
}
