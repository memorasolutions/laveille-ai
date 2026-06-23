<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F17 - Etiquette (tag) de la banque de questions. Owner-scopee : un formateur ne
 * voit/attache QUE ses propres tags. Le slug est unique PAR proprietaire. Aucune
 * logique UI ici (relations + scopes purs).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int    $id
 * @property int    $owner_id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class QuestionTag extends Model
{
    protected $table = 'academy_question_tags';

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
    ];

    protected $casts = [
        'owner_id' => 'integer',
    ];

    /** Formateur proprietaire. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Questions portant ce tag. */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'academy_question_tag', 'tag_id', 'question_id')
            ->withTimestamps();
    }

    /** Scope : tags appartenant a un proprietaire donne. */
    public function scopeOwned(Builder $query, $user): Builder
    {
        $ownerId = $user instanceof User ? $user->getKey() : (int) $user;

        return $query->where('owner_id', $ownerId);
    }

    /**
     * Normalise un libelle en slug stable (sert de cle d'unicite owner-scope et de
     * deduplication a la creation a la volee). Vide si le libelle ne produit rien.
     */
    public static function slugify(string $name): string
    {
        return Str::slug(trim($name));
    }
}
