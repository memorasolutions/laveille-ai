<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Exceptions\CourseNotFreeException;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;

class EnrollmentService
{
    public function enrollFree(User $user, Course $course): Enrollment
    {
        if ($course->access_type !== 'free') {
            throw new CourseNotFreeException("Le cours [{$course->slug}] n'est pas gratuit.");
        }

        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['status' => 'active', 'source' => 'free', 'enrolled_at' => now()]
        );

        if ($enrollment->wasRecentlyCreated) {
            $this->logActivity($user, $course);
            $this->notifyQuestProgress($user);
        }

        return $enrollment;
    }

    private function logActivity(User $user, Course $course): void
    {
        // Défensif : spatie/laravel-activitylog doit être installé
        if (! class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            return;
        }

        try {
            activity('academy')
                ->performedOn($course)
                ->causedBy($user)
                ->withProperties(['source' => 'free'])
                ->log('academy.enrolled');
        } catch (\Throwable) {
            // Ne jamais bloquer l'inscription pour un log raté
        }
    }

    private function notifyQuestProgress(User $user): void
    {
        // Hook défensif vers Modules/Tools — no-op silencieux si le module est absent
        if (! class_exists(\Modules\Tools\Models\QuestProgress::class)) {
            return;
        }

        try {
            // Placeholder : logique future (incrémente badge, streak, etc.)
            // \Modules\Tools\Models\QuestProgress::where('user_email', $user->email)->...
        } catch (\Throwable) {
            // Silencieux
        }
    }
}
