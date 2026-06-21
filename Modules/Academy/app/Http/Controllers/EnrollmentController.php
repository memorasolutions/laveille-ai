<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academy\Exceptions\CourseNotFreeException;
use Modules\Academy\Exceptions\PrerequisitesNotMetException;
use Modules\Academy\Models\Course;
use Modules\Academy\Services\EnrollmentService;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        try {
            app(EnrollmentService::class)->enrollFree($user, $course);
        } catch (CourseNotFreeException|PrerequisitesNotMetException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('academy.index')
            ->with('success', "Inscription réussie au cours \"{$course->title}\".");
    }
}
