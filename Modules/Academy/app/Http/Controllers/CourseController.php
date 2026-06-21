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
        // Prévisualisation « en tant qu'étudiant » : autorisée UNIQUEMENT à un gérant
        // de CE cours (can('update') = admin OU owner/instructor/editor du cours). Le
        // seul query param ?preview=1 ne suffit JAMAIS : un non-gérant qui l'ajoute sur
        // un cours brouillon échoue le can('update') et retombe sur le 404 public.
        $isPreview = $request->boolean('preview')
            && auth()->check()
            && auth()->user()->can('update', $course);

        if (! $isPreview && ($course->status !== 'published' || $course->visibility !== 'public')) {
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

        // En prévisualisation, le gérant voit le contenu comme un étudiant inscrit
        // (sans pour autant créer d'inscription : c'est purement un affichage).
        if ($isPreview) {
            $isEnrolled = true;
        }

        $isFree = $course->access_type === 'free';

        return view('academy::public.show', compact('course', 'isEnrolled', 'isFree', 'enrollment', 'isPreview'));
    }
}
