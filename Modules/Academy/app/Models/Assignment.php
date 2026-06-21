<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Devoir (assignment) d'un cours (Phase E / E2 - évaluation). Un devoir
 * appartient à UN cours (ownership clair) et peut être rattaché à une leçon
 * (lesson_id, optionnel) ou au cours entier. is_published = false → brouillon
 * (jamais visible d'un étudiant). Le contrôle d'appartenance et d'autorisation
 * est fait côté composant Livewire (autorisation SERVEUR, anti-IDOR).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $course_id
 * @property int|null    $lesson_id
 * @property string      $title
 * @property string|null $instructions
 * @property int         $max_points
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property bool        $is_published
 * @property int         $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Assignment extends Model
{
    protected $table = 'academy_assignments';

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'instructions',
        'max_points',
        'due_at',
        'is_published',
        'position',
    ];

    protected $casts = [
        'max_points'   => 'integer',
        'position'     => 'integer',
        'is_published' => 'boolean',
        'due_at'       => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** Devoirs PUBLIÉS uniquement (is_published = true). */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Consignes rendues en HTML SÛR (anti-XSS stockée) : on délègue au rendu
     * markdown unique de LessonItem::renderRichText (html_input=strip +
     * allow_unsafe_links=false). Rendu via {!! … !!} en toute sûreté.
     */
    public function renderedInstructions(): string
    {
        return LessonItem::renderRichText($this->instructions);
    }
}
