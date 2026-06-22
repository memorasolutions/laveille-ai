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
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\CompletionService;

class CompletionController extends Controller
{
    /**
     * POST academy.lessons.complete
     *
     * Bouton « Marquer comme terminé » pour les items video et doc.
     * Auth + inscription vérifiés.
     * Idempotent via CompletionService::markComplete.
     */
    public function store(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        if (! Auth::check()) {
            abort(403);
        }

        $item = LessonItem::findOrFail($itemId);

        // Valider appartenance item → lesson → course
        if ((int) $item->lesson_id !== $lesson->id) {
            abort(404);
        }

        $lesson->loadMissing('chapter');
        if ((int) $lesson->chapter->course_id !== $course->id) {
            abort(404);
        }

        // Inscription active obligatoire
        $enrollment = Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->first();

        if ($enrollment === null) {
            abort(403);
        }

        // C4 - Garde SERVEUR : on ne peut pas valider une leçon dont les prérequis du
        // cours ne sont pas satisfaits, ni une leçon encore verrouillée par le drip
        // (enrolled_at + drip_days dans le futur). Empêche le contournement par POST.
        if (! $course->prerequisitesMetFor(Auth::user())) {
            abort(403);
        }
        if ($lesson->isDripLockedFor($enrollment->enrolled_at)) {
            abort(403);
        }

        // Les quiz sont complétés via QuizController (soumission du formulaire)
        if ($item->type === 'quiz') {
            return back()->with('error', 'Les quiz doivent être complétés via le formulaire de quiz.');
        }

        // V2-c : le bouton manuel ne complète QUE les items dont le critère est « manual »
        // (défaut historique vidéo/document). Pour un item « view », l'achèvement est
        // automatique à la consultation (LessonController) : on rejette le POST manuel
        // (défensif ; le bouton n'est de toute façon pas affiché pour ces items).
        if (ActivityCompletionService::criterionFor($item) !== 'manual') {
            return back()->with('error', "L'achèvement de cet élément est automatique.");
        }

        CompletionService::markComplete(Auth::user(), $item);

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}")
            ->with('success', 'Leçon marquée comme terminée.');
    }
}
