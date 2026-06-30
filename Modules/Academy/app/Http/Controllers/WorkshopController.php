<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : actions d'un item de leçon « workshop » (évaluation par les
 * pairs ; type Moodle « Workshop »). Étudiant : REMETTRE son travail (phase submission),
 * ÉVALUER les travaux qui lui sont attribués (phase assessment). Gérant : changer la PHASE,
 * ATTRIBUER les évaluations (allocation déterministe).
 *
 * SÉCURITÉ (même patron que Database/Wiki/Forum) :
 *  - auth requis (route `auth`) + throttle (route `throttle:20,1`) ;
 *  - item RE-RÉSOLU serveur et rattaché à la leçon/cours (anti-IDOR), type forcé « workshop » ;
 *  - écrire EXIGE l'inscription active OU le rôle gérant (manageEnrollments). Un étudiant
 *    non inscrit / anonyme est rejeté (403) ;
 *  - honeypot `hp_url` (anti-spam MAISON) : rempli => REJET SILENCIEUX (aucune écriture) ;
 *  - VALIDATION DE PHASE serveur : remettre hors phase submission => 403 ; évaluer hors phase
 *    assessment => 403 ; scores bornés 0..max_score du critère (au service) ;
 *  - ANTI-IDOR évaluation : un étudiant n'évalue QUE les travaux qui LUI sont attribués
 *    (assessor_id = lui) ; jamais le sien (garanti par l'allocation + re-vérifié ici) ;
 *  - ANTI auto-évaluation : impossible de noter son propre travail ;
 *  - la GRILLE (critères) et les PHASES « gérant » NE se définissent PAS toutes ici : la
 *    grille vit dans l'éditeur de cours (CourseEditor, manageStructure) ; le changement de
 *    phase et l'allocation sont gatés manageEnrollments dans ce contrôleur.
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
use Modules\Academy\Models\WorkshopAssessment;
use Modules\Academy\Services\AccessRestrictionService;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\WorkshopService;

class WorkshopController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────────
    // ÉTUDIANT (et gérant) : remettre son travail / évaluer un pair
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.workshop.submit - remettre / mettre à jour SON travail (phase submission). */
    public function submitWork(Request $request, Course $course, Lesson $lesson, int $itemId): RedirectResponse
    {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'Votre travail a été remis.');
        }

        // VALIDATION DE PHASE serveur : on ne remet QUE pendant la phase « submission ».
        if (WorkshopService::phase($item) !== 'submission') {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:'.WorkshopService::TITLE_MAX],
            'body'  => ['nullable', 'string', 'max:'.WorkshopService::BODY_MAX],
        ]);

        WorkshopService::upsertSubmission(
            $item,
            (int) Auth::id(),
            (string) $data['title'],
            $data['body'] ?? null,
        );

        $this->maybeComplete($item, $manager);

        return $this->backToItem($course, $lesson, $item)->with('success', 'Votre travail a été remis.');
    }

    /** POST academy.workshop.assess - évaluer un travail attribué (phase assessment). */
    public function assess(Request $request, Course $course, Lesson $lesson, int $itemId, int $assessmentId): RedirectResponse
    {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'Votre évaluation a été enregistrée.');
        }

        // VALIDATION DE PHASE serveur : on n'évalue QUE pendant la phase « assessment ».
        if (WorkshopService::phase($item) !== 'assessment') {
            abort(403);
        }

        $assessment = $this->resolveAssessment($item, $assessmentId);

        // ANTI-IDOR : l'évaluation doit AVOIR ÉTÉ ATTRIBUÉE à l'utilisateur courant.
        if ((int) ($assessment->assessor_id ?? 0) !== (int) Auth::id()) {
            abort(403);
        }

        // ANTI auto-évaluation (défensif : l'allocation l'empêche déjà) : on ne note
        // jamais son PROPRE travail.
        $assessment->loadMissing('submission:id,user_id,lesson_item_id');
        if ((int) ($assessment->submission->user_id ?? 0) === (int) Auth::id()) {
            abort(403);
        }

        $criteria = WorkshopService::criteria($item);

        // Scores bornés 0..max_score PAR CRITÈRE (règles dérivées de la grille).
        $rules = ['feedback' => ['nullable', 'string', 'max:'.WorkshopService::FEEDBACK_MAX]];
        foreach ($criteria as $criterion) {
            $rules['scores.'.$criterion->id] = ['nullable', 'integer', 'min:0', 'max:'.(int) $criterion->max_score];
        }
        $request->validate($rules);

        WorkshopService::recordAssessment(
            $assessment,
            $criteria,
            (array) $request->input('scores', []),
            $request->input('feedback'),
        );

        return $this->backToItem($course, $lesson, $item)->with('success', 'Votre évaluation a été enregistrée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // GÉRANT (manageEnrollments) : changer la phase / attribuer les évaluations
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.workshop.phase - avancer / régler la phase (setup/submission/assessment/closed). */
    public function setPhase(Request $request, Course $course, Lesson $lesson, int $itemId): RedirectResponse
    {
        $item = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);

        $data = $request->validate([
            'phase' => ['required', 'string', \Illuminate\Validation\Rule::in(WorkshopService::PHASES)],
        ]);

        $payload          = is_array($item->payload) ? $item->payload : [];
        $payload['phase'] = $data['phase'];
        $item->payload    = $payload;
        $item->save();

        // À l'entrée en phase « assessment », on attribue automatiquement les évaluations
        // (idempotent : rejouer n'ajoute pas de doublon).
        $allocatedMsg = '';
        if ($data['phase'] === 'assessment') {
            $count = WorkshopService::allocate($item);
            $allocatedMsg = $count > 0 ? ' '.$count.' évaluation(s) attribuée(s).' : '';
        }

        return $this->backToItem($course, $lesson, $item)
            ->with('success', 'Phase mise à jour : '.$data['phase'].'.'.$allocatedMsg);
    }

    /** POST academy.workshop.allocate - (ré)attribuer les évaluations manuellement (gérant). */
    public function allocate(Course $course, Lesson $lesson, int $itemId): RedirectResponse
    {
        $item = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);

        $count = WorkshopService::allocate($item);

        return $this->backToItem($course, $lesson, $item)
            ->with('success', $count > 0
                ? $count.' évaluation(s) attribuée(s).'
                : 'Pas assez de travaux remis pour attribuer des évaluations.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Aides privées (anti-IDOR, gardes) - calquées sur DatabaseController
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Re-résout l'item serveur et vérifie auth + appartenance leçon -> cours (anti-IDOR)
     * + type « workshop ». Abort 403 / 404 sinon.
     */
    private function resolveItem(Course $course, Lesson $lesson, int $itemId): LessonItem
    {
        if (! Auth::check()) {
            abort(403);
        }

        $item = LessonItem::findOrFail($itemId);

        if ((int) $item->lesson_id !== $lesson->id) {
            abort(404);
        }

        $lesson->loadMissing('chapter');
        if ((int) $lesson->chapter->course_id !== $course->id) {
            abort(404);
        }

        if ($item->type !== 'workshop') {
            abort(404);
        }

        return $item;
    }

    /** Re-résout une évaluation scopée à l'item via son travail (anti-IDOR). Abort 404 sinon. */
    private function resolveAssessment(LessonItem $item, int $assessmentId): WorkshopAssessment
    {
        $assessment = WorkshopAssessment::with('submission:id,user_id,lesson_item_id')->findOrFail($assessmentId);

        if ((int) ($assessment->submission->lesson_item_id ?? 0) !== $item->id) {
            abort(404);
        }

        return $assessment;
    }

    /**
     * Garde commune des contributions : inscription active OU gérant + restrictions d'accès
     * de l'item (un item verrouillé par une restriction bloque l'étudiant).
     */
    private function ensureContributor(Course $course, LessonItem $item, bool $manager): void
    {
        if (! $manager && ! $this->isEnrolled($course)) {
            abort(403);
        }

        if (! $manager && class_exists(AccessRestrictionService::class)) {
            $restriction = AccessRestrictionService::evaluate(Auth::user(), $item, $course);
            if (! ($restriction['allowed'] ?? true)) {
                abort(403);
            }
        }
    }

    /** Gérant de CE cours (admin OU owner/instructor). */
    private function isManager(Course $course): bool
    {
        return Auth::check() && Auth::user()->can('manageEnrollments', $course);
    }

    /** Réservé au gérant (abort 403 sinon). */
    private function authorizeManager(Course $course): void
    {
        abort_unless($this->isManager($course), 403);
    }

    private function isEnrolled(Course $course): bool
    {
        return Enrollment::where('user_id', Auth::id())
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();
    }

    /** Honeypot rempli => spam (rejet silencieux). */
    private function isSpam(Request $request): bool
    {
        return trim((string) $request->input(WorkshopService::HONEYPOT, '')) !== '';
    }

    /**
     * Achèvement « submit_work » (défaut atelier) : un ÉTUDIANT complète l'item en
     * remettant son travail (phase « submission »). Jamais pour un gérant (aucune
     * progression). markComplete est idempotent.
     */
    private function maybeComplete(LessonItem $item, bool $manager): void
    {
        if (! $manager && ActivityCompletionService::criterionFor($item) === 'submit_work') {
            CompletionService::markComplete(Auth::user(), $item);
        }
    }

    private function backToItem(Course $course, Lesson $lesson, LessonItem $item): RedirectResponse
    {
        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }
}
