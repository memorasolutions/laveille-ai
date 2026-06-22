<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-a - NIVEAU d'un critère de grille (rubric). Appartient à UN critère
 * (criterion_id) : libellé + points (>=0). À la correction, le formateur retient
 * UN niveau par critère ; la note brute = somme des points retenus. Le contrôle
 * d'appartenance/autorisation est fait côté composant Livewire (anti-IDOR : tout
 * niveau ciblé est re-résolu scopé au critère → devoir → cours).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $criterion_id
 * @property string $description
 * @property int    $points
 * @property int    $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RubricLevel extends Model
{
    protected $table = 'academy_rubric_levels';

    protected $fillable = [
        'criterion_id',
        'description',
        'points',
        'position',
    ];

    protected $casts = [
        'criterion_id' => 'integer',
        'points'       => 'integer',
        'position'     => 'integer',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'criterion_id');
    }

    /** Libellé rendu en HTML SÛR (anti-XSS stockée), rendu via {!! … !!}. */
    public function renderedDescription(): string
    {
        return LessonItem::renderRichText($this->description);
    }
}
