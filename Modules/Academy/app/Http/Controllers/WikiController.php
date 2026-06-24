<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F19 - WIKI : actions d'un item de leçon « wiki » (pages collaboratives + historique,
 * type Moodle « Wiki ») : créer une page, éditer une page (collaboratif), restaurer une
 * révision, et modérer (verrouiller / supprimer) pour un gérant.
 *
 * SÉCURITÉ (même patron que Forum/Quiz/Choice/Feedback) :
 *  - auth requis (route `auth`) + throttle (route `throttle:20,1`) ;
 *  - item RE-RÉSOLU serveur et rattaché à la leçon/cours (anti-IDOR), type forcé « wiki » ;
 *  - écrire EXIGE l'inscription active OU le rôle gérant (manageEnrollments). Un étudiant
 *    non inscrit / anonyme est rejeté (403) ;
 *  - honeypot `hp_url` (anti-spam MAISON) : rempli => REJET SILENCIEUX (aucune écriture) ;
 *  - bornes de longueur SERVEUR (titre <= 200, corps <= 50000) ;
 *  - édition COLLABORATIVE gatée par allow_student_edit : si false, seul un gérant édite ;
 *    une page is_locked n'est éditable que par un gérant ;
 *  - restauration : gérant OU auteur de la page (created_by), et toujours sous les mêmes
 *    règles d'édition (page non verrouillée, édition étudiante permise) ;
 *  - modération (verrouiller / supprimer) gatée manageEnrollments ; page RE-RÉSOLUE scopée
 *    à l'item (anti-IDOR) ; suppression = soft-delete ; la page d'accueil ne se supprime pas.
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
use Modules\Academy\Models\WikiPage;
use Modules\Academy\Models\WikiRevision;
use Modules\Academy\Services\AccessRestrictionService;
use Modules\Academy\Services\WikiService;

class WikiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────────
    // ÉTUDIANT (et gérant) : créer / éditer une page, restaurer une révision
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.wiki.pages.create */
    public function createPage(Request $request, Course $course, Lesson $lesson, int $itemId): RedirectResponse
    {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'La page a été créée.');
        }

        // allow_student_edit=false : seul un gérant peut créer/éditer des pages.
        if (! $manager && ! WikiService::allowsStudentEdit($item)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:'.WikiService::TITLE_MAX,
            'body'  => 'nullable|string|max:'.WikiService::BODY_MAX,
        ]);

        $page = WikiService::createPage($item, Auth::id(), $data['title'], (string) ($data['body'] ?? ''));

        return $this->backToPage($course, $lesson, $item, $page)->with('success', 'La page a été créée.');
    }

    /** POST academy.wiki.pages.update */
    public function editPage(Request $request, Course $course, Lesson $lesson, int $itemId, int $pageId): RedirectResponse
    {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'La page a été mise à jour.');
        }

        $page = $this->resolvePage($item, $pageId);

        // Contrôle d'édition COLLABORATIVE : gérant toujours ; sinon allow_student_edit
        // ET page non verrouillée.
        if (! $this->canEditPage($item, $page, $manager)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:'.WikiService::TITLE_MAX,
            'body'  => 'nullable|string|max:'.WikiService::BODY_MAX,
        ]);

        // VERSIONING : snapshot de l'état précédent avant écrasement (cf. WikiService).
        WikiService::applyEdit($page, Auth::id(), $data['title'], (string) ($data['body'] ?? ''));

        return $this->backToPage($course, $lesson, $item, $page)->with('success', 'La page a été mise à jour.');
    }

    /** POST academy.wiki.pages.restore - restaure le contenu d'une révision (nouvelle version). */
    public function restoreRevision(
        Course $course,
        Lesson $lesson,
        int $itemId,
        int $pageId,
        int $revisionId
    ): RedirectResponse {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        $this->ensureContributor($course, $item, $manager);

        $page = $this->resolvePage($item, $pageId);

        // Anti-IDOR : la révision doit appartenir à CETTE page.
        $revision = WikiRevision::findOrFail($revisionId);
        if ((int) $revision->wiki_page_id !== $page->id) {
            abort(404);
        }

        // Restauration : gérant OU auteur de la page, ET sous les règles d'édition.
        $isAuthor = (int) ($page->created_by ?? 0) === (int) Auth::id();
        if (! $manager && ! $isAuthor) {
            abort(403);
        }
        if (! $this->canEditPage($item, $page, $manager)) {
            abort(403);
        }

        WikiService::applyEdit($page, Auth::id(), $revision->title, (string) ($revision->body ?? ''));

        return $this->backToPage($course, $lesson, $item, $page)->with('success', 'La révision a été restaurée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // MODÉRATION (gérant uniquement : manageEnrollments)
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.wiki.pages.lock - verrouille / déverrouille une page (bascule). */
    public function lockPage(Course $course, Lesson $lesson, int $itemId, int $pageId): RedirectResponse
    {
        $item = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);
        $page = $this->resolvePage($item, $pageId);

        $page->is_locked = ! $page->is_locked;
        $page->save();

        return $this->backToPage($course, $lesson, $item, $page)
            ->with('success', $page->is_locked ? 'Page verrouillée.' : 'Page déverrouillée.');
    }

    /** POST academy.wiki.pages.delete - soft-delete d'une page (sauf la page d'accueil). */
    public function deletePage(Course $course, Lesson $lesson, int $itemId, int $pageId): RedirectResponse
    {
        $item = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);
        $page = $this->resolvePage($item, $pageId);

        // La page d'accueil structure le wiki : on ne la supprime pas (parité Moodle).
        if ($page->is_home) {
            return $this->backToItem($course, $lesson, $item)
                ->with('error', "La page d'accueil ne peut pas être supprimée.");
        }

        $page->delete();

        return $this->backToItem($course, $lesson, $item)->with('success', 'Page supprimée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Aides privées (anti-IDOR, gardes, achèvement)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Re-résout l'item serveur et vérifie auth + appartenance leçon -> cours (anti-IDOR)
     * + type « wiki ». Abort 403 / 404 sinon.
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

        if ($item->type !== 'wiki') {
            abort(404);
        }

        return $item;
    }

    /** Re-résout une page scopée à l'item (anti-IDOR). Abort 404 sinon. */
    private function resolvePage(LessonItem $item, int $pageId): WikiPage
    {
        $page = WikiPage::findOrFail($pageId);
        if ((int) $page->lesson_item_id !== $item->id) {
            abort(404);
        }

        return $page;
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

    /**
     * Peut éditer la page ? Gérant : toujours. Étudiant : édition étudiante autorisée
     * (allow_student_edit) ET page non verrouillée. (L'inscription est déjà vérifiée.)
     */
    private function canEditPage(LessonItem $item, WikiPage $page, bool $manager): bool
    {
        if ($manager) {
            return true;
        }

        return WikiService::allowsStudentEdit($item) && ! $page->is_locked;
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
        return trim((string) $request->input(WikiService::HONEYPOT, '')) !== '';
    }

    private function backToItem(Course $course, Lesson $lesson, LessonItem $item): RedirectResponse
    {
        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }

    /** Retour sur la leçon, sur la page concernée du wiki (paramètre de requête scopé à l'item). */
    private function backToPage(Course $course, Lesson $lesson, LessonItem $item, WikiPage $page): RedirectResponse
    {
        return redirect()
            ->route('academy.lessons.show', [$course, $lesson, 'wpage_'.$item->id => $page->slug])
            ->withFragment("item-{$item->id}");
    }
}
