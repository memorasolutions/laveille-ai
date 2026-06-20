<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Modèle de sécurité « rôle + ownership » (OWASP A01, autorisation serveur).
 * - admin (academy.manage) : gère TOUS les cours.
 * - formateur (rôle Spatie instructor) : gère UNIQUEMENT les cours où il figure
 *   dans course_roles (owner/instructor/editor selon l'action). Sa permission
 *   globale ne lui donne JAMAIS accès au cours d'un autre.
 * - étudiant (rôle Spatie student) : voit + s'inscrit, aucune gestion.
 *
 * Règle d'or : chaque capacité de GESTION =
 *   can('academy.manage')  OU  course->hasRole($user, [rôles permis]).
 * On ne base JAMAIS une autorisation par-cours sur les permissions globales
 * academy.courses.* seules (sinon un formateur éditerait tous les cours = faille).
 */

declare(strict_types=1);

namespace Modules\Academy\Policies;

use App\Models\User;
use Modules\Academy\Models\Course;

class CoursePolicy
{
    /**
     * Accès à un espace de gestion : admin (tous cours) OU formateur (verra SES
     * cours via une requête scoped au niveau du contrôleur, pas ici).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('academy.manage') || $user->hasRole('instructor');
    }

    /**
     * Lecture d'un cours : publié+public pour tous, sinon admin OU instructeur du cours.
     */
    public function view(User $user, Course $course): bool
    {
        if ($course->status === 'published' && $course->visibility === 'public') {
            return true;
        }

        if ($user->can('academy.manage')) {
            return true;
        }

        return $course->hasInstructor($user);
    }

    /**
     * Création d'un cours : admin OU formateur (il en deviendra owner via course_roles).
     */
    public function create(User $user): bool
    {
        return $user->can('academy.manage') || $user->hasRole('instructor');
    }

    /**
     * Édition d'un cours : admin (tous) OU owner/instructor/editor de CE cours.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->can('academy.manage')
            || $course->hasRole($user, ['owner', 'instructor', 'editor']);
    }

    /**
     * Gestion de la structure (chapitres/leçons/items) : admin OU owner/instructor/editor de CE cours.
     */
    public function manageStructure(User $user, Course $course): bool
    {
        return $user->can('academy.manage')
            || $course->hasRole($user, ['owner', 'instructor', 'editor']);
    }

    /**
     * Publication : admin OU owner/instructor de CE cours (un editor ne publie pas).
     */
    public function publish(User $user, Course $course): bool
    {
        return $user->can('academy.manage')
            || $course->hasRole($user, ['owner', 'instructor']);
    }

    /**
     * Suppression : admin OU owner de CE cours uniquement (action la plus sensible).
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->can('academy.manage')
            || $course->hasRole($user, ['owner']);
    }

    /**
     * Gestion des inscriptions : admin OU owner/instructor de CE cours.
     */
    public function manageEnrollments(User $user, Course $course): bool
    {
        return $user->can('academy.manage')
            || $course->hasRole($user, ['owner', 'instructor']);
    }

    /**
     * Gestion des rôles du cours (assigner formateurs/assistants) : admin OU owner de CE cours.
     */
    public function manageRoles(User $user, Course $course): bool
    {
        return $user->can('academy.manage')
            || $course->hasRole($user, ['owner']);
    }

    /**
     * Inscription : permission academy.enroll OU cours gratuit publié (étudiant inclus).
     */
    public function enroll(User $user, Course $course): bool
    {
        if ($user->can('academy.enroll')) {
            return true;
        }

        return $course->access_type === 'free' && $course->status === 'published';
    }
}
