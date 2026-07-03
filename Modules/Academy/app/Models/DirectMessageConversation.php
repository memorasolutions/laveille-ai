<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) — fil privé entre EXACTEMENT deux utilisateurs qui
 * partagent une relation pédagogique commune sur un cours (formateur du cours
 * OU apprenant inscrit à ce cours). C'est la RÈGLE D'AUTORISATION CLÉ du
 * système : elle est revérifiée à CHAQUE tentative d'envoi (findOrCreateFor()),
 * jamais présumée acquise parce qu'un fil existe déjà en base (une inscription
 * annulée après coup doit couper l'accès immédiatement).
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
 * @property int    $id
 * @property int    $course_id
 * @property int    $user_one_id
 * @property int    $user_two_id
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DirectMessageConversation extends Model
{
    use SoftDeletes;

    protected $table = 'academy_dm_conversations';

    protected $fillable = [
        'course_id',
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DirectMessage::class, 'conversation_id');
    }

    /** Vrai si l'utilisateur donné est l'un des DEUX participants (anti-IDOR). */
    public function hasParticipant(User $user): bool
    {
        return (int) $user->id === (int) $this->user_one_id
            || (int) $user->id === (int) $this->user_two_id;
    }

    /** L'AUTRE participant de la conversation par rapport à l'utilisateur donné. */
    public function otherParticipant(User $user): ?User
    {
        if ((int) $user->id === (int) $this->user_one_id) {
            return $this->userTwo;
        }

        if ((int) $user->id === (int) $this->user_two_id) {
            return $this->userOne;
        }

        return null;
    }

    /** Scope : conversations où l'utilisateur donné est participant. */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user): void {
            $q->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id);
        });
    }

    /**
     * RÈGLE D'AUTORISATION CLÉ : deux utilisateurs peuvent-ils s'écrire ?
     * Vrai UNIQUEMENT s'ils partagent une inscription à un même cours, l'un
     * étant formateur du cours (course_roles) ET l'autre apprenant inscrit
     * ACTIF (enrollments.status = active) à ce MÊME cours — dans un sens ou
     * l'autre. Deux apprenants entre eux, ou deux formateurs entre eux SANS
     * qu'aucun ne soit inscrit comme apprenant, ne peuvent PAS échanger.
     *
     * Retourne l'id du premier cours qui satisfait la règle (pour traçabilité
     * UI), ou null si aucune relation pédagogique commune n'existe.
     */
    public static function sharedCourseIdFor(User $a, User $b): ?int
    {
        if ((int) $a->id === (int) $b->id) {
            return null;
        }

        $aInstructorCourseIds = CourseRole::query()
            ->where('user_id', $a->id)
            ->pluck('course_id');

        $aEnrolledCourseIds = Enrollment::query()
            ->where('user_id', $a->id)
            ->where('status', 'active')
            ->pluck('course_id');

        $bInstructorCourseIds = CourseRole::query()
            ->where('user_id', $b->id)
            ->pluck('course_id');

        $bEnrolledCourseIds = Enrollment::query()
            ->where('user_id', $b->id)
            ->where('status', 'active')
            ->pluck('course_id');

        // A formateur d'un cours où B est apprenant actif.
        $courseId = $aInstructorCourseIds->intersect($bEnrolledCourseIds)->first();
        if ($courseId !== null) {
            return (int) $courseId;
        }

        // B formateur d'un cours où A est apprenant actif.
        $courseId = $bInstructorCourseIds->intersect($aEnrolledCourseIds)->first();

        return $courseId !== null ? (int) $courseId : null;
    }

    /** Alias explicite demandé par la spec : « canMessageUser ». */
    public static function canMessage(User $a, User $b): bool
    {
        return self::sharedCourseIdFor($a, $b) !== null;
    }

    /**
     * Trouve le fil existant entre deux utilisateurs (peu importe l'ordre),
     * ou en crée un NOUVEAU — mais SEULEMENT si canMessage() est vrai. Lève
     * une exception métier sinon (le composant Livewire traduit en abort_if).
     */
    public static function findOrCreateFor(User $a, User $b): self
    {
        $courseId = self::sharedCourseIdFor($a, $b);

        if ($courseId === null) {
            throw new \RuntimeException('Aucune relation pédagogique commune entre ces deux utilisateurs.');
        }

        [$oneId, $twoId] = $a->id < $b->id ? [$a->id, $b->id] : [$b->id, $a->id];

        return self::query()->firstOrCreate(
            [
                'course_id'   => $courseId,
                'user_one_id' => $oneId,
                'user_two_id' => $twoId,
            ],
        );
    }
}
