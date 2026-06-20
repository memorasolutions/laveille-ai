<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gestion front-end des INSCRIPTIONS (roster) et des RÔLES DE COURS (équipe) -
 * PHASE 4 (FE-4). Composant Livewire rendu sous l'éditeur de cours.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant du cours est figé au montage ($courseId, propriété privée).
 *  - À CHAQUE mutation, le cours est RE-RÉSOLU côté serveur via resolveCourse()
 *    puis RÉ-AUTORISÉ par $this->authorize(...) sur la Policy posée en FE-1 :
 *      • inscriptions (roster) → authorize('manageEnrollments', $course)
 *        (admin OU owner/instructor du cours)
 *      • rôles de cours        → authorize('manageRoles', $course)
 *        (admin OU owner du cours UNIQUEMENT)
 *  - On ne fait JAMAIS confiance à un ID/état venant du navigateur. Chaque
 *    Enrollment / CourseRole ciblé est RE-RÉSOLU et SCOPÉ à CE cours (anti-IDOR)
 *    avant toute écriture : un objet d'un AUTRE cours → ModelNotFound, rien écrit.
 *  - Le rôle ajouté est validé contre une LISTE BLANCHE {instructor,assistant,
 *    editor} ; 'owner' n'est JAMAIS attribuable ni retirable par ce composant.
 *  - @can(...) en Blade ne sert qu'à CACHER des boutons (jamais l'unique garde).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;

class CourseRoster extends Component
{
    /** Rôles de cours attribuables (liste blanche, alignée sur l'admin Backoffice). 'owner' EXCLU. */
    private const ASSIGNABLE_ROLES = ['instructor', 'assistant', 'editor'];

    /** Identifiant du cours géré (figé au montage, source de vérité serveur). */
    public int $courseId;

    // ── Saisie : inscrire un étudiant par courriel ───────────────────────────────
    public string $enrollEmail = '';

    // ── Saisie : ajouter un rôle d'équipe par courriel ───────────────────────────
    public string $roleEmail = '';
    public string $roleName = 'instructor';

    // ── Confirmations inline à 2 temps (jamais confirm() natif) ──────────────────
    public ?int $confirmingEnrollmentRemoval = null;
    public ?int $confirmingRoleRemoval = null;

