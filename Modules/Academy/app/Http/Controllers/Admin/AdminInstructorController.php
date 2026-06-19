<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;

class AdminInstructorController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role'    => 'required|in:instructor,assistant,editor',
        ]);

        $exists = CourseRole::where('course_id', $course->id)
            ->where('user_id', $request->user_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['user_id' => 'Cet utilisateur a déjà un rôle dans ce cours.']);
        }

        CourseRole::create([
            'course_id' => $course->id,
            'user_id'   => (int) $request->user_id,
            'role'      => $request->role,
        ]);

        return back()->with('success', 'Rôle assigné avec succès.');
    }

    public function destroy(CourseRole $courseRole): RedirectResponse
    {
        if ($courseRole->role === 'owner') {
            return back()->with('error', 'Impossible de retirer le rôle owner.');
        }

        $courseRole->delete();

        return back()->with('success', 'Rôle retiré avec succès.');
    }
}
