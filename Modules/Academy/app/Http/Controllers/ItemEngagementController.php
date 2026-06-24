<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F18 - NOTES (étoiles 1 à 5) + COMMENTAIRES sur les items de leçon (parité Moodle
 * ratings/comments).
 *
 * SÉCURITÉ (même patron que Quiz/Choice/Forum) :
 *  - auth requis (route `auth`) + throttle (route `throttle:20,1`) ;
 *  - NOTER / COMMENTER : EXIGE l'inscription active via le trait AuthorizesAcademyAccess
 *    (auth + appartenance item -> leçon -> cours anti-IDOR + inscription active +
 *    restrictions d'accès). Un non-inscrit / anonyme est rejeté (403) ;
 *  - honeypot `hp_url` sur le commentaire (anti-spam MAISON, patron Newsletter/Forum) :
 *    rempli = REJET SILENCIEUX (aucune écriture, redirection « normale ») ;
 *  - borne de longueur SERVEUR (corps <= 2000) + corps assaini au RENDU
 *    (ItemComment::renderedBody -> LessonItem::renderRichText : html_input=strip) ;
 *  - NOTE bornée 1..5 stricte (validation serveur) + UNE note par (item, user) via
 *    updateOrCreate : re-noter MET À JOUR la même ligne (jamais de doublon) ;
 *  - SUPPRESSION d'un commentaire : AUTEUR OU gérant (manageEnrollments). Item
 *    RE-RÉSOLU + commentaire scopé à l'item (anti-IDOR), soft-delete (audit conservé).
 *  - Rétrocompat : un item sans note / commentaire reste inchangé.
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Academy\Http\Controllers\Concerns\AuthorizesAcademyAccess;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\ItemComment;
use Modules\Academy\Models\ItemRating;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ItemEngagementService;

class ItemEngagementController extends Controller
{
    use AuthorizesAcademyAccess;

    // ─────────────────────────────────────────────────────────────────────────────
    // COMMENTAIRES
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.items.comments.store */
    public function storeComment(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);

        // Gaté inscription via le trait (anti-IDOR + inscription active + restrictions).
        $this->authorizeAccess($course, $lesson, $item);

        // Honeypot : rempli => rejet SILENCIEUX (le bot croit avoir réussi).
        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)
                ->with('success', 'Votre commentaire a été publié.');
        }

        $data = $request->validate([
            'body' => 'required|string|max:'.ItemEngagementService::COMMENT_MAX,
        ]);

        ItemComment::create([
            'lesson_item_id' => $item->id,
            'user_id'        => Auth::id(),
            'body'           => $data['body'],
        ]);

        return $this->backToItem($course, $lesson, $item)
            ->with('success', 'Votre commentaire a été publié.');
    }

    /** POST academy.items.comments.delete - AUTEUR OU gérant ; soft-delete (audit). */
    public function deleteComment(
        Course $course,
        Lesson $lesson,
        int $itemId,
        int $commentId
    ): RedirectResponse {
        // Re-résolution serveur (anti-IDOR) SANS exiger l'inscription : un gérant
        // modère même s'il n'est pas inscrit. L'autorisation fine (auteur OU gérant)
        // est faite juste après.
        $item = $this->resolveItem($course, $lesson, $itemId);

        // Anti-IDOR : le commentaire doit appartenir à CET item.
        $comment = ItemComment::findOrFail($commentId);
        if ((int) $comment->lesson_item_id !== $item->id) {
            abort(404);
        }

        $isManager = $this->isManager($course);
        $isAuthor  = Auth::check() && (int) $comment->user_id === (int) Auth::id();

        abort_unless($isManager || $isAuthor, 403);

        $comment->delete();

        return $this->backToItem($course, $lesson, $item)
            ->with('success', 'Commentaire supprimé.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // NOTES (étoiles)
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.items.rate - 1..5, UNE note par (item, user), re-note = MAJ. */
    public function rate(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item = LessonItem::findOrFail($itemId);

        // Gaté inscription via le trait (anti-IDOR + inscription active + restrictions).
        $this->authorizeAccess($course, $lesson, $item);

        $data = $request->validate([
            'value' => 'required|integer|min:'.ItemEngagementService::RATING_MIN
                .'|max:'.ItemEngagementService::RATING_MAX,
        ]);

        // UNE note par (item, user) : upsert scopé. Re-noter MET À JOUR la même ligne
        // (contrainte UNIQUE en base), jamais de doublon.
        ItemRating::updateOrCreate(
            ['lesson_item_id' => $item->id, 'user_id' => Auth::id()],
            ['value' => (int) $data['value']]
        );

        return $this->backToItem($course, $lesson, $item)
            ->with('success', 'Votre note a été enregistrée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Aides privées (anti-IDOR, gardes)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Re-résout l'item serveur et vérifie auth + appartenance leçon -> cours
     * (anti-IDOR). N'exige PAS l'inscription (réservé à la modération gérant/auteur).
     * Abort 403 / 404 sinon.
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

        return $item;
    }

    /** Gérant de CE cours (admin OU owner/instructor). */
    private function isManager(Course $course): bool
    {
        return Auth::check() && Auth::user()->can('manageEnrollments', $course);
    }

    /** Honeypot rempli => spam (rejet silencieux). */
    private function isSpam(Request $request): bool
    {
        return trim((string) $request->input(ItemEngagementService::HONEYPOT, '')) !== '';
    }

    private function backToItem(Course $course, Lesson $lesson, LessonItem $item): RedirectResponse
    {
        return redirect()
            ->route('academy.lessons.show', [$course, $lesson])
            ->withFragment("item-{$item->id}");
    }
}
