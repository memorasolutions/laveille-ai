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
use Modules\Academy\Services\ProgressService;

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

        // Cours non publié : 404 sauf en prévisualisation par un gérant.
        if (! $isPreview && $course->status !== 'published') {
            abort(404);
        }

        // BUG-002 fix : visibilité par cours.
        //  - public    -> visible par tous (y compris les anonymes) : aucune restriction.
        //  - unlisted  -> visible par lien direct pour tous (pas de 404).
        //  - private   -> visible uniquement par un inscrit actif ou un staff/admin ; 404 sinon.
        if (! $isPreview && $course->visibility === 'private') {
            $canAccess = false;

            if (auth()->check()) {
                $canAccess = auth()->user()->can('academy.manage')
                    || $course->hasRole(auth()->user(), ['owner', 'instructor', 'editor', 'assistant'])
                    || Enrollment::where('user_id', auth()->id())
                        ->where('course_id', $course->id)
                        ->where('status', 'active')
                        ->exists();
            }

            if (! $canAccess) {
                abort(404);
            }
        }

        $course->load([
            'chapters'         => fn ($q) => $q->orderBy('position'),
            'chapters.lessons' => fn ($q) => $q->orderBy('position'),
            'courseRoles',
            'prerequisites',
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

        // C4 - Prérequis NON satisfaits (collection). Calcul SERVEUR. En preview, on
        // n'impose aucun prérequis (le gérant voit tout). Le panneau « Prérequis à
        // compléter d'abord » s'affiche à la place du bouton d'inscription.
        $prerequisitesUnmet = collect();
        if (! $isPreview && auth()->check()) {
            $prerequisitesUnmet = $course->prerequisitesUnmetFor(auth()->user());
        }

        // Bouton « Continuer le cours » (sidebar) : URL réelle de reprise, calculée
        // SERVEUR via ProgressService (DRY, source unique de la progression). Calculé
        // uniquement pour un inscrit (actif ou gérant en preview) : c'est la seule
        // situation où ce bouton s'affiche (cf. academy::public.show).
        $continueUrl = null;
        if ($isEnrolled && auth()->check()) {
            $continueUrl = ProgressService::continueUrlFor(auth()->user(), $course);
        }

        return view('academy::public.show', compact(
            'course',
            'isEnrolled',
            'isFree',
            'enrollment',
            'isPreview',
            'prerequisitesUnmet',
            'continueUrl'
        ));
    }
}
