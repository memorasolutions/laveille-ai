<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : une ENTRÉE (fiche) soumise par un inscrit selon
 * le schéma d'une activité « database » (type Moodle « Database »). Porte une valeur par
 * champ (hasMany DatabaseValue). user_id = auteur (null si compte supprimé => « (inconnu) »).
 * is_approved : visible de tous ; false = en attente de modération (visible de l'auteur +
 * gérants seulement). SoftDeletes : une fiche supprimée est conservée (audit), exclue par défaut.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $lesson_item_id
 * @property int|null    $user_id
 * @property bool        $is_approved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DatabaseEntry extends Model
{
    use SoftDeletes;

    protected $table = 'academy_database_entries';

    protected $fillable = [
        'lesson_item_id',
        'user_id',
        'is_approved',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'user_id'        => 'integer',
        'is_approved'    => 'boolean',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    /** Auteur de la fiche (null si compte supprimé). */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(DatabaseValue::class, 'database_entry_id');
    }

    /** Scope : entrées d'un item « database » donné. */
    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('lesson_item_id', $itemId);
    }

    /** Scope : entrées approuvées (visibles de tous). */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }
}
