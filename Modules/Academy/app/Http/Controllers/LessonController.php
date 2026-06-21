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
use Modules\Academy\Models\Lesson;

class LessonController extends Controller
{
    /**
     * GET /academie/courses/{course:slug}/lessons/{lesson}
     *
     * Sécurité :
     * - 404 si le cours n'est pas publié/public
     * - 404 si la leçon n'appartient pas au cours (via le chapitre)
     * - Le contenu vidéo n'est injecté dans la vue QUE si l'accès est autorisé
     */
    public function show(Request $request, Course $course, Lesson $lesson): \Illuminate\View\View
    {
        // 1. Prévisualisation « en tant qu'étudiant » : réservée à un gérant de CE cours
        //    (can('update') = admin OU owner/instructor/editor). Le query param ?preview=1
        //    seul ne suffit JAMAIS : un non-gérant échoue le can('update') et retombe sur
        //    le 404 public. On ne fait jamais confiance au seul paramètre du navigateur.
        $isPreview = $request->boolean('preview')
            && auth()->check()
            && auth()->user()->can('update', $course);

        if (! $isPreview && ($course->status !== 'published' || $course->visibility !== 'public')) {
            abort(404);
        }

        // 2. Charger l'arborescence du cours pour la navigation latérale
        $course->load([
            'chapters'         => fn ($q) => $q->orderBy('position'),
            'chapters.lessons' => fn ($q) => $q->orderBy('position'),
        ]);

        // 3. Vérifier que la leçon appartient bien à ce cours
        $belongsToCourse = $course->chapters->flatMap(fn ($ch) => $ch->lessons)->contains('id', $lesson->id);
        if (! $belongsToCourse) {
            abort(404);
        }

        // 4. Charger les items de la leçon (triés)
        $lesson->load(['lessonItems' => fn ($q) => $q->orderBy('position')]);

        // 5. Vérifier l'accès : inscrit actif OU cours gratuit (accès contrôlé par item)
        $isEnrolled = false;
        if (auth()->check()) {
            $isEnrolled = Enrollment::where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->exists();
        }

        // En prévisualisation, le gérant voit le contenu comme un étudiant inscrit
        // (aucune inscription n'est créée : c'est purement un affichage).
        if ($isPreview) {
            $isEnrolled = true;
        }

        // 6. Navigation préc/suiv
        $allLessons = $course->chapters->flatMap(fn ($ch) => $ch->lessons);
        $currentIndex = $allLessons->search(fn ($l) => $l->id === $lesson->id);
        $prevLesson   = $currentIndex > 0 ? $allLessons->get($currentIndex - 1) : null;
        $nextLesson   = $currentIndex < $allLessons->count() - 1 ? $allLessons->get($currentIndex + 1) : null;

        // 7. Chapitre courant (pour la sidebar)
        $currentChapter = $course->chapters->first(
            fn ($ch) => $ch->lessons->contains('id', $lesson->id)
        );

        return view('academy::public.lesson', compact(
            'course',
            'lesson',
            'isEnrolled',
            'prevLesson',
            'nextLesson',
            'currentChapter',
            'isPreview',
        ));
    }
}
