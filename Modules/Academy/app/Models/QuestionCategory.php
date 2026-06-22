<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Catégorie de la banque de questions réutilisable (QB1). Arbre owner-scoped :
 * un formateur ne gère que SES catégories ; l'admin (academy.manage) voit tout
 * — l'enforcement d'autorisation est SERVEUR (QB2). Ici : relations + scopes purs.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int|null    $owner_id
 * @property int|null    $parent_id
 * @property string      $name
 * @property string|null $slug
 * @property int         $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class QuestionCategory extends Model
{
    protected $table = 'academy_question_categories';

    protected $fillable = [
        'owner_id',
        'parent_id',
        'name',
        'slug',
        'position',
    ];

    protected $casts = [
        'owner_id'  => 'integer',
        'parent_id' => 'integer',
        'position'  => 'integer',
    ];

    /** Formateur propriétaire. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Catégorie parente (null = racine). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Sous-catégories directes. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** Questions rattachées directement à cette catégorie. */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'category_id');
    }

    /**
     * Scope : catégories appartenant à un propriétaire donné.
     *
     * @param  \App\Models\User|int $user
     */
    public function scopeOwned(Builder $query, $user): Builder
    {
        $ownerId = $user instanceof User ? $user->getKey() : (int) $user;

        return $query->where('owner_id', $ownerId);
    }

    /**
     * IDs de cette catégorie + toutes ses sous-catégories (récursif),
     * borné à l'arbre du MÊME propriétaire (anti-fuite inter-formateurs).
     * Sert au tirage récursif de QuestionBankService.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids   = [(int) $this->getKey()];
        $queue = [(int) $this->getKey()];

        // Largeur d'abord, scopé au propriétaire de la racine (null compris).
        while ($queue !== []) {
            $children = self::query()
                ->whereIn('parent_id', $queue)
                ->where('owner_id', $this->owner_id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $new = array_values(array_diff($children, $ids));
            if ($new === []) {
                break;
            }

            $ids   = array_merge($ids, $new);
            $queue = $new;
        }

        return array_values(array_unique($ids));
    }

    /**
     * QB3 : nombre de questions ACTIVES disponibles dans cette catégorie EN INCLUANT
     * toute sa descendance (parité Moodle : un parent tire aussi ses sous-catégories).
     * Borné à l'arbre du même propriétaire via descendantIds(). Pour une catégorie
     * feuille, le résultat est identique au compte direct.
     */
    public function activeQuestionCountDeep(): int
    {
        return (int) Question::query()
            ->active()
            ->whereIn('category_id', $this->descendantIds())
            ->count();
    }
}
