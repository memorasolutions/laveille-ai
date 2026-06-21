<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Espace personnel front-end UNIQUE et role-aware de l'Académie (PHASE 2).
 *
 * Modèle de sécurité (OWASP A01, autorisation SERVEUR) :
 *  - « Mes formations » : tout utilisateur connecté voit SES propres inscriptions
 *    (requête scopée à auth()->id(), aucune Policy car ce sont ses données).
 *  - « Mes cours » (gestion) : visible seulement si can('viewAny', Course::class)
 *    (admin OU formateur). La liste est SCOPÉE :
 *      • admin (can academy.manage) → tous les cours ;
 *      • formateur                  → uniquement les cours où il figure dans
 *        course_roles (whereHas). Un formateur ne voit JAMAIS le cours d'un autre.
 *  L'UI ne fait que refléter ce que ces requêtes scopées renvoient.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\CourseDuplicator;

class Dashboard extends Component
{
    /**
     * Id du cours dont la duplication est en attente de confirmation (inline à 2
     * temps, jamais de popup native). null = aucune confirmation en cours.
     */
    public ?int $confirmingDuplicationId = null;

    /**
     * Sécurité : la route exige déjà le middleware `auth`. On ne fait PAS
     * $this->authorize('viewAny', ...) ici car tout utilisateur connecté a le
     * droit de voir SES formations (ce sont ses données). La gestion « Mes cours »
     * est gâtée plus bas par can('viewAny', ...) ET scopée par course_roles/admin.
     */
    public function mount(): void
    {
        abort_unless(Auth::check(), 403);
    }

    /**
     * Mes formations : inscriptions actives de l'utilisateur courant, avec
     * progression et certificats. Strictement scopé à auth()->id().
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function enrollments(): Collection
    {
        $user = Auth::user();

        return \Modules\Academy\Models\Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['course:id,slug,title'])
            ->get()
            ->map(function ($enrollment) use ($user): array {
                $course = $enrollment->course;

                $progress = $course
                    ? \Modules\Academy\Models\Progress::query()
                        ->where('user_id', $user->id)
                        ->where('course_id', $course->id)
                        ->first()
                    : null;

                $certificate = $course
                    ? \Modules\Academy\Models\CertificateIssued::query()
                        ->where('user_id', $user->id)
                        ->where('course_id', $course->id)
                        ->first()
                    : null;

                return [
                    'course'      => $course,
                    'percent'     => (int) ($progress->percent ?? 0),
                    'firstLesson' => $course ? $this->firstLessonOf($course) : null,
                    'certificate' => $certificate,
                ];
            })
            ->filter(fn (array $row): bool => $row['course'] !== null)
            ->values();
    }

    /**
     * Mes cours (gestion) : visible uniquement pour admin/formateur.
     * Requête SCOPÉE obligatoire (anti-fuite) :
     *  - admin (academy.manage)  → tous les cours ;
     *  - formateur               → uniquement ceux où il a un course_role.
     *
     * @return Collection<int, Course>
     */
    #[Computed]
    public function managedCourses(): Collection
    {
        $user = Auth::user();

        if (! $user->can('viewAny', Course::class)) {
            return collect();
        }

        $query = Course::query()
            ->withCount(['chapters'])
            ->with(['courseRoles' => fn ($q) => $q->where('user_id', $user->id)]);

        // SCOPING (cœur anti-escalade) : l'admin voit tout ; sinon on RESTREINT
        // aux cours où l'utilisateur figure dans course_roles.
        if (! $user->can('academy.manage')) {
            $query->whereHas('courseRoles', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Course $course) use ($user): Course {
                // Nombre de leçons (chapitres → leçons) et badge de rôle, calculés
                // sans exposer de données d'autres cours.
                $course->setAttribute('lessons_count', $this->lessonsCountOf($course));
                $course->setAttribute('viewer_role', $this->viewerRoleOf($course, $user));

                return $course;
            });
    }

