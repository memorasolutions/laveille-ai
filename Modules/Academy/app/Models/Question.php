<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Question réutilisable de la banque (QB1). Le payload (cast array) porte la
 * structure propre au type ; QuestionBankService la traduit au format scoré par
 * QuizService::score(). Ici : relations, scopes, accessors purs (aucune logique UI).
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $category_id
 * @property int|null    $owner_id
 * @property string      $type
 * @property string      $prompt
 * @property array       $payload
 * @property string|null $explanation
 * @property string|null $difficulty
 * @property int         $points
 * @property bool        $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Question extends Model
{
    protected $table = 'academy_questions';

    /**
     * Types de questions supportés (reproduisent QtService / QuizService::score).
     * `essay` (ajouté en dernier, rétrocompat de l'ordre) = réponse rédigée à
     * CORRECTION MANUELLE (type Moodle « Essay ») : aucun scoring auto, le formateur
     * attribue les points après coup (cf. QuizService::score / EssayGradingService).
     */
    public const TYPES = ['mcq', 'truefalse', 'short', 'matching', 'ordering', 'cloze', 'numerical', 'ddwtos', 'essay'];

    protected $fillable = [
        'category_id',
        'owner_id',
        'type',
        'prompt',
        'payload',
        'explanation',
        'difficulty',
        'points',
        'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'owner_id'    => 'integer',
        'payload'     => 'array',
        'points'      => 'integer',
        'is_active'   => 'boolean',
    ];

    /** Catégorie de rattachement. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    /** Formateur propriétaire. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * F17 - Etiquettes (tags) attachees a cette question. Additif : une question sans
     * tag se comporte exactement comme avant (relation vide).
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(QuestionTag::class, 'academy_question_tag', 'question_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * F17 - Historique des versions (etats precedents archives avant chaque edition),
     * de la plus recente a la plus ancienne.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class, 'question_id')->orderByDesc('version');
    }

    /** Scope : questions actives uniquement. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Scope : questions d'un propriétaire donné. */
    public function scopeOwned(Builder $query, $user): Builder
    {
        $ownerId = $user instanceof User ? $user->getKey() : (int) $user;

        return $query->where('owner_id', $ownerId);
    }

    /** Le type est-il reconnu par le moteur de scoring ? */
    public function hasSupportedType(): bool
    {
        return in_array($this->type, self::TYPES, true);
    }
}
