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

        // 5d. V2-c - ACHÈVEMENT « view » : auto-marquer les items dont le critère est
        //     « view » dès que la leçon est consultée par un INSCRIT RÉEL avec accès.
        //     Gardes (anti-contournement) : jamais en prévisualisation, inscription
        //     active réelle ($enrollment), prérequis satisfaits et leçon non verrouillée
        //     par le drip. Idempotent (une seule complétion par user/item). Aucune
        //     complétion pour un non-inscrit, un gérant en preview ou un contenu gaté.
        if (! $isPreview
            && auth()->check()
            && $enrollment !== null
            && ! $isDripLocked
            && $prerequisitesUnmet->isEmpty()
            && class_exists(\Modules\Academy\Services\ActivityCompletionService::class)) {
            \Modules\Academy\Services\ActivityCompletionService::autoMarkViewItems(auth()->user(), $lesson);
        }

        // 5e. V5-d - RESTRICTIONS D'ACCES par item (calcul SERVEUR, 100 %). Pour chaque
        //     item de la leçon, on évalue les conditions access_restrictions du payload
        //     et on construit une map [item_id => {allowed, hidden, reasons}].
        //     - En prévisualisation : le gérant voit tout (aucune restriction).
        //     - Non inscrit / drip verrouillé : inutile de calculer (le gating $hasAccess
        //       coupe déjà l'accès ; la map reste vide = tout ouvert par défaut).
        //     Rétrocompat stricte : un item sans la clé retourne always allowed=true.
        $itemRestrictions = [];
        if (! $isPreview && $isEnrolled && auth()->check()
            && class_exists(\Modules\Academy\Services\AccessRestrictionService::class)) {
            $course->loadMissing(['chapters.lessons.lessonItems']);
            $validItemIds = \Modules\Academy\Services\AccessRestrictionService::courseItemIds($course);
            foreach ($lesson->lessonItems as $lessonItem) {
                $itemRestrictions[$lessonItem->id] = \Modules\Academy\Services\AccessRestrictionService::evaluate(
                    auth()->user(),
                    $lessonItem,
                    $course,
                );
                // Correction anti-IDOR : on ne passe que les IDs du cours courant
                // (courseItemIds() a déjà été calculé avec la relation chargée).
            }
        }

        // 5f. C3 (anti N+1) - Précharge en UNE requête les votes « choice » de
        //     l'utilisateur courant pour TOUS les items de la leçon. Le lecteur consulte
        //     ensuite cette map au lieu de requêter par item. Vide si anonyme ou en
        //     prévisualisation (le gérant ne vote pas).
        $choiceVotes = collect();
        if (! $isPreview && auth()->check()) {
            $choiceItemIds = $lesson->lessonItems
                ->where('type', 'choice')
                ->pluck('id');

            if ($choiceItemIds->isNotEmpty()) {
                $choiceVotes = \Modules\Academy\Services\ChoiceService::preloadUserVotes(
                    $choiceItemIds,
                    auth()->user()
                );
            }
        }

        // 5g. C2 (requête hors vue) - Précharge en UNE requête les réponses NOMMÉES de
        //     l'utilisateur courant pour TOUS les items « feedback » de la leçon, pour le
        //     pré-remplissage du formulaire (réponse modifiable). La vue ne requête plus.
        //     Vide si anonyme ou en prévisualisation (le gérant ne répond pas) ; les
        //     réponses anonymes (user_id NULL) sont naturellement exclues.
        $feedbackResponses = collect();
        if (! $isPreview && auth()->check()) {
            $feedbackItemIds = $lesson->lessonItems
                ->where('type', 'feedback')
                ->pluck('id');

            if ($feedbackItemIds->isNotEmpty()) {
                $feedbackResponses = \Modules\Academy\Services\FeedbackService::preloadUserResponses(
                    $feedbackItemIds,
                    auth()->user()
                );
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

        // 7c. M6 - Complétion de cours calculée serveur (CourseCompletionService, source
        // unique). La vue affiche le certificat uniquement sur la base de cette variable
        // booléenne ; plus de >= 100 codé en dur. Gaté class_exists ; défaut historique :
        // percent persisté >= 100 si le service est absent.
        $courseCompleted = false;
        if (auth()->check() && $isEnrolled && ! $isPreview) {
            if (class_exists(\Modules\Academy\Services\CourseCompletionService::class)) {
                try {
                    $courseCompleted = (new \Modules\Academy\Services\CourseCompletionService())
                        ->isComplete(auth()->user(), $course);
                } catch (\Throwable) {}
            } else {
                $pct = \Modules\Academy\Models\Progress::where('user_id', auth()->id())
                    ->where('course_id', $course->id)
                    ->value('percent');
                $courseCompleted = (int) ($pct ?? 0) >= 100;
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
            'choiceVotes',
            'feedbackResponses',
            'courseCompleted',
            'itemRestrictions',
        ));
    }
}