    /**
     * Modèles réutilisables (is_template = true) VISIBLES par l'utilisateur, pour la
     * section « Modèles ». Requête SCOPÉE (même logique anti-fuite que managedCourses) :
     *  - admin (academy.manage)  → tous les modèles ;
     *  - formateur               → uniquement ses modèles (course_roles).
     * Un duplicateur n'utilise que des modèles qu'il a déjà le droit de gérer.
     *
     * @return Collection<int, Course>
     */
    #[Computed]
    public function managedTemplates(): Collection
    {
        $user = Auth::user();

        if (! $user->can('viewAny', Course::class)) {
            return collect();
        }

        $query = Course::query()->where('is_template', true);

        if (! $user->can('academy.manage')) {
            $query->whereHas('courseRoles', fn ($q) => $q->where('user_id', $user->id));
        }

        return $query->orderByDesc('updated_at')->get();
    }

    /**
     * Duplique un cours (action « Dupliquer » ou « Utiliser ce modèle »).
     *
     * SÉCURITÉ (OWASP A01) : l'id vient du navigateur → on NE lui fait jamais
     * confiance. On RE-RÉSOUT le cours source côté serveur, on VÉRIFIE que l'utilisateur
     * a le droit de le voir/gérer (authorize('update') = admin OU rôle de cours) ET
     * qu'il peut créer un cours (authorize('create')). Le nouvel owner est TOUJOURS
     * l'utilisateur connecté, jamais une valeur venue du client.
     */
    public function duplicate(int $courseId, CourseDuplicator $duplicator)
    {
        $user   = Auth::user();
        $source = Course::findOrFail($courseId); // re-résolution serveur (jamais le client)

        // Peut-il gérer CE cours source ? (admin OU owner/instructor/editor du cours)
        $this->authorize('update', $source);
        // Peut-il créer un cours ? (admin OU formateur)
        $this->authorize('create', Course::class);

        $copy = $duplicator->duplicate($source, $user);

        $this->confirmingDuplicationId = null;
        session()->flash('academy_dashboard_status', 'Cours dupliqué : « '.$copy->title.' ».');

        return redirect()->route('academy.courses.manage', $copy->slug);
    }

    /** Demande de confirmation inline de duplication (à 2 temps, jamais confirm() natif). */
    public function confirmDuplication(int $courseId): void
    {
        $this->confirmingDuplicationId = $courseId;
    }

    public function cancelDuplication(): void
    {
        $this->confirmingDuplicationId = null;
    }

    /** L'utilisateur courant est-il administrateur de l'Académie ? */
    #[Computed]
    public function isAcademyAdmin(): bool
    {
        return (bool) Auth::user()?->can('academy.manage');
    }

    /** Peut-il accéder à un espace de gestion (admin OU formateur) ? */
    #[Computed]
    public function canManageCourses(): bool
    {
        return (bool) Auth::user()?->can('viewAny', Course::class);
    }

    /** Peut-il créer un cours ? (admin OU formateur, via CoursePolicy::create) */
    #[Computed]
    public function canCreateCourse(): bool
    {
        return (bool) Auth::user()?->can('create', Course::class);
    }

    /** 1re leçon d'un cours (pour le lien « Continuer »), ou null. */
    private function firstLessonOf(Course $course): ?\Modules\Academy\Models\Lesson
    {
        return \Modules\Academy\Models\Lesson::query()
            ->whereHas('chapter', fn ($q) => $q->where('course_id', $course->id))
            ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->where('chapters.course_id', $course->id)
            ->orderBy('chapters.position')
            ->orderBy('lessons.position')
            ->select('lessons.*')
            ->first();
    }

    /** Nombre de leçons d'un cours (toutes chapitres confondus). */
    private function lessonsCountOf(Course $course): int
    {
        return \Modules\Academy\Models\Lesson::query()
            ->whereHas('chapter', fn ($q) => $q->where('course_id', $course->id))
            ->count();
    }

    /** Libellé du rôle de l'utilisateur sur ce cours (Propriétaire / Formateur). */
    private function viewerRoleOf(Course $course, $user): string
    {
        if ($course->hasRole($user, ['owner'])) {
            return 'Propriétaire';
        }

        if ($course->hasRole($user, ['instructor', 'assistant', 'editor'])) {
            return 'Formateur';
        }

        return 'Administrateur';
    }

    public function render()
    {
        return view('academy::livewire.dashboard');
    }
}
