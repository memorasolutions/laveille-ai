<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable as ScoutSearchable;
use Modules\Academy\Models\Concerns\CourseSearchable;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\Media\Traits\HasMediaAttachments;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int         $id
 * @property string      $slug
 * @property string      $title
 * @property string|null $subtitle
 * @property string|null $summary
 * @property string|null $description
 * @property string      $language
 * @property string      $level
 * @property int|null    $duration_minutes
 * @property int|null    $image_media_id
 * @property string      $visibility
 * @property string      $access_type
 * @property int|null    $price_cents
 * @property string      $currency
 * @property string|null $stripe_price_id
 * @property string      $status
 * @property bool        $is_template
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property array|null  $seo_jsonld
 * @property array|null  $faq_dictionary_ids
 * @property int|null    $tools_collection_id
 * @property string|null $certificate_title
 * @property string|null $certificate_message
 * @property string|null $certificate_signature_name
 * @property string|null $certificate_accent_color
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Course extends Model implements HasMedia
{
    use CourseSearchable;  // toSearchableArray / shouldBeSearchable / searchableAs
    use HasMediaAttachments;  // upload/stockage Spatie (collection « cover », disque public)
    use LogsActivityStandard;
    use ScoutSearchable {
        // CourseSearchable gagne la résolution des conflits
        CourseSearchable::shouldBeSearchable insteadof ScoutSearchable;
        CourseSearchable::toSearchableArray  insteadof ScoutSearchable;
        CourseSearchable::searchableAs       insteadof ScoutSearchable;
    }
    use SoftDeletes;

    protected array $activitylogFields = ['title', 'status', 'visibility', 'access_type'];

    protected string $activitylogName = 'academy.course';

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'summary',
        'description',
        'language',
        'level',
        'duration_minutes',
        'image_media_id',
        'visibility',
        'access_type',
        'price_cents',
        'currency',
        'stripe_price_id',
        'status',
        'is_template',
        'published_at',
        'seo_jsonld',
        'faq_dictionary_ids',
        'tools_collection_id',
        'certificate_title',
        'certificate_message',
        'certificate_signature_name',
        'certificate_accent_color',
        'grade_letter_scheme',
        'completion_criteria',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at'        => 'datetime',
        'seo_jsonld'          => 'array',
        'faq_dictionary_ids'  => 'array',
        'duration_minutes'    => 'integer',
        'price_cents'         => 'integer',
        'is_template'         => 'boolean',
        'grade_letter_scheme' => 'array',
        'completion_criteria' => 'array',
    ];

    /**
     * Critère d'ACHÈVEMENT du cours, NORMALISÉ (source unique de la forme attendue).
     * NULL en base ou forme inconnue → défaut « all_required » (= comportement actuel,
     * rétrocompatibilité stricte). Voir Modules\Academy\Services\CourseCompletionService.
     *
     * @return array{type: string, value: int|null, items: array<int, int>}
     */
    public function completionCriteria(): array
    {
        return \Modules\Academy\Services\CourseCompletionService::criteriaFor($this);
    }

    // Relations

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courseRoles(): HasMany
    {
        return $this->hasMany(CourseRole::class);
    }

    /**
     * Annonces du cours (D3), les plus récemment publiées d'abord (puis création).
     * Le tri met les brouillons - published_at null - après les publiées.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class)
            ->orderByRaw('published_at IS NULL')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class);
    }

    /**
     * Devoirs (assignments) du cours (E2), triés par position puis création.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function certificatesIssued(): HasMany
    {
        return $this->hasMany(CertificateIssued::class);
    }

    /**
     * V2-b - Catégories de notes pondérées du cours (gradebook), ordonnées.
     * Aucune catégorie = carnet en agrégation simple (rétrocompat).
     */
    public function gradeCategories(): HasMany
    {
        return $this->hasMany(GradeCategory::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    /** V2-b - Affectations item↔catégorie (poids) du cours. */
    public function gradeItems(): HasMany
    {
        return $this->hasMany(GradeItem::class);
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    /**
     * Cours prérequis (C4) : les AUTRES cours qu'un apprenant doit avoir complétés
     * (100 %) avant de pouvoir s'inscrire / accéder à CE cours. Relation many-to-many
     * réflexive via la table de liaison academy_course_prerequisites
     * (course_id → prerequisite_course_id).
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'academy_course_prerequisites',
            'course_id',
            'prerequisite_course_id'
        )->withTimestamps();
    }

    /**
     * Liste des cours prérequis NON ENCORE complétés (à 100 %) par l'utilisateur.
     * Réutilise le mécanisme de progression existant (Progress.percent) : un cours
     * est « complété » si l'utilisateur a une ligne progresses à percent = 100.
     *
     * Calcul SERVEUR : ne se base que sur les données persistées, jamais sur le
     * client. Retourne une collection (vide = tous les prérequis sont satisfaits).
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Academy\Models\Course>
     */
    public function prerequisitesUnmetFor(User $user): \Illuminate\Support\Collection
    {
        $prerequisites = $this->prerequisites()->get();

        if ($prerequisites->isEmpty()) {
            return collect();
        }

        // IDs des cours complétés (100 %) par cet utilisateur, parmi les prérequis.
        $completedCourseIds = Progress::query()
            ->where('user_id', $user->id)
            ->whereIn('course_id', $prerequisites->pluck('id'))
            ->where('percent', '>=', 100)
            ->pluck('course_id')
            ->all();

        return $prerequisites->reject(
            fn (Course $prereq) => in_array($prereq->id, $completedCourseIds, true)
        )->values();
    }

    /**
     * Vrai si l'utilisateur a satisfait TOUS les prérequis de ce cours (ou s'il n'y
     * en a aucun). Garde serveur réutilisée par l'inscription et l'accès au contenu.
     */
    public function prerequisitesMetFor(User $user): bool
    {
        return $this->prerequisitesUnmetFor($user)->isEmpty();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('visibility', 'public');
    }

    // -------------------------------------------------------------------------
    // CourseRole helpers (M1)
    // -------------------------------------------------------------------------

    /**
     * Retourne vrai si l'utilisateur a l'un des rôles donnés sur ce cours.
     *
     * @param  \App\Models\User  $user
     * @param  string|string[]   $roles  ex. ['owner','instructor']
     */
    public function hasRole(\App\Models\User $user, string|array $roles = ['owner', 'instructor', 'assistant', 'editor']): bool
    {
        return $this->courseRoles()
            ->where('user_id', $user->id)
            ->whereIn('role', (array) $roles)
            ->exists();
    }

    /**
     * Alias sémantique : l'utilisateur est-il instructeur (ou owner) de ce cours ?
     */
    public function hasInstructor(\App\Models\User $user): bool
    {
        return $this->hasRole($user, ['owner', 'instructor']);
    }

    /**
     * L'utilisateur peut-il effectuer l'action donnée sur ce cours ?
     * Actuellement 'manage' ↔ owner|instructor|assistant.
     */
    public function userCan(\App\Models\User $user, string $action): bool
    {
        return match ($action) {
            'manage' => $this->hasRole($user, ['owner', 'instructor', 'assistant']),
            'edit'   => $this->hasRole($user, ['owner', 'instructor', 'assistant', 'editor']),
            default  => false,
        };
    }

    // -------------------------------------------------------------------------
    // Média : image de couverture (Spatie, collection « cover », disque public)
    // -------------------------------------------------------------------------

    /**
     * Collection unique « cover » : une seule image de couverture par cours,
     * restreinte aux formats web sûrs. `singleFile()` remplace automatiquement
     * l'ancienne lors d'un nouveau téléversement (pas d'accumulation).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * URL de l'image de couverture du cours (conversion optionnelle), ou null si
     * aucune image n'a été téléversée. On lit la collection Spatie « cover »
     * (source de vérité) ; `image_media_id` n'est qu'une référence numérique.
     */
    public function coverUrl(string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia('cover');

        return $media ? $media->getUrl($conversion) : null;
    }
}
