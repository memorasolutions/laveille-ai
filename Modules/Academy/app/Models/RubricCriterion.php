<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-a - CRITÈRE d'une grille d'évaluation (rubric). Appartient à UN devoir
 * (assignment_id) et porte N niveaux (RubricLevel). Le contrôle d'appartenance et
 * d'autorisation est fait côté composant Livewire (autorisation SERVEUR,
 * anti-IDOR : tout critère ciblé est re-résolu scopé au cours).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property int    $assignment_id
 * @property string $description
 * @property int    $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RubricCriterion extends Model
{
    protected $table = 'academy_rubric_criteria';

    protected $fillable = [
        'assignment_id',
        'description',
        'position',
    ];

    protected $casts = [
        'assignment_id' => 'integer',
        'position'      => 'integer',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** Niveaux du critère, ordonnés (points croissants par convention de saisie). */
    public function levels(): HasMany
    {
        return $this->hasMany(RubricLevel::class, 'criterion_id')
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Libellé rendu en HTML SÛR (anti-XSS stockée), via le rendu markdown unique
     * de LessonItem::renderRichText. Rendu via {!! … !!} en toute sûreté.
     */
    public function renderedDescription(): string
    {
        return LessonItem::renderRichText($this->description);
    }
}
