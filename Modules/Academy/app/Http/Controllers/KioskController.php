<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Mode kiosque — consignation des incidents détectés côté client pendant une
 * tentative de quiz surveillée (sortie plein écran, changement d'onglet,
 * outils de développement suspectés, sortie volontaire). RIEN de ce contrôleur
 * ne touche à la notation du quiz (voir Services\QuizService::score, appelé
 * exclusivement depuis QuizController) : c'est un journal d'audit, jamais un
 * mécanisme de scoring ou d'invalidation automatique.
 *
 * ARCHITECTURE : la QuizAttempt n'existe qu'à la SOUMISSION FINALE du quiz
 * (QuizController::submitQuiz). Pendant la tentative EN COURS (round stocké en
 * session serveur par startQuiz), il n'y a donc pas encore d'attempt_id en
 * base. Les incidents sont empilés dans CETTE MÊME session serveur (clé
 * « academy.quiz.{itemId} », scopée par utilisateur via le cookie de session)
 * puis migrés vers academy_kiosk_violations par
 * KioskViolationService::flushSessionToAttempt() une fois la tentative créée.
 * ANTI-IDOR : la session appartient intrinsèquement à l'utilisateur
 * authentifié courant (aucun attempt_id n'est jamais accepté du client).
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academy\Http\Controllers\Concerns\AuthorizesAcademyAccess;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\KioskViolationService;

class KioskController extends Controller
{
    use AuthorizesAcademyAccess;

    /**
     * POST academy.quiz.kiosk-violation
     *
     * Consigne UN incident de mode kiosque pour la tentative EN COURS de
     * l'utilisateur authentifié sur cet item (round actif en session serveur).
     *
     * Sécurité (anti-IDOR) :
     *  - auth + inscription active vérifiées (authorizeAccess, trait partagé) ;
     *  - l'incident est empilé dans LA SESSION SERVEUR de l'utilisateur courant
     *    (jamais dans une table indexée par un identifiant fourni par le
     *    client) — un apprenant ne peut donc PHYSIQUEMENT pas viser la
     *    tentative d'un autre apprenant, la session étant strictement liée à
     *    son propre cookie authentifié ;
     *  - `type` contraint à la liste blanche KioskViolationService::TYPES ;
     *  - 404 si aucun round de quiz n'est actif en session pour cet item
     *    (rien à consigner : la tentative n'a pas commencé ou a expiré).
     *
     * Drapeau global : config('academy.kiosk_mode_enabled') désactivé (défaut)
     * → 404 direct, aucune écriture possible même si la route était appelée.
     */
    public function recordViolation(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): JsonResponse {
        if (! config('academy.kiosk_mode_enabled', false)) {
            abort(404);
        }

        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(KioskViolationService::TYPES)],
            'meta' => ['nullable', 'array'],
        ]);

        $sessionKey = "academy.quiz.{$item->id}";
        $quizData   = $request->session()->get($sessionKey);

        // Aucun round actif en session pour CET item : rien à consigner (la
        // tentative n'a pas démarré via startQuiz, ou a déjà expiré/été soumise).
        if (! is_array($quizData)) {
            abort(404);
        }

        $meta = is_array($validated['meta'] ?? null) ? $validated['meta'] : [];

        $quizData = app(KioskViolationService::class)->stageInSession($quizData, $validated['type'], $meta);
        $request->session()->put($sessionKey, $quizData);

        return response()->json(['recorded' => true]);
    }
}
