<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Éditeur de cours front-end (« mode édition » façon Moodle) - PHASE 3 (FE-3).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant du cours est figé au montage ($courseId, propriété privée).
 *  - À CHAQUE mutation, le cours est RE-RÉSOLU côté serveur via resolveCourse()
 *    puis RÉ-AUTORISÉ par $this->authorize(...) sur la Policy posée en FE-1 :
 *      • métadonnées          → authorize('update', $course)
 *      • publier / dépublier  → authorize('publish', $course)
 *      • structure (chap/leçon) → authorize('manageStructure', $course)
 *      • suppression du cours  → authorize('delete', $course)
 *  - On ne fait JAMAIS confiance à un ID/état venant du navigateur. Pour chaque
 *    chapitre/leçon ciblé, on vérifie l'APPARTENANCE à CE cours (anti-IDOR) avant
 *    toute écriture.
 *  - @can(...) en Blade ne sert qu'à CACHER des boutons (jamais l'unique garde).
 *
 * Portée FE-3 : métadonnées + chapitres + leçons (CRUD + réordonnancement).
 * Portée FE-3b (cette phase) : ITEMS de leçon (vidéo ScreenPal / document / quiz).
 * Mêmes gardes serveur : resolveCourse() → authorize('manageStructure') →
 * resolveItemFor(course, lessonId, itemId) (anti-IDOR remontant
 * item->lesson->chapter->course) → validate → écrire.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

class CourseEditor extends Component
{
    /** Types d'items autorisés (liste blanche, alignée sur l'admin Backoffice). */
    private const ITEM_TYPES = ['video', 'document', 'quiz'];

    /** Identifiant du cours géré (figé au montage, source de vérité serveur). */
    public int $courseId;

    // ── Métadonnées (formulaire du cours) ───────────────────────────────────────
    public string $title = '';
    public ?string $subtitle = null;
    public ?string $summary = null;
    public string $level = 'intro';
    public string $language = 'fr-CA';
    public string $visibility = 'public';
    public string $access_type = 'free';
    public ?int $price_cents = null;

    // ── Saisie d'un nouveau chapitre ─────────────────────────────────────────────
    public string $newChapterTitle = '';
    public ?string $newChapterSummary = null;

    // ── Saisie d'une nouvelle leçon (par chapitre, indexée par chapter_id) ───────
    /** @var array<int, array{title: string, summary: ?string, estimated_minutes: ?int}> */
    public array $newLesson = [];

    // ── Saisie d'un nouvel item (par leçon, indexé par lesson_id) ────────────────
    /**
     * @var array<int, array{
     *   type: string, title: string, estimated_minutes: ?int, is_required: bool,
     *   external_ref: ?string, rich_text: ?string, qt_bank_key: ?string
     * }>
     */
    public array $newItem = [];

    // ── Confirmations de suppression inline à 2 temps (jamais confirm() natif) ───
    public ?int $confirmingChapterDeletion = null;
    public ?int $confirmingLessonDeletion = null;
    public ?int $confirmingItemDeletion = null;
    public bool $confirmingCourseDeletion = false;

