<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - LIEN compétence <-> cible (cours OU item de leçon). Un lien porte EXACTEMENT
 * une cible : course_id XOR lesson_item_id (garanti par CourseCompetencies à la
 * création). Modèle pur (relations) ; aucune autorisation ici (faite SERVEUR côté
 * composant : compétence scopée owner, cours/item re-résolus, item re-scopé au cours).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int      $id
 * @property int      $competency_id
 * @property int|null $course_id
 * @property int|null $lesson_item_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CompetencyLink extends Model
{
    protected $table = 'academy_competency_links';

    protected $fillable = [
        'competency_id',
        'course_id',
        'lesson_item_id',
    ];

    protected $casts = [
        'competency_id'  => 'integer',
        'course_id'      => 'integer',
        'lesson_item_id' => 'integer',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class, 'competency_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }
}
