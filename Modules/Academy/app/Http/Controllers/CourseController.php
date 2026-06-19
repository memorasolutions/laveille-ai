<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;

class CourseController extends Controller
{
    public function show(Request $request, Course $course)
    {
        if ($course->status !== 'published' || $course->visibility !== 'public') {
            abort(404);
        }

        $course->load([
            'chapters'         => fn ($q) => $q->orderBy('position'),
            'chapters.lessons' => fn ($q) => $q->orderBy('position'),
            'courseRoles',
        ]);

        $isEnrolled = false;
        $enrollment = null;

        if (auth()->check()) {
            $enrollment = Enrollment::where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->first();

            $isEnrolled = $enrollment !== null;
        }

        $isFree = $course->access_type === 'free';

        return view('academy::public.show', compact('course', 'isEnrolled', 'isFree', 'enrollment'));
    }
}