    /**
     * Entrée dans l'éditeur. Autorisation SERVEUR obligatoire : seul un gérant de
     * CE cours (admin OU owner/instructor/editor) peut ouvrir l'éditeur.
     */
    public function mount(Course $course): void
    {
        $this->authorize('update', $course);

        $this->courseId = $course->id;
        $this->fillMetadataFrom($course);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Résolution + autorisation serveur (cœur anti-escalade / anti-IDOR)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Re-résout TOUJOURS le cours depuis la base (jamais depuis le navigateur).
     */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Re-résout un chapitre ET vérifie qu'il appartient bien à CE cours (anti-IDOR).
     */
    private function resolveChapterFor(Course $course, int $chapterId): Chapter
    {
        return Chapter::where('id', $chapterId)
            ->where('course_id', $course->id)
            ->firstOrFail();
    }

    /**
     * Re-résout une leçon ET vérifie qu'elle appartient bien à un chapitre de CE
     * cours (anti-IDOR) via la relation chapter.course_id.
     */
    private function resolveLessonFor(Course $course, int $lessonId): Lesson
    {
        return Lesson::where('lessons.id', $lessonId)
            ->whereHas('chapter', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();
    }

    /**
     * Re-résout un item ET vérifie qu'il appartient bien à une leçon d'un chapitre
     * de CE cours (anti-IDOR), en remontant item->lesson->chapter->course_id.
     * Un item étranger (autre cours) → ModelNotFound, aucune écriture possible.
     */
    private function resolveItemFor(Course $course, int $itemId): LessonItem
    {
        return LessonItem::where('lesson_items.id', $itemId)
            ->whereHas('lesson.chapter', fn ($q) => $q->where('course_id', $course->id))
            ->firstOrFail();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // MÉTADONNÉES
    // ─────────────────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('update', $course);

        $validated = $this->validate([
            'title'       => 'required|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'summary'     => 'nullable|string|max:1000',
            'level'       => ['required', Rule::in(['intro', 'inter', 'avance'])],
            'language'    => 'required|string|max:10',
            'visibility'  => ['required', Rule::in(['public', 'unlisted', 'private'])],
            'access_type' => ['required', Rule::in(['free', 'paid_one_time', 'paid_subscription'])],
            'price_cents' => 'nullable|integer|min:0',
        ]);

        // Prix obligatoire pour un cours payant (règle reprise de l'admin).
        $isPaid = in_array($validated['access_type'], ['paid_one_time', 'paid_subscription'], true);
        if ($isPaid) {
            if (empty($validated['price_cents']) || $validated['price_cents'] <= 0) {
                $this->addError('price_cents', 'Un prix supérieur à zéro est requis pour un cours payant.');

                return;
            }
        } else {
            $validated['price_cents'] = null;
        }

        // Slug : ne PAS casser un slug publié existant. On ne (re)génère un slug
        // que s'il est absent (sécurité : un cours déjà publié garde son URL).
        if (blank($course->slug)) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['updated_by'] = Auth::id();

        $course->update($validated);

        session()->flash('academy_editor_status', 'Métadonnées du cours enregistrées.');
    }

    public function togglePublish(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('publish', $course);

        $newStatus = $course->status === 'published' ? 'draft' : 'published';

        $course->update([
            'status'       => $newStatus,
            'updated_by'   => Auth::id(),
            'published_at' => $newStatus === 'published' ? ($course->published_at ?? now()) : $course->published_at,
        ]);

        session()->flash(
            'academy_editor_status',
            $newStatus === 'published' ? 'Cours publié.' : 'Cours repassé en brouillon.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // CHAPITRES
    // ─────────────────────────────────────────────────────────────────────────────

    public function addChapter(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $this->validate([
            'newChapterTitle'   => 'required|string|max:200',
            'newChapterSummary' => 'nullable|string|max:1000',
        ]);

        $position = (int) Chapter::where('course_id', $course->id)->max('position') + 1;

        Chapter::create([
            'course_id' => $course->id,
            'title'     => $this->newChapterTitle,
            'summary'   => $this->newChapterSummary,
            'position'  => $position,
        ]);

        $this->reset(['newChapterTitle', 'newChapterSummary']);
        session()->flash('academy_editor_status', 'Chapitre ajouté.');
    }

    public function updateChapter(int $chapterId, string $title, ?string $summary = null): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $chapter = $this->resolveChapterFor($course, $chapterId);

        $data = validator(
            ['title' => $title, 'summary' => $summary],
            [
                'title'   => 'required|string|max:200',
                'summary' => 'nullable|string|max:1000',
            ]
        )->validate();

        $chapter->update($data);

        session()->flash('academy_editor_status', 'Chapitre mis à jour.');
    }

    public function deleteChapter(int $chapterId): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $chapter = $this->resolveChapterFor($course, $chapterId);
        $chapter->delete(); // suppression en cascade des leçons gérée par la FK (onDelete cascade)

        $this->confirmingChapterDeletion = null;
        session()->flash('academy_editor_status', 'Chapitre supprimé.');
    }

    public function moveChapterUp(int $chapterId): void
    {
        $this->swapChapter($chapterId, 'up');
    }

    public function moveChapterDown(int $chapterId): void
    {
        $this->swapChapter($chapterId, 'down');
    }

    private function swapChapter(int $chapterId, string $direction): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $chapter = $this->resolveChapterFor($course, $chapterId);

        $neighbor = Chapter::where('course_id', $course->id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $chapter->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $chapter->position)->orderBy('position')
            )
            ->first();

        if (! $neighbor) {
            return; // déjà en bout de liste
        }

        $tmp                = $chapter->position;
        $chapter->position  = $neighbor->position;
        $neighbor->position = $tmp;
        $chapter->save();
        $neighbor->save();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // LEÇONS
    // ─────────────────────────────────────────────────────────────────────────────

    public function addLesson(int $chapterId): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : le chapitre doit appartenir à CE cours.
        $chapter = $this->resolveChapterFor($course, $chapterId);

        $input = $this->newLesson[$chapterId] ?? ['title' => '', 'summary' => null, 'estimated_minutes' => null];

        $data = validator($input, [
            'title'             => 'required|string|max:255',
            'summary'           => 'nullable|string|max:1000',
            'estimated_minutes' => 'nullable|integer|min:1',
        ])->validate();

        $position = (int) Lesson::where('chapter_id', $chapter->id)->max('position') + 1;

        Lesson::create([
            'chapter_id'        => $chapter->id,
            'title'             => $data['title'],
            'slug'              => Str::slug($data['title']),
            'summary'           => $data['summary'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'position'          => $position,
        ]);

        unset($this->newLesson[$chapterId]);
        session()->flash('academy_editor_status', 'Leçon ajoutée.');
    }

    public function updateLesson(int $lessonId, string $title, ?string $summary = null, ?int $estimatedMinutes = null): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : la leçon doit appartenir à un chapitre de CE cours.
        $lesson = $this->resolveLessonFor($course, $lessonId);

        $data = validator(
            ['title' => $title, 'summary' => $summary, 'estimated_minutes' => $estimatedMinutes],
            [
                'title'             => 'required|string|max:255',
                'summary'           => 'nullable|string|max:1000',
                'estimated_minutes' => 'nullable|integer|min:1',
            ]
        )->validate();

        // Slug : ne PAS casser un slug existant (le slug suit le cycle de vie de la leçon).
        $lesson->update([
            'title'             => $data['title'],
            'summary'           => $data['summary'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
        ]);

        session()->flash('academy_editor_status', 'Leçon mise à jour.');
    }

    public function deleteLesson(int $lessonId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $lesson = $this->resolveLessonFor($course, $lessonId);
        $lesson->delete();

        $this->confirmingLessonDeletion = null;
        session()->flash('academy_editor_status', 'Leçon supprimée.');
    }

    public function moveLessonUp(int $lessonId): void
    {
        $this->swapLesson($lessonId, 'up');
    }

    public function moveLessonDown(int $lessonId): void
    {
        $this->swapLesson($lessonId, 'down');
    }

    private function swapLesson(int $lessonId, string $direction): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $lesson = $this->resolveLessonFor($course, $lessonId);

        $neighbor = Lesson::where('chapter_id', $lesson->chapter_id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $lesson->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $lesson->position)->orderBy('position')
            )
            ->first();

