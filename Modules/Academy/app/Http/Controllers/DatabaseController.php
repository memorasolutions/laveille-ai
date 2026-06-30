<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F20 - BASE DE DONNÉES collaborative : actions d'un item de leçon « database » (fiches
 * soumises selon un schéma défini par le gérant ; type Moodle « Database ») : ajouter une
 * fiche, éditer / supprimer SA fiche, approuver / supprimer une fiche (modération gérant).
 *
 * SÉCURITÉ (même patron que Wiki/Forum/Quiz/Choice/Feedback) :
 *  - auth requis (route `auth`) + throttle (route `throttle:20,1`) ;
 *  - item RE-RÉSOLU serveur et rattaché à la leçon/cours (anti-IDOR), type forcé « database » ;
 *  - écrire EXIGE l'inscription active OU le rôle gérant (manageEnrollments). Un étudiant
 *    non inscrit / anonyme est rejeté (403) ;
 *  - honeypot `hp_url` (anti-spam MAISON) : rempli => REJET SILENCIEUX (aucune écriture) ;
 *  - ajout d'une fiche gaté par allow_student_add : si false, seul un gérant ajoute ;
 *  - validation des valeurs PAR TYPE dérivée du schéma (DatabaseService::entryRules) :
 *    number = numérique, url = url valide, select = dans les options, required respecté,
 *    bornes de longueur ; le texte est stocké brut (rendu strippé à l'affichage, anti-XSS) ;
 *  - un inscrit n'édite / ne supprime QUE SA PROPRE fiche (anti-IDOR sur user_id) ; un gérant
 *    modère n'importe quelle fiche (approuver / supprimer en soft-delete) ;
 *  - le SCHÉMA (les champs) NE se gère PAS ici : il est défini dans l'éditeur de cours
 *    (CourseEditor, autorisation manageStructure). Ce contrôleur ne touche qu'aux fiches.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\DatabaseEntry;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\AccessRestrictionService;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\DatabaseService;

class DatabaseController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────────
    // INSCRIT (et gérant) : ajouter / éditer / supprimer SA fiche
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.database.entries.create */
    public function addEntry(Request $request, Course $course, Lesson $lesson, int $itemId): RedirectResponse
    {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'La fiche a été ajoutée.');
        }

        // allow_student_add=false : seul un gérant peut ajouter une fiche.
        if (! $manager && ! DatabaseService::allowsStudentAdd($item)) {
            abort(403);
        }

        $fields = DatabaseService::fields($item);

        // Validation des valeurs PAR TYPE dérivée du schéma (number/url/select/required/bornes).
        $request->validate(DatabaseService::entryRules($fields));

        // Modération : une fiche d'étudiant naît « en attente » si require_approval ; un
        // gérant publie directement (parité Moodle).
        $approved = $manager || ! DatabaseService::requiresApproval($item);

        // Transaction : la fiche ET ses valeurs forment un tout. Sinon, un échec de
        // storeValues laisserait une fiche approuvée sans valeur, visible des inscrits.
        DB::transaction(function () use ($item, $approved, $fields, $request): void {
            $entry = DatabaseEntry::create([
                'lesson_item_id' => $item->id,
                'user_id'        => Auth::id(),
                'is_approved'    => $approved,
            ]);

            DatabaseService::storeValues($entry, $fields, (array) $request->input('values', []));
        });

        $this->maybeComplete($item, $manager);

        return $this->backToItem($course, $lesson, $item)->with(
            'success',
            $approved ? 'La fiche a été ajoutée.' : 'La fiche a été soumise ; elle sera visible après approbation.'
        );
    }

    /** POST academy.database.entries.update - éditer SA fiche (ou n'importe laquelle si gérant). */
    public function updateEntry(Request $request, Course $course, Lesson $lesson, int $itemId, int $entryId): RedirectResponse
    {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'La fiche a été mise à jour.');
        }

        $entry = $this->resolveEntry($item, $entryId);

        // Anti-IDOR : un inscrit ne touche QUE SA fiche ; un gérant toutes.
        if (! $manager && (int) ($entry->user_id ?? 0) !== (int) Auth::id()) {
            abort(403);
        }

        $fields = DatabaseService::fields($item);
        $request->validate(DatabaseService::entryRules($fields));

        // Transaction : mise à jour atomique des valeurs (anti état partiel visible).
        DB::transaction(function () use ($entry, $fields, $request): void {
            DatabaseService::storeValues($entry, $fields, (array) $request->input('values', []));
        });

        return $this->backToItem($course, $lesson, $item)->with('success', 'La fiche a été mise à jour.');
    }

    /** POST academy.database.entries.delete - supprimer SA fiche (ou n'importe laquelle si gérant). */
    public function deleteEntry(Course $course, Lesson $lesson, int $itemId, int $entryId): RedirectResponse
    {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        $entry = $this->resolveEntry($item, $entryId);

        if (! $manager && (int) ($entry->user_id ?? 0) !== (int) Auth::id()) {
            abort(403);
        }

        $entry->delete(); // soft-delete

        return $this->backToItem($course, $lesson, $item)->with('success', 'La fiche a été supprimée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // MODÉRATION (gérant uniquement : manageEnrollments)
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.database.entries.approve - approuve une fiche en attente. */
    public function approveEntry(Course $course, Lesson $lesson, int $itemId, int $entryId): RedirectResponse
    {
        $item = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);
        $entry = $this->resolveEntry($item, $entryId);

        $entry->is_approved = true;
        $entry->save();

        return $this->backToItem($course, $lesson, $item)->with('success', 'La fiche a été approuvée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Aides privées (anti-IDOR, gardes)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Re-résout l'item serveur et vérifie auth + appartenance leçon -> cours (anti-IDOR)
     * + type « database ». Abort 403 / 404 sinon.
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

        if ($item->type !== 'database') {
            abort(404);
        }

        return $item;
    }

    /** Re-résout une entrée scopée à l'item (anti-IDOR). Abort 404 sinon. */
    private function resolveEntry(LessonItem $item, int $entryId): DatabaseEntry
    {
        $entry = DatabaseEntry::findOrFail($entryId);
        if ((int) $entry->lesson_item_id !== $item->id) {
            abort(404);
        }

        return $entry;
    }

    /**
     * Garde commune des contributions : inscription active OU gérant + restrictions
     * d'accès de l'item (un item verrouillé par une restriction bloque l'étudiant).
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

    /** Modération : exige manageEnrollments (abort 403 sinon). */
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
        return trim((string) $request->input(DatabaseService::HONEYPOT, '')) !== '';
    }

    /**
     * Achèvement « add » (défaut base de données) : un INSCRIT complète l'item en
     * ajoutant une fiche (même en attente d'approbation : la participation suffit,
     * comme le forum). Jamais pour un gérant. markComplete est idempotent.
     */
    private function maybeComplete(LessonItem $item, bool $manager): void
    {
        if (! $manager && ActivityCompletionService::criterionFor($item) === 'add') {
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
