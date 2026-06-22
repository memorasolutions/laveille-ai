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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\QuestionBankService;
use Modules\Academy\Services\QuizService;

class QuizController extends Controller
{
    /**
     * POST academy.quiz.start
     *
     * Démarre (ou redémarre) une session de quiz pour un item donné.
     * - Auth + inscription vérifiés
     * - Respecte attempts_allowed
     * - Stocke le round en session côté serveur (les questions ne sont jamais exposées en GET)
     */
    public function startQuiz(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        if ($item->type !== 'quiz') {
            abort(404);
        }

        $user = Auth::user();

        // Vérifier le nombre de tentatives
        $attemptsAllowed = isset($item->payload['attempts_allowed'])
            ? (int) $item->payload['attempts_allowed']
            : null;

        if ($attemptsAllowed !== null) {
            // V1-b : la limite s'appuie désormais sur l'HISTORIQUE RÉEL des tentatives
            // (1 ligne QuizAttempt par soumission), et non plus sur le comptage des
            // Completion (upsertées/idempotentes sur (user_id, lesson_item_id), donc
            // approximatif). Comportement identique pour un nouvel item (0 tentative).
            $existingAttempts = QuizAttempt::attemptCount($user->id, $item->id);

            if ($existingAttempts >= $attemptsAllowed) {
                return back()->with('error', 'Nombre de tentatives maximum atteint.');
            }
        }

        // Construire le round.
        // QB2 : si l'item est lié à une catégorie de banque (payload['question_bank']),
        // on tire N questions de CETTE catégorie (+ sous-catégories). La catégorie est
        // RE-RÉSOLUE serveur (findOrFail) ; un round vide (catégorie supprimée/vidée)
        // déclenche le REPLI sur qt_bank_key, puis « Quiz indisponible » si toujours vide.
        // Sans lien de banque → comportement existant qt_bank_key INCHANGÉ.
        $round    = [];
        $bankLink = $item->payload['question_bank'] ?? null;

        if (is_array($bankLink) && ! empty($bankLink['category_id'])) {
            $category = QuestionCategory::find((int) $bankLink['category_id']);

            if ($category !== null) {
                $drawCount = max(1, min(50, (int) ($bankLink['draw_count'] ?? 5)));
                // QB3 : inclure les sous-catégories par défaut (parité Moodle). Si la
                // clé est absente (item QB2 déjà lié), on considère true (rétrocompat).
                $includeSub = (bool) ($bankLink['include_subcategories'] ?? true);
                $round      = QuestionBankService::drawFromCategory($category, $drawCount, $includeSub);
            }
        }

        // Repli (pas de lien de banque OU round vide) : clé QT existante (inchangé).
        if (empty($round)) {
            $bankKey = (string) ($item->payload['qt_bank_key'] ?? 'qt-questions');
            $round   = QuizService::buildRound($bankKey);
        }

        if (empty($round)) {
            return back()->with('error', 'Quiz indisponible pour le moment.');
        }

        // Stocker le round en session serveur (les questions restent côté serveur)
        $request->session()->put("academy.quiz.{$item->id}", [
            'questions'  => $round,
            'started_at' => now()->toIso8601String(),
        ]);

        // Marquer l'item comme démarré (idempotent)
        CompletionService::markStarted($user, $item);

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }

    /**
     * POST academy.quiz.submit
     *
     * Soumet les réponses du quiz actif en session.
     * - Si score >= passing_score → marque l'item 'completed'
     * - Flash le résultat pour affichage immédiat
     */
    public function submitQuiz(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        $user       = Auth::user();
        $sessionKey = "academy.quiz.{$item->id}";
        $quizData   = $request->session()->get($sessionKey);

        if (! $quizData) {
            return back()->with('error', 'Session de quiz expirée. Recommencez le quiz.');
        }

        $questions   = $quizData['questions'] ?? [];
        $answers     = $request->input('answers', []);
        $scoreResult = QuizService::score($questions, $answers);

        $passingScore = isset($item->payload['passing_score'])
            ? (int) $item->payload['passing_score']
            : 60;

        $passed = $scoreResult['percent'] >= $passingScore;

        // V1-b : horodatage de début du round (posé par startQuiz en session).
        $startedAt = null;
        if (! empty($quizData['started_at'])) {
            try {
                $startedAt = \Illuminate\Support\Carbon::parse($quizData['started_at']);
            } catch (\Throwable) {
                $startedAt = null;
            }
        }

        // Supprimer la session (une tentative = une soumission)
        $request->session()->forget($sessionKey);

        // V1-b : enregistrer l'historique de la tentative ET marquer la complétion
        // (si réussi) dans une SEULE transaction. La complétion reste INCHANGÉE
        // (toujours appelée si réussi) ; la table d'historique démarre vide
        // (rétrocompat des Completion existantes).
        DB::transaction(function () use ($user, $item, $scoreResult, $passed, $answers, $questions, $startedAt): void {
            QuizAttempt::create([
                'user_id'            => $user->id,
                'lesson_item_id'     => $item->id,
                'course_id'          => $this->resolveCourseId($item),
                'score'              => $scoreResult['score'],
                'max_score'          => $scoreResult['total'],
                'percent'            => $scoreResult['percent'],
                'passed'             => $passed,
                'answers'            => $answers,
                'questions_snapshot' => $questions,
                'started_at'         => $startedAt,
                'submitted_at'       => now(),
            ]);

            if ($passed) {
                CompletionService::markComplete($user, $item, $scoreResult['score']);
            }
        });

        // Flasher le résultat (item_id inclus pour affichage ciblé en vue)
        $request->session()->flash('academy.quiz_result', [
            'item_id' => $item->id,
            'passed'  => $passed,
            'percent' => $scoreResult['percent'],
            'correct' => $scoreResult['correct'],
            'total'   => $scoreResult['total'],
        ]);

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }

    /**
     * Vérifie que l'utilisateur est authentifié, inscrit, et que l'item appartient à la leçon
     * qui appartient au cours. Abort 403/404 sinon.
     */
    private function authorizeAccess(Course $course, Lesson $lesson, LessonItem $item): void
    {
        if (! Auth::check()) {
            abort(403);
        }

        // Item appartient à la leçon
        if ((int) $item->lesson_id !== $lesson->id) {
            abort(404);
        }

        // Leçon appartient au cours (via chapter)
        $lesson->loadMissing('chapter');
        if ((int) $lesson->chapter->course_id !== $course->id) {
            abort(404);
        }

        // Inscription active
        $isEnrolled = Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        if (! $isEnrolled) {
            abort(403);
        }
    }

    /**
     * V1-b : résout l'ID du cours d'un item (via item→lesson→chapter), dénormalisé
     * dans academy_quiz_attempts pour scoper l'analytics. Retombe sur 0 si introuvable.
     */
    private function resolveCourseId(LessonItem $item): int
    {
        try {
            $item->loadMissing('lesson.chapter');

            return (int) ($item->lesson->chapter->course_id ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}
