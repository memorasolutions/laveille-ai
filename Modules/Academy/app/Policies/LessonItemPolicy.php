<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Policy OWNERSHIP-AWARE pour les éléments de leçon. La gestion s'autorise via
 * le COURS PARENT (item->lesson->chapter->course) :
 *   can('academy.manage')  OU  course->hasRole($user, ['owner','instructor','editor']).
 * Jamais sur la seule permission globale academy.lessons.manage (faille A01).
 */

declare(strict_types=1);

namespace Modules\Academy\Policies;

use App\Models\User;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\LessonItem;

class LessonItemPolicy
{
    /** Espace de gestion : admin OU formateur (filtrage par cours via requête scoped). */
    public function viewAny(User $user): bool
    {
        return $user->can('academy.manage') || $user->hasRole('instructor');
    }

    /** Lecture (gestion) : admin OU gestionnaire du cours parent. */
    public function view(User $user, LessonItem $item): bool
    {
        return $this->canManageParentCourse($user, $item);
    }

    /** Création : admin OU gestionnaire du cours parent. */
    public function create(User $user, LessonItem $item): bool
    {
        return $this->canManageParentCourse($user, $item);
    }

    /** Édition : admin OU gestionnaire du cours parent. */
    public function update(User $user, LessonItem $item): bool
    {
        return $this->canManageParentCourse($user, $item);
    }

    /** Suppression : admin OU gestionnaire du cours parent. */
    public function delete(User $user, LessonItem $item): bool
    {
        return $this->canManageParentCourse($user, $item);
    }

    /** Gestion générique : admin OU gestionnaire du cours parent. */
    public function manage(User $user, LessonItem $item): bool
    {
        return $this->canManageParentCourse($user, $item);
    }

    /**
     * Autorisation ownership-aware : remonte au cours parent
     * (item->lesson->chapter->course) puis applique rôle+ownership.
     * Chaîne incomplète (donnée orpheline) → seul l'admin passe.
     */
    private function canManageParentCourse(User $user, LessonItem $item): bool
    {
        if ($user->can('academy.manage')) {
            return true;
        }

        $course = $item->lesson?->chapter?->course;

        if (! $course instanceof Course) {
            return false;
        }

        return $course->hasRole($user, ['owner', 'instructor', 'editor']);
    }
}