        if (! $neighbor) {
            return;
        }

        $tmp               = $lesson->position;
        $lesson->position  = $neighbor->position;
        $neighbor->position = $tmp;
        $lesson->save();
        $neighbor->save();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // ITEMS DE LEÇON (vidéo ScreenPal / document / quiz) - FE-3b
    //
    // Chaque mutation : resolveCourse() → authorize('manageStructure') →
    // resolveLessonFor / resolveItemFor (anti-IDOR) → validate → écrire.
    // Le `type` est toujours validé contre la liste blanche ITEM_TYPES.
    // ─────────────────────────────────────────────────────────────────────────────

    public function addItem(int $lessonId, string $type): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : la leçon doit appartenir à un chapitre de CE cours.
        $lesson = $this->resolveLessonFor($course, $lessonId);

        $input          = $this->newItem[$lessonId] ?? [];
        $input['type']  = $type;

        $data    = $this->validateItem($input);
        $payload = $this->buildItemPayload($data['type'], $input);

        $position = (int) LessonItem::where('lesson_id', $lesson->id)->max('position') + 1;

        LessonItem::create([
            'lesson_id'         => $lesson->id,
            'type'              => $data['type'],
            'title'             => $data['title'],
            'position'          => $position,
            'payload'           => $payload,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'is_required'       => (bool) ($input['is_required'] ?? false),
            'external_ref'      => $data['external_ref'] ?? null,
        ]);

