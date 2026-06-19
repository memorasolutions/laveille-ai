<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Policies;

use App\Models\User;
use Modules\Academy\Models\Lesson;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('academy.manage');
    }

    public function view(User $user, Lesson $lesson): bool
    {
        // La vérification d'enrollment est déléguée au controller ; ici on vérifie
        // seulement les droits de gestion (instructeur ou admin).
        return $user->can('academy.manage');
    }

    public function create(User $user, Lesson $lesson): bool
    {
        return $user->can('academy.lessons.manage');
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->can('academy.lessons.manage');
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->can('academy.lessons.manage');
    }

    public function manage(User $user, Lesson $lesson): bool
    {
        return $user->can('academy.lessons.manage');
    }
}
