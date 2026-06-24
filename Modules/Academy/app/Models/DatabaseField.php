<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : un CHAMP du schéma d'une activité « database »
 * (type Moodle « Database »). Le schéma (l'ensemble des champs d'une activité) est défini
 * par le gérant dans l'éditeur de cours. Une entrée (fiche) porte une valeur par champ.
 * SoftDeletes : un champ retiré est conservé (les valeurs déjà saisies restent), exclu
 * des listes par le scope par défaut.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $lesson_item_id
 * @property string      $label
 * @property string      $name
 * @property string      $type
 * @property array|null  $options
 * @property bool        $required
 * @property int         $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DatabaseField extends Model
{
    use SoftDeletes;

    protected $table = 'academy_database_fields';

    protected $fillable = [
        'lesson_item_id',
        'label',
        'name',
        'type',
        'options',
        'required',
        'position',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'options'        => 'array',
        'required'       => 'boolean',
        'position'       => 'integer',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(DatabaseValue::class, 'database_field_id');
    }

    /** Scope : champs d'un item « database » donné, dans l'ordre du schéma. */
    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('lesson_item_id', $itemId)->orderBy('position')->orderBy('id');
    }
}
