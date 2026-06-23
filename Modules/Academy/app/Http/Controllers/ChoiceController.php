<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * CHOICE - enregistrement du vote à un item de leçon « choice » (sondage non noté).
 *
 * SÉCURITÉ (même patron que QuizController) : auth + inscription active vérifiées,
 * item RE-RÉSOLU serveur et rattaché à la leçon/cours (anti-IDOR), choix BORNÉS aux
 * options du payload (le client ne peut jamais voter hors options), UN vote par
 * étudiant (upsert scopé user+item, modifiable). Voter complète l'item (V2-c) quand
 * le critère effectif est « vote » (défaut d'un choice).
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Academy\Http\Controllers\Concerns\AuthorizesAcademyAccess;
use Modules\Academy\Models\ChoiceResponse;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\ChoiceService;
use Modules\Academy\Services\CompletionService;

class ChoiceController extends Controller
{
    use AuthorizesAcademyAccess;

    /**
     * POST academy.choice.vote
     */
    public function vote(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);
        $this->authorizeAccess($course, $lesson, $item);

        if ($item->type !== 'choice') {
            abort(404);
        }

        $user    = Auth::user();
        $options = ChoiceService::options($item);

        if (count($options) < 2) {
            return back()->with('error', 'Ce sondage n\'est pas disponible pour le moment.');
        }

        // Indices valides = 0..count-1. On normalise la soumission en tableau d'entiers
        // puis on BORNE strictement aux options (anti-forge : un index hors options est
        // rejeté). Le client ne décide jamais des options : elles viennent du payload.
        $allowMultiple = ChoiceService::allowsMultiple($item);
        $submitted     = $request->input('choices', $request->input('choice'));

        $choices = [];
        foreach ((array) $submitted as $value) {
            if (is_numeric($value)) {
                $idx = (int) $value;
                if ($idx >= 0 && $idx < count($options)) {
                    $choices[] = $idx;
                }
            }
        }

        $choices = array_values(array_unique($choices));

        // Au moins un choix valide est requis.
        if ($choices === []) {
            return back()->with('error', 'Veuillez sélectionner une réponse.');
        }

        // Sondage à choix unique : on ne conserve qu'un seul choix (le premier valide).
        if (! $allowMultiple) {
            $choices = [$choices[0]];
        }

        // UN vote par (item, étudiant) : upsert scopé. Le re-vote MET À JOUR la même
        // ligne (contrainte UNIQUE en base), il ne duplique jamais.
        ChoiceResponse::updateOrCreate(
            ['lesson_item_id' => $item->id, 'user_id' => $user->id],
            ['choices' => $choices]
        );

        // V2-c : voter complète l'item quand le critère effectif est « vote » (défaut
        // d'un choice). Un gérant ayant choisi « view » → complété à la consultation ;
        // « manual » → clic. markComplete est idempotent.
        if (ActivityCompletionService::criterionFor($item) === 'vote') {
            CompletionService::markComplete($user, $item);
        }

        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}")
            ->with('success', 'Votre vote a été enregistré.');
    }
}
