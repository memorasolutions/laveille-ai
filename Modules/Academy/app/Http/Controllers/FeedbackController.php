<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK - enregistrement de la réponse à un item de leçon « feedback »
 * (questionnaire multi-questions NON noté, optionnellement anonyme ; type Moodle
 * « Feedback »).
 *
 * SÉCURITÉ (même patron que QuizController / ChoiceController) : auth + inscription
 * active vérifiées (trait), item RE-RÉSOLU serveur et rattaché à la leçon/cours
 * (anti-IDOR), réponses VALIDÉES/BORNÉES côté serveur contre les questions du payload
 * (le client ne décide jamais des bornes). Sondage NOMMÉ = une réponse par étudiant
 * (upsert scopé user+item, modifiable). Sondage ANONYME = user_id NULL (aucune
 * identité) + drapeau de session pour borner le re-spam de l'étudiant courant.
 * Répondre complète l'item quand le critère effectif est « submit » (défaut feedback).
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Academy\Http\Controllers\Concerns\AuthorizesAcademyAccess;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\FeedbackResponse;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\FeedbackService;

class FeedbackController extends Controller
{
    use AuthorizesAcademyAccess;

    /**
     * POST academy.feedback.submit
     */
    public function submit(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        if ($item->type !== 'feedback') {
            abort(404);
        }

        $user      = Auth::user();
        $questions = FeedbackService::questions($item);

        if ($questions === []) {
            return back()->with('error', 'Ce sondage n\'est pas disponible pour le moment.');
        }

        $anonymous = FeedbackService::isAnonymous($item);

        // Anti re-spam ANONYME : aucune identité n'est stockée, on borne par session
        // (l'étudiant courant ne peut pas ré-empiler des réponses dans la même session).
        if ($anonymous && FeedbackService::hasResponded($item, $user)) {
            return redirect()
                ->route('academy.lessons.show', [$course, $lesson])
                ->withFragment("item-{$item->id}")
                ->with('info', 'Vous avez déjà répondu à ce sondage.');
        }

        // Validation/bornage SERVEUR contre les questions du payload (jamais le client).
        $result = FeedbackService::validateAndCollect(
            $item,
            (array) $request->input('answers', [])
        );

        if ($result['errors'] !== []) {
            return back()
                ->withFragment("item-{$item->id}")
                ->with('error', $result['errors'][0]);
        }

        if ($anonymous) {
            // Réponse ANONYME : aucun lien vers l'auteur (user_id NULL).
            FeedbackResponse::create([
                'lesson_item_id' => $item->id,
                'user_id'        => null,
                'answers'        => $result['answers'],
            ]);
            FeedbackService::markAnsweredInSession($item);
        } else {
            // Réponse NOMMÉE : UNE par (item, étudiant) ; le ré-envoi MET À JOUR la même
            // ligne (contrainte UNIQUE en base), jamais de doublon. withTrashed : si une
            // réponse a été soft-supprimée (C3), on la restaure + met à jour plutôt que
            // de violer l'UNIQUE(item, user) par une insertion.
            $response = FeedbackResponse::withTrashed()
                ->firstOrNew(['lesson_item_id' => $item->id, 'user_id' => $user->id]);
            $response->answers = $result['answers'];
            if ($response->trashed()) {
                $response->deleted_at = null;
            }
            $response->save();
        }

        // C1 - PARTICIPATION (nommé ET anonyme) : trace le FAIT d'avoir répondu sans
        // lier au contenu, pour borner le re-spam anonyme même après reconnexion.
        FeedbackService::recordParticipation($item, $user);

        // Achèvement : répondre complète l'item quand le critère effectif est « submit »
        // (défaut d'un feedback). markComplete est idempotent.
        if (ActivityCompletionService::criterionFor($item) === 'submit') {
            CompletionService::markComplete($user, $item);
        }

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}")
            ->with('success', 'Merci, votre réponse a été enregistrée.');
    }
}
