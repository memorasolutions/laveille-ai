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
        $enrollment = null;
        if (auth()->check()) {
            $enrollment = Enrollment::where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->first();
            $isEnrolled = $enrollment !== null;
        }

        // En prévisualisation, le gérant voit le contenu comme un étudiant inscrit
        // (aucune inscription n'est créée : c'est purement un affichage).
        if ($isPreview) {
            $isEnrolled = true;
        }

        // 5b. C4 - Garde PRÉREQUIS (SERVEUR). Si l'utilisateur n'a pas complété tous
        //     les prérequis, on coupe l'accès comme un non-inscrit : le contenu
        //     verrouillé n'est JAMAIS injecté dans le DOM (gating $hasAccess existant).
        //     Jamais imposé en prévisualisation (le gérant voit tout).
        $prerequisitesUnmet = collect();
        if (! $isPreview && auth()->check()) {
            $prerequisitesUnmet = $course->prerequisitesUnmetFor(auth()->user());
            if ($prerequisitesUnmet->isNotEmpty()) {
                $isEnrolled = false;
            }
        }

        // 5c. C4 - Garde DRIP (SERVEUR). La leçon courante est-elle encore verrouillée
        //     par la libération progressive (enrolled_at + drip_days dans le futur) ?
        //     Calcul serveur ; jamais imposé en preview ni à un gérant. Si verrouillée,
        //     on coupe l'accès au contenu (gating $hasAccess) + on expose la date.
        $isDripLocked    = false;
        $dripAvailableAt = null;
        if (! $isPreview && $isEnrolled && $enrollment !== null) {
            if ($lesson->isDripLockedFor($enrollment->enrolled_at)) {
                $isDripLocked    = true;
                $dripAvailableAt = $lesson->dripAvailableAt($enrollment->enrolled_at);
                $isEnrolled      = false; // coupe l'accès au contenu de CETTE leçon
            }
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

        // 7b. C4 - Map des dates de disponibilité (drip) par leçon, pour le cadenas
        //     de la sidebar. Calcul SERVEUR à partir de enrolled_at. Vide si non inscrit
        //     ou en preview (le gérant ne voit aucun cadenas drip).
        $dripLockedLessonIds = [];
        if (! $isPreview && $enrollment !== null) {
            foreach ($allLessons as $navLesson) {
                if ($navLesson->isDripLockedFor($enrollment->enrolled_at)) {
                    $dripLockedLessonIds[$navLesson->id] = $navLesson->dripAvailableAt($enrollment->enrolled_at);
                }
            }
        }

        return view('academy::public.lesson', compact(
            'course',
            'lesson',
            'isEnrolled',
            'prevLesson',
            'nextLesson',
            'currentChapter',
            'isPreview',
            'prerequisitesUnmet',
            'isDripLocked',
            'dripAvailableAt',
            'dripLockedLessonIds',
        ));
    }
}
