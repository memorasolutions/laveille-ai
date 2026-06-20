<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Policy OWNERSHIP-AWARE pour les chapitres. La gestion s'autorise via le
 * COURS PARENT (chapter->course) :
 *   can('academy.manage')  OU  course->hasRole($user, ['owner','instructor','editor']).
 * Jamais sur la seule permission globale academy.lessons.manage (faille A01).
 */

declare(strict_types=1);

namespace Modules\Academy\Policies;

use App\Models\User;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;

class ChapterPolicy
{
    /** Espace de gestion : admin OU formateur (filtrage par cours via requête scoped). */
    public function viewAny(User $user): bool
    {
        return $user->can('academy.manage') || $user->hasRole('instructor');
    }

    /** Lecture (gestion) : admin OU gestionnaire du cours parent. */
    public function view(User $user, Chapter $chapter): bool
    {
        return $this->canManageParentCourse($user, $chapter);
    }

    /** Création : admin OU gestionnaire du cours parent. */
    public function create(User $user, Chapter $chapter): bool
    {
        return $this->canManageParentCourse($user, $chapter);
    }

    /** Édition : admin OU gestionnaire du cours parent. */
    public function update(User $user, Chapter $chapter): bool
    {
        return $this->canManageParentCourse($user, $chapter);
    }

    /** Suppression : admin OU gestionnaire du cours parent. */
    public function delete(User $user, Chapter $chapter): bool
    {
        return $this->canManageParentCourse($user, $chapter);
    }

    /** Gestion générique : admin OU gestionnaire du cours parent. */
    public function manage(User $user, Chapter $chapter): bool
    {
        return $this->canManageParentCourse($user, $chapter);
    }

    /**
     * Autorisation ownership-aware : remonte au cours parent (chapter->course)
     * puis applique rôle+ownership. Cours orphelin → seul l'admin passe.
     */
    private function canManageParentCourse(User $user, Chapter $chapter): bool
    {
        if ($user->can('academy.manage')) {
            return true;
        }

        $course = $chapter->course;

        if (! $course instanceof Course) {
            return false;
        }

        return $course->hasRole($user, ['owner', 'instructor', 'editor']);
    }
}
