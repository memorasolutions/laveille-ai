<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Policies;

use App\Models\User;
use Modules\Academy\Models\Course;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('academy.view');
    }

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

    public function create(User $user): bool
    {
        return $user->can('academy.courses.create');
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->can('academy.courses.update')) {
            return true;
        }

        return $course->userCan($user, 'manage');
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->can('academy.courses.delete');
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->can('academy.courses.publish');
    }

    public function enroll(User $user, Course $course): bool
    {
        if ($user->can('academy.enroll')) {
            return true;
        }

        return $course->access_type === 'free' && $course->status === 'published';
    }
}
