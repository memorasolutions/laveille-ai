<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Policy OWNERSHIP-AWARE pour les leçons (et, par extension, chapitres / items).
 * La gestion d'une leçon s'autorise via le COURS PARENT (lesson->chapter->course) :
 *   can('academy.manage')  OU  course->hasRole($user, ['owner','instructor','editor']).
 * On ne se base JAMAIS sur la seule permission globale academy.lessons.manage
 * (sinon un formateur éditerait les leçons des cours d'autrui = faille A01).
 */

declare(strict_types=1);

namespace Modules\Academy\Policies;

use App\Models\User;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;

class LessonPolicy
{
    /**
     * Accès à un espace de gestion des leçons : admin OU formateur (filtrage
     * par cours fait par requête scoped au niveau du contrôleur).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('academy.manage') || $user->hasRole('instructor');
    }

    /**
     * Lecture d'une leçon (côté gestion) : admin OU gestionnaire du cours parent.
     * La vérification d'enrollment côté apprenant est déléguée au contrôleur.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        return $this->canManageParentCourse($user, $lesson);
    }

    /**
     * Création d'une leçon : admin OU gestionnaire du cours parent.
     */
    public function create(User $user, Lesson $lesson): bool
    {
        return $this->canManageParentCourse($user, $lesson);
    }

    /**
     * Édition d'une leçon : admin OU gestionnaire du cours parent.
     */
    public function update(User $user, Lesson $lesson): bool
    {
        return $this->canManageParentCourse($user, $lesson);
    }

    /**
     * Suppression d'une leçon : admin OU gestionnaire du cours parent.
     */
    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->canManageParentCourse($user, $lesson);
    }

    /**
     * Gestion générique d'une leçon : admin OU gestionnaire du cours parent.
     */
    public function manage(User $user, Lesson $lesson): bool
    {
        return $this->canManageParentCourse($user, $lesson);
    }

    /**
     * Cœur de l'autorisation ownership-aware : remonte au cours parent
     * (lesson->chapter->course) puis applique la règle rôle+ownership.
     * Si le cours parent est introuvable (donnée orpheline), seul l'admin passe.
     */
    private function canManageParentCourse(User $user, Lesson $lesson): bool
    {
        if ($user->can('academy.manage')) {
            return true;
        }

        $course = $lesson->chapter?->course;

        if (! $course instanceof Course) {
            return false;
        }

        return $course->hasRole($user, ['owner', 'instructor', 'editor']);
    }
}