    /**
     * Entrée. Autorisation SERVEUR d'affichage : pour voir CET espace il faut au
     * minimum pouvoir gérer les inscriptions (admin OU owner/instructor du cours).
     * La gestion des rôles est en plus gâtée par 'manageRoles' (owner/admin).
     */
    public function mount(Course $course): void
    {
        $this->authorize('manageEnrollments', $course);

        $this->courseId = $course->id;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Résolution + autorisation serveur (cœur anti-escalade / anti-IDOR)
    // ─────────────────────────────────────────────────────────────────────────────

    /** Re-résout TOUJOURS le cours depuis la base (jamais depuis le navigateur). */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Re-résout un Enrollment ET vérifie qu'il appartient bien à CE cours (anti-IDOR).
     * Un Enrollment d'un autre cours → ModelNotFound, aucune écriture possible.
     */
    private function resolveEnrollmentFor(Course $course, int $enrollmentId): Enrollment
    {
        return Enrollment::where('id', $enrollmentId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * Re-résout un CourseRole ET vérifie qu'il appartient bien à CE cours (anti-IDOR).
     * Un rôle d'un autre cours → ModelNotFound, aucune écriture possible.
     */
    private function resolveRoleFor(Course $course, int $roleId): CourseRole
    {
        return CourseRole::where('id', $roleId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ROSTER (inscriptions) - gâté manageEnrollments
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Inscrit un utilisateur EXISTANT par courriel (status='active', source='admin').
     * Si aucun compte n'existe pour ce courriel : message d'erreur clair, AUCUN
     * compte n'est créé (on n'invite pas, on ne provisionne pas d'utilisateur ici).
     */
    public function enrollByEmail(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $data = $this->validate([
            'enrollEmail' => 'required|email|max:255',
        ]);

        $user = User::where('email', $data['enrollEmail'])->first();

        if (! $user) {
            $this->addError('enrollEmail', "Aucun compte n'existe pour ce courriel. L'utilisateur doit d'abord se créer un compte.");

            return;
        }

        // Idempotence : un Enrollment annulé est réactivé ; un actif reste actif.
        $enrollment = Enrollment::firstOrNew([
            'course_id' => $course->id,
            'user_id'   => $user->id,
        ]);

        if ($enrollment->exists && $enrollment->status === 'active') {
            $this->addError('enrollEmail', 'Cet utilisateur est déjà inscrit à ce cours.');

            return;
        }

        $enrollment->fill([
            'status'       => 'active',
            'source'       => 'admin',
            'enrolled_at'  => now(),
            'cancelled_at' => null,
        ])->save();

        $this->reset('enrollEmail');
        session()->flash('academy_roster_status', 'Utilisateur inscrit au cours.');
    }

    /**
     * Désinscrit (status='cancelled' - choix auditable, on ne supprime pas la ligne).
     * L'Enrollment est re-résolu et SCOPÉ à CE cours (anti-IDOR).
     */
    public function cancelEnrollment(int $enrollmentId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageEnrollments', $course);

        $enrollment = $this->resolveEnrollmentFor($course, $enrollmentId);

        $enrollment->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->confirmingEnrollmentRemoval = null;
        session()->flash('academy_roster_status', 'Inscription annulée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // RÔLES DE COURS (équipe) - gâté manageRoles (owner/admin UNIQUEMENT)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Ajoute un rôle d'équipe à un utilisateur EXISTANT par courriel.
     * Le rôle est validé contre la liste blanche {instructor,assistant,editor}
     * ('owner' jamais attribuable). Unicité user+course (un cours créé par un
     * formateur a déjà son créateur en 'owner' : on ne duplique pas).
     */
    public function addRoleByEmail(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageRoles', $course);

        $data = $this->validate([
            'roleEmail' => 'required|email|max:255',
            'roleName'  => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
        ]);

        $user = User::where('email', $data['roleEmail'])->first();

        if (! $user) {
            $this->addError('roleEmail', "Aucun compte n'existe pour ce courriel. L'utilisateur doit d'abord se créer un compte.");

            return;
        }

        $exists = CourseRole::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            $this->addError('roleEmail', 'Cet utilisateur a déjà un rôle dans ce cours.');

            return;
        }

        CourseRole::create([
            'course_id' => $course->id,
            'user_id'   => $user->id,
            'role'      => $data['roleName'],
        ]);

        $this->reset('roleEmail');
        session()->flash('academy_roster_status', 'Rôle attribué.');
    }

    /**
     * Retire un rôle d'équipe. Le CourseRole est re-résolu et SCOPÉ à CE cours
     * (anti-IDOR). Le rôle 'owner' n'est JAMAIS retirable par ce composant.
     */
    public function removeRole(int $roleId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageRoles', $course);

        $role = $this->resolveRoleFor($course, $roleId);

        if ($role->role === 'owner') {
            $this->confirmingRoleRemoval = null;
            $this->addError('roleEmail', "Le rôle « propriétaire » ne peut pas être retiré.");

            return;
        }

        $role->delete();

        $this->confirmingRoleRemoval = null;
        session()->flash('academy_roster_status', 'Rôle retiré.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps (jamais de popup native)
    // ─────────────────────────────────────────────────────────────────────────────

    public function confirmEnrollmentRemoval(int $enrollmentId): void
    {
        $this->confirmingEnrollmentRemoval = $enrollmentId;
    }

    public function cancelEnrollmentRemoval(): void
    {
        $this->confirmingEnrollmentRemoval = null;
    }

    public function confirmRoleRemoval(int $roleId): void
    {
        $this->confirmingRoleRemoval = $roleId;
    }

    public function cancelRoleRemoval(): void
    {
        $this->confirmingRoleRemoval = null;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Lecture (affichage) - listes fraîches, scopées à CE cours
    // ─────────────────────────────────────────────────────────────────────────────

    /** Le cours frais (sert les directives @can dans la vue). */
    #[Computed]
    public function course(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /** Inscriptions de CE cours, avec l'utilisateur, les plus récentes d'abord. */
    #[Computed]
    public function enrollments()
    {
        return Enrollment::where('course_id', $this->courseId)
            ->with('user')
            ->orderByDesc('enrolled_at')
            ->orderByDesc('id')
            ->get();
    }

    /** Membres de l'équipe (rôles) de CE cours, avec l'utilisateur. */
    #[Computed]
    public function teamRoles()
    {
        return CourseRole::where('course_id', $this->courseId)
            ->with('user')
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }

    /** Libellé lisible d'un rôle (FR). */
    public function roleLabel(string $role): string
    {
        return match ($role) {
            'owner'      => 'Propriétaire',
            'instructor' => 'Formateur',
            'assistant'  => 'Assistant',
            'editor'     => 'Éditeur',
            default      => $role,
        };
    }

    /** Libellé lisible d'un statut d'inscription (FR). */
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'active'    => 'Active',
            'pending'   => 'En attente',
            'cancelled' => 'Annulée',
            default     => $status,
        };
    }

    public function render()
    {
        return view('academy::livewire.course-roster');
    }
}