        unset($this->newItem[$lessonId]);
        session()->flash('academy_editor_status', 'Élément ajouté.');
    }

    public function updateItem(
        int $itemId,
        string $type,
        string $title,
        ?int $estimatedMinutes = null,
        ?string $externalRef = null,
        ?string $richText = null,
        ?string $qtBankKey = null
    ): void {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : l'item doit appartenir à une leçon d'un chapitre de CE cours.
        $item = $this->resolveItemFor($course, $itemId);

        $input = [
            'type'              => $type,
            'title'             => $title,
            'estimated_minutes' => $estimatedMinutes,
            'external_ref'      => $externalRef,
            'rich_text'         => $richText,
            'qt_bank_key'       => $qtBankKey,
        ];

        $data    = $this->validateItem($input);
        $payload = $this->buildItemPayload($data['type'], $input);

        $item->update([
            'type'              => $data['type'],
            'title'             => $data['title'],
            'payload'           => $payload,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'external_ref'      => $data['external_ref'] ?? null,
        ]);

        session()->flash('academy_editor_status', 'Élément mis à jour.');
    }

    public function deleteItem(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);
        $item->delete();

        $this->confirmingItemDeletion = null;
        session()->flash('academy_editor_status', 'Élément supprimé.');
    }

    public function toggleRequired(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);
        $item->update(['is_required' => ! $item->is_required]);

        session()->flash('academy_editor_status', 'Élément mis à jour.');
    }

    public function moveItemUp(int $itemId): void
    {
        $this->swapItem($itemId, 'up');
    }

    public function moveItemDown(int $itemId): void
    {
        $this->swapItem($itemId, 'down');
    }

    private function swapItem(int $itemId, string $direction): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $neighbor = LessonItem::where('lesson_id', $item->lesson_id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $item->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $item->position)->orderBy('position')
            )
            ->first();

        if (! $neighbor) {
            return; // déjà en bout de liste
        }

        $tmp                = $item->position;
        $item->position     = $neighbor->position;
        $neighbor->position = $tmp;
        $item->save();
        $neighbor->save();
    }

    /**
     * Validation commune d'un item. Le `type` est contraint à la liste blanche
     * ITEM_TYPES (un type forgé hors liste est rejeté avant toute écriture).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validateItem(array $input): array
    {
        return validator($input, [
            'type'              => ['required', Rule::in(self::ITEM_TYPES)],
            'title'             => 'required|string|max:255',
            'estimated_minutes' => 'nullable|integer|min:1',
            'external_ref'      => 'nullable|string|max:500',
            'rich_text'         => 'nullable|string|max:20000',
            'qt_bank_key'       => 'nullable|string|max:120',
        ])->validate();
    }

    /**
     * Construit le payload (array casté) propre à chaque type, de façon défensive :
     *  - video    : référence d'intégration ScreenPal (external_ref OU payload.embed).
     *  - document : texte riche / markdown simple (payload.rich_text).
     *  - quiz     : clé de banque QT réutilisée par QtService (payload.qt_bank_key).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildItemPayload(string $type, array $input): array
    {
        return match ($type) {
            'video'    => ['embed' => trim((string) ($input['external_ref'] ?? '')) ?: null],
            'document' => ['rich_text' => (string) ($input['rich_text'] ?? '')],
            'quiz'     => ['qt_bank_key' => trim((string) ($input['qt_bank_key'] ?? '')) ?: null],
            default    => [],
        };
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // SUPPRESSION DU COURS (action la plus sensible : admin OU owner uniquement)
    // ─────────────────────────────────────────────────────────────────────────────

    public function deleteCourse()
    {
        $course = $this->resolveCourse();
        $this->authorize('delete', $course);

        $course->delete();

        return redirect()->route('academy.dashboard');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Confirmations inline à 2 temps (jamais de popup native)
    // ─────────────────────────────────────────────────────────────────────────────

    public function confirmChapterDeletion(int $chapterId): void
    {
        $this->confirmingChapterDeletion = $chapterId;
    }

    public function cancelChapterDeletion(): void
    {
        $this->confirmingChapterDeletion = null;
    }

    public function confirmLessonDeletion(int $lessonId): void
    {
        $this->confirmingLessonDeletion = $lessonId;
    }

    public function cancelLessonDeletion(): void
    {
        $this->confirmingLessonDeletion = null;
    }

    public function confirmItemDeletion(int $itemId): void
    {
        $this->confirmingItemDeletion = $itemId;
    }

    public function cancelItemDeletion(): void
    {
        $this->confirmingItemDeletion = null;
    }

    public function confirmCourseDeletion(): void
    {
        $this->confirmingCourseDeletion = true;
    }

    public function cancelCourseDeletion(): void
    {
        $this->confirmingCourseDeletion = false;
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Lecture (affichage)
    // ─────────────────────────────────────────────────────────────────────────────

    /** Le cours frais, avec chapitres + leçons + compte d'items, pour l'affichage. */
    #[Computed]
    public function course(): Course
    {
        return Course::with([
            'chapters' => fn ($q) => $q->orderBy('position'),
            'chapters.lessons' => fn ($q) => $q->orderBy('position')->withCount('lessonItems'),
            'chapters.lessons.lessonItems' => fn ($q) => $q->orderBy('position'),
        ])->findOrFail($this->courseId);
    }

    private function fillMetadataFrom(Course $course): void
    {
        $this->title       = (string) $course->title;
        $this->subtitle    = $course->subtitle;
        $this->summary     = $course->summary;
        $this->level       = (string) ($course->level ?? 'intro');
        $this->language    = (string) ($course->language ?? 'fr-CA');
        $this->visibility  = (string) ($course->visibility ?? 'public');
        $this->access_type = (string) ($course->access_type ?? 'free');
        $this->price_cents = $course->price_cents;
    }

    public function render()
    {
        return view('academy::livewire.course-editor');
    }
}
