<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FORUM - actions d'un item de leçon « forum » (discussions attachées à une leçon,
 * type Moodle « Forum ») : ouvrir un sujet, répondre, et modérer (épingler /
 * verrouiller / supprimer) pour un gérant.
 *
 * SÉCURITÉ (même patron que Quiz/Choice/Feedback) :
 *  - auth requis (route `auth`) + throttle (route `throttle:20,1`) ;
 *  - item RE-RÉSOLU serveur et rattaché à la leçon/cours (anti-IDOR), type forcé
 *    « forum » ;
 *  - écrire EXIGE l'inscription active OU le rôle gérant (manageEnrollments). Un
 *    étudiant non inscrit est rejeté (403) ;
 *  - honeypot `hp_url` (anti-spam MAISON, patron NewsletterController) : rempli =
 *    REJET SILENCIEUX (aucune écriture, redirection « normale ») ;
 *  - bornes de longueur SERVEUR (titre <= 200, corps <= 10000) ;
 *  - locked (forum) / is_locked (sujet) : aucune réponse sauf gérant ;
 *  - allow_student_topics=false : un étudiant ne peut PAS ouvrir de sujet (gérant si) ;
 *  - modération gatée manageEnrollments + sujet/post RE-RÉSOLUS scopés à l'item
 *    (anti-IDOR), suppression = soft-delete (jamais de perte dure) ;
 *  - achèvement « post » (défaut forum) : un ÉTUDIANT complète l'item en publiant un
 *    sujet OU une réponse (jamais pour un gérant : pas de progression).
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\ForumPost;
use Modules\Academy\Models\ForumTopic;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\ForumService;

class ForumController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────────
    // ÉTUDIANT (et gérant) : ouvrir un sujet / répondre
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.forum.topics.create */
    public function createTopic(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId
    ): RedirectResponse {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        // Écrire exige l'inscription active OU le rôle gérant.
        if (! $manager && ! $this->isEnrolled($course)) {
            abort(403);
        }

        // Honeypot : rempli => rejet SILENCIEUX (le bot croit avoir réussi).
        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'Votre sujet a été publié.');
        }

        // allow_student_topics=false : seul un gérant peut ouvrir un sujet.
        if (! $manager && ! ForumService::allowsStudentTopics($item)) {
            abort(403);
        }

        // Forum verrouillé : aucun nouveau sujet sauf gérant.
        if (! $manager && ForumService::isLocked($item)) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:'.ForumService::TITLE_MAX,
            'body'  => 'required|string|max:'.ForumService::BODY_MAX,
        ]);

        ForumTopic::create([
            'lesson_item_id' => $item->id,
            'user_id'        => Auth::id(),
            'title'          => $data['title'],
            'body'           => $data['body'],
        ]);

        $this->maybeComplete($item, $manager);

        return $this->backToItem($course, $lesson, $item)->with('success', 'Votre sujet a été publié.');
    }

    /** POST academy.forum.topics.reply */
    public function reply(
        Request $request,
        Course $course,
        Lesson $lesson,
        int $itemId,
        int $topicId
    ): RedirectResponse {
        $item    = $this->resolveItem($course, $lesson, $itemId);
        $manager = $this->isManager($course);

        if (! $manager && ! $this->isEnrolled($course)) {
            abort(403);
        }

        if ($this->isSpam($request)) {
            return $this->backToItem($course, $lesson, $item)->with('success', 'Votre réponse a été publiée.');
        }

        // Anti-IDOR : le sujet doit appartenir à CET item.
        $topic = ForumTopic::findOrFail($topicId);
        if ((int) $topic->lesson_item_id !== $item->id) {
            abort(404);
        }

        // Forum global verrouillé OU sujet verrouillé : aucune réponse sauf gérant.
        if (! $manager && (ForumService::isLocked($item) || $topic->is_locked)) {
            abort(403);
        }

        $data = $request->validate([
            'body' => 'required|string|max:'.ForumService::BODY_MAX,
        ]);

        ForumPost::create([
            'topic_id' => $topic->id,
            'user_id'  => Auth::id(),
            'body'     => $data['body'],
        ]);

        $this->maybeComplete($item, $manager);

        return $this->backToItem($course, $lesson, $item)->with('success', 'Votre réponse a été publiée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // MODÉRATION (gérant uniquement : manageEnrollments)
    // ─────────────────────────────────────────────────────────────────────────────

    /** POST academy.forum.topics.pin - épingle / désépingle (bascule). */
    public function pinTopic(Course $course, Lesson $lesson, int $itemId, int $topicId): RedirectResponse
    {
        $item  = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);
        $topic = $this->resolveTopic($item, $topicId);

        $topic->is_pinned = ! $topic->is_pinned;
        $topic->save();

        return $this->backToItem($course, $lesson, $item)
            ->with('success', $topic->is_pinned ? 'Sujet épinglé.' : 'Sujet désépinglé.');
    }

    /** POST academy.forum.topics.lock - verrouille / déverrouille (bascule). */
    public function lockTopic(Course $course, Lesson $lesson, int $itemId, int $topicId): RedirectResponse
    {
        $item  = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);
        $topic = $this->resolveTopic($item, $topicId);

        $topic->is_locked = ! $topic->is_locked;
        $topic->save();

        return $this->backToItem($course, $lesson, $item)
            ->with('success', $topic->is_locked ? 'Sujet verrouillé.' : 'Sujet déverrouillé.');
    }

    /** POST academy.forum.topics.delete - soft-delete d'un sujet (audit conservé). */
    public function deleteTopic(Course $course, Lesson $lesson, int $itemId, int $topicId): RedirectResponse
    {
        $item  = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);
        $topic = $this->resolveTopic($item, $topicId);

        $topic->delete();

        return $this->backToItem($course, $lesson, $item)->with('success', 'Sujet supprimé.');
    }

    /** POST academy.forum.posts.delete - soft-delete d'une réponse (audit conservé). */
    public function deletePost(Course $course, Lesson $lesson, int $itemId, int $postId): RedirectResponse
    {
        $item = $this->resolveItem($course, $lesson, $itemId);
        $this->authorizeManager($course);

        // Anti-IDOR : la réponse doit appartenir à un sujet de CET item.
        $post  = ForumPost::findOrFail($postId);
        $topic = ForumTopic::withTrashed()->findOrFail($post->topic_id);
        if ((int) $topic->lesson_item_id !== $item->id) {
            abort(404);
        }

        $post->delete();

        return $this->backToItem($course, $lesson, $item)->with('success', 'Réponse supprimée.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Aides privées (anti-IDOR, gardes, achèvement)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Re-résout l'item serveur et vérifie auth + appartenance leçon -> cours
     * (anti-IDOR) + type « forum ». Abort 403 / 404 sinon.
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

        if ($item->type !== 'forum') {
            abort(404);
        }

        return $item;
    }

    /** Re-résout un sujet scopé à l'item (anti-IDOR). Abort 404 sinon. */
    private function resolveTopic(LessonItem $item, int $topicId): ForumTopic
    {
        $topic = ForumTopic::findOrFail($topicId);
        if ((int) $topic->lesson_item_id !== $item->id) {
            abort(404);
        }

        return $topic;
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
        return trim((string) $request->input(ForumService::HONEYPOT, '')) !== '';
    }

    /**
     * Achèvement « post » (défaut forum) : un ÉTUDIANT complète l'item en publiant.
     * Jamais pour un gérant (aucune progression). markComplete est idempotent.
     */
    private function maybeComplete(LessonItem $item, bool $manager): void
    {
        if (! $manager && ActivityCompletionService::criterionFor($item) === 'post') {
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
