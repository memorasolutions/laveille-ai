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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuestionCategory;

class CourseEditor extends Component
{
    use WithFileUploads;

    /** Types d'items autorisés (liste blanche, alignée sur l'admin Backoffice). */
    private const ITEM_TYPES = ['video', 'document', 'quiz'];

    /** Tailles maximales (Ko) des téléversements - validées côté SERVEUR. */
    private const COVER_MAX_KB = 4096;       // ~4 Mo (image de couverture)
    private const POSTER_MAX_KB = 4096;      // ~4 Mo (affiche vidéo)
    private const ATTACHMENT_MAX_KB = 10240; // ~10 Mo (pièce jointe document)

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

    /** Modèle réutilisable (C3) : ce cours peut être proposé dans la section « Modèles ». */
    public bool $is_template = false;

    // ── Personnalisation du certificat (E3) ─────────────────────────────────────
    // Tous facultatifs : vide ('') → null → on retombe sur les défauts du gabarit.
    public ?string $certificate_title = null;
    public ?string $certificate_message = null;
    public ?string $certificate_signature_name = null;
    public ?string $certificate_accent_color = null;

    /**
     * Prérequis du cours (C4) : ids des AUTRES cours à compléter avant celui-ci.
     * Piloté par des cases à cocher dans l'éditeur ; toute écriture re-valide
     * l'appartenance des ids à l'ensemble des cours visibles (anti-IDOR).
     *
     * @var array<int, int>
     */
    public array $prerequisiteIds = [];

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
     *   external_ref: ?string, player_url: ?string, poster_url: ?string,
     *   duration_minutes: ?int, rich_text: ?string, qt_bank_key: ?string,
     *   passing_score: ?int, attempts_allowed: ?int
     * }>
     */
    public array $newItem = [];

    /**
     * V1-a : feedback global par tranche de score (« grade boundaries » Moodle),
     * indexé par item_id. Chaque entrée = liste de lignes {min_percent, message}.
     * Édité par des actions dédiées (ajout/retrait de ligne + enregistrement), à la
     * manière des prérequis : la liste dynamique ne se prête pas au $event.target inline.
     *
     * @var array<int, array<int, array{min_percent: int|string, message: string}>>
     */
    public array $overallFeedback = [];

    // ── Téléversements (Livewire WithFileUploads) ───────────────────────────────
    /** Fichier image de couverture en attente de traitement (TemporaryUploadedFile). */
    public $cover = null;

    /** Affiche vidéo en attente, indexée par item_id (TemporaryUploadedFile). */
    public array $itemPoster = [];

    /** Pièce jointe document en attente, indexée par item_id (TemporaryUploadedFile). */
    public array $itemAttachment = [];

    // ── Confirmations de suppression inline à 2 temps (jamais confirm() natif) ───
    public ?int $confirmingChapterDeletion = null;
    public ?int $confirmingLessonDeletion = null;
    public ?int $confirmingItemDeletion = null;
    public bool $confirmingCourseDeletion = false;

    /** Liste blanche des métadonnées du cours en autosave (wire:model.blur → updated()). */
    private const METADATA_FIELDS = [
        'title', 'subtitle', 'summary', 'level',
        'language', 'visibility', 'access_type', 'price_cents', 'is_template',
    ];

    /** Champs de personnalisation du certificat (E3) en autosave (wire:model.live.blur). */
    private const CERTIFICATE_FIELDS = [
        'certificate_title', 'certificate_message',
        'certificate_signature_name', 'certificate_accent_color',
    ];

    /**
     * Entrée dans l'éditeur. Autorisation SERVEUR obligatoire : seul un gérant de
     * CE cours (admin OU owner/instructor/editor) peut ouvrir l'éditeur.
     */
    public function mount(Course $course): void
    {
        $this->authorize('update', $course);

        $this->courseId = $course->id;
        $this->fillMetadataFrom($course);
        $this->prerequisiteIds = $course->prerequisites()->pluck('courses.id')->map(fn ($id) => (int) $id)->values()->all();
        // Note: pluck('courses.id') qualifie la colonne du modèle lié (table courses).
    }

    /**
     * Signal global « enregistré » (DRY). Flash session pour la persistance d'un
     * rechargement + évènement Livewire pour l'indicateur Alpine discret en haut.
     */
    private function flashSaved(string $msg): void
    {
        session()->flash('academy_editor_status', $msg);
        $this->dispatch('academy-saved', message: $msg);
    }

    /**
     * Autosave des métadonnées du cours. Déclenché par wire:model.blur sur la liste
     * blanche des 8 champs de métadonnées UNIQUEMENT (jamais les saisies de nouveaux
     * chapitres/leçons/items ni les uploads). La validation/sécurité de save() reste
     * la seule garde : resolveCourse() → authorize('update') → validate → écrire.
     */
    public function updated(string $name): void
    {
        if (in_array($name, self::METADATA_FIELDS, true)) {
            $this->save();

            return;
        }

        if (in_array($name, self::CERTIFICATE_FIELDS, true)) {
            $this->saveCertificate();
        }
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
            'is_template' => 'boolean',
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

        $this->flashSaved('Métadonnées du cours enregistrées.');
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
    // PRÉREQUIS DE COURS (C4) - gâtés manageStructure
    //
    // SÉCURITÉ : resolveCourse() → authorize('manageStructure') → on RE-RÉSOUT côté
    // serveur l'ensemble des cours candidats (visibles par l'utilisateur, hors cours
    // courant) et on n'écrit QUE l'intersection avec les ids reçus (anti-IDOR : un id
    // forgé d'un cours non visible / du cours lui-même est ignoré). Auto-référence et
    // doublons impossibles par construction.
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Cours candidats aux prérequis : ceux que l'utilisateur peut VOIR (la même
     * requête scoped que le tableau de bord), EXCLUANT le cours courant. Sert à la
     * fois à l'affichage (cases à cocher) et de liste blanche serveur (anti-IDOR).
     *
     * @return \Illuminate\Support\Collection<int, Course>
     */
    #[Computed]
    public function availableCourses(): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        $query = Course::query()
            ->where('id', '!=', $this->courseId)
            ->orderBy('title');

        // Admin (academy.manage) voit tout ; sinon, uniquement les cours dont il est
        // gérant (course_roles), via la même logique d'ownership que la Policy.
        if (! ($user?->can('academy.manage'))) {
            $query->whereHas('courseRoles', fn ($q) => $q->where('user_id', $user?->id));
        }

        return $query->get();
    }

    /**
     * Synchronise les prérequis du cours avec la sélection de cases à cocher.
     * Seuls les ids appartenant à availableCourses() (cours visibles, hors courant)
     * sont conservés : un id forgé (cours non visible ou auto-référence) est écarté.
     */
    public function savePrerequisites(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Liste blanche serveur : ids des cours réellement visibles par l'utilisateur.
        $allowed = $this->availableCourses->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Intersection : on n'attache QUE des prérequis légitimes (anti-IDOR + anti
        // auto-référence, le cours courant étant déjà exclu de availableCourses()).
        $clean = array_values(array_unique(array_intersect(
            array_map(static fn ($id) => (int) $id, $this->prerequisiteIds),
            $allowed
        )));

        $course->prerequisites()->sync($clean);

        $this->prerequisiteIds = $clean;
        $this->flashSaved('Prérequis du cours enregistrés.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // PERSONNALISATION DU CERTIFICAT (E3) - gâtée manageStructure
    //
    // SÉCURITÉ : resolveCourse() → authorize('manageStructure') → validate → écrire.
    // Un gérant ne personnalise que SES cours (le cours est re-résolu serveur). Les
    // valeurs vides ('') sont normalisées en null → le gabarit retombe sur ses défauts
    // (rétrocompatibilité totale). La couleur d'accent est validée comme hex #RGB/#RRGGBB ;
    // l'anti-XSS final est assuré au RENDU (e() pour titre/signature, markdown nettoyé
    // pour le message), cf. public/certificate.blade.php.
    // ─────────────────────────────────────────────────────────────────────────────

    public function saveCertificate(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Normalisation '' → null AVANT validation : un champ vidé efface la personnalisation
        // (retour au défaut), il ne doit jamais déclencher une règle sur chaîne vide.
        foreach (['certificate_title', 'certificate_message', 'certificate_signature_name', 'certificate_accent_color'] as $field) {
            $value = $this->{$field};
            $this->{$field} = (is_string($value) && trim($value) === '') ? null : (is_string($value) ? trim($value) : $value);
        }

        $validated = $this->validate([
            'certificate_title'          => 'nullable|string|max:120',
            'certificate_message'        => 'nullable|string|max:2000',
            'certificate_signature_name' => 'nullable|string|max:120',
            // Couleur d'accent : hexadécimal #RGB ou #RRGGBB uniquement (sinon rejet).
            'certificate_accent_color'   => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
        ], [
            'certificate_accent_color.regex' => 'La couleur doit être un code hexadécimal valide (ex. #064E5A).',
        ]);

        $course->update([
            'certificate_title'          => $validated['certificate_title'] ?? null,
            'certificate_message'        => $validated['certificate_message'] ?? null,
            'certificate_signature_name' => $validated['certificate_signature_name'] ?? null,
            'certificate_accent_color'   => $validated['certificate_accent_color'] ?? null,
            'updated_by'                 => Auth::id(),
        ]);

        $this->flashSaved('Certificat du cours enregistré.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // IMAGE DE COUVERTURE (média Spatie, collection « cover »)
    //
    // SÉCURITÉ : re-résout le cours + authorize('update') AVANT toute écriture ;
    // valide le mime (image jpg/png/webp) ET la taille côté SERVEUR ; le fichier
    // reçoit un nom non devinable (uniqid Spatie) sur le disque public.
    // ─────────────────────────────────────────────────────────────────────────────

    public function saveCover(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('update', $course);

        $this->validate([
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::COVER_MAX_KB],
        ]);

        // singleFile() remplace automatiquement l'ancienne couverture.
        // addMedia($UploadedFile) : Spatie lit le mime réel du fichier téléversé.
        $media = $course->addMedia($this->cover)
            ->usingFileName($this->safeFileName($this->cover, 'couverture'))
            ->toMediaCollection('cover');

        // Référence numérique cohérente avec le nom de colonne *_media_id.
        $course->forceFill(['image_media_id' => $media->id, 'updated_by' => Auth::id()])->save();

        $this->reset('cover');
        $this->flashSaved('Image de couverture mise à jour.');
    }

    public function removeCover(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('update', $course);

        $course->clearMediaCollection('cover');
        $course->forceFill(['image_media_id' => null, 'updated_by' => Auth::id()])->save();

        $this->reset('cover');
        $this->flashSaved('Image de couverture retirée.');
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
        $this->flashSaved('Chapitre ajouté.');
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

        $this->flashSaved('Chapitre mis à jour.');
    }

    public function deleteChapter(int $chapterId): void
    {
        $course  = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $chapter = $this->resolveChapterFor($course, $chapterId);
        $chapter->delete(); // suppression en cascade des leçons gérée par la FK (onDelete cascade)

        $this->confirmingChapterDeletion = null;
        $this->flashSaved('Chapitre supprimé.');
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

    /**
     * Réordonne les CHAPITRES du cours (glisser-déposer + persistance instantanée).
     *
     * On ne fait JAMAIS confiance à l'ordre venu du navigateur : on RE-RÉSOUT le cours,
     * on RÉ-AUTORISE manageStructure, puis on VÉRIFIE que l'ensemble d'ids reçu est
     * EXACTEMENT celui des chapitres de CE cours (anti-IDOR). Le moindre id étranger,
     * manquant ou en double → rejet TOTAL, aucune écriture (transaction).
     *
     * @param  array<int|string>  $orderedIds  liste ordonnée des ids de chapitres
     */
    public function reorderChapters(array $orderedIds): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $ordered  = $this->sanitizeOrderedIds($orderedIds);
        $expected = Chapter::where('course_id', $course->id)->pluck('id')->all();

        if (! $this->orderedIdsMatch($ordered, $expected)) {
            return; // ensemble forgé / incomplet : on n'écrit rien (anti-IDOR).
        }

        DB::transaction(function () use ($course, $ordered): void {
            foreach ($ordered as $index => $chapterId) {
                Chapter::where('id', $chapterId)
                    ->where('course_id', $course->id)
                    ->update(['position' => $index + 1]);
            }
        });

        $this->flashSaved('Ordre des chapitres enregistré.');
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
        $this->flashSaved('Leçon ajoutée.');
    }

    public function updateLesson(
        int $lessonId,
        string $title,
        ?string $summary = null,
        $estimatedMinutes = null,
        $dripDays = null
    ): void {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : la leçon doit appartenir à un chapitre de CE cours.
        $lesson = $this->resolveLessonFor($course, $lessonId);

        // Les champs numériques arrivent du DOM en CHAÎNE (value d'un <input>). Un champ
        // vidé ('') doit valoir null, jamais provoquer un TypeError (strict_types) sur un
        // paramètre typé ?int. On normalise donc ici avant validation : '' / null → null,
        // sinon entier.
        $estimatedMinutes = ($estimatedMinutes === '' || $estimatedMinutes === null) ? null : (int) $estimatedMinutes;
        $dripDays = ($dripDays === '' || $dripDays === null) ? null : (int) $dripDays;

        $data = validator(
            [
                'title'             => $title,
                'summary'           => $summary,
                'estimated_minutes' => $estimatedMinutes,
                'drip_days'         => $dripDays,
            ],
            [
                'title'             => 'required|string|max:255',
                'summary'           => 'nullable|string|max:1000',
                'estimated_minutes' => 'nullable|integer|min:1',
                // C4 : 0 ou vide = disponible immédiatement (null). Max 365 jours.
                'drip_days'         => 'nullable|integer|min:0|max:365',
            ]
        )->validate();

        // 0 (« immédiat ») est normalisé en null pour rester cohérent avec « pas de drip ».
        $drip = $data['drip_days'] ?? null;
        if ($drip !== null && (int) $drip === 0) {
            $drip = null;
        }

        // Slug : ne PAS casser un slug existant (le slug suit le cycle de vie de la leçon).
        $lesson->update([
            'title'             => $data['title'],
            'summary'           => $data['summary'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'drip_days'         => $drip,
        ]);

        $this->flashSaved('Leçon mise à jour.');
    }

    public function deleteLesson(int $lessonId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $lesson = $this->resolveLessonFor($course, $lessonId);
        $lesson->delete();

        $this->confirmingLessonDeletion = null;
        $this->flashSaved('Leçon supprimée.');
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

    /**
     * Réordonne les LEÇONS d'un chapitre (glisser-déposer + persistance instantanée).
     *
     * Anti-IDOR à deux niveaux : le CHAPITRE doit appartenir à CE cours, et l'ensemble
     * d'ids reçu doit être EXACTEMENT celui des leçons de CE chapitre. Sinon → rejet
     * total, aucune écriture.
     *
     * @param  array<int|string>  $orderedIds
     */
    public function reorderLessons(int $chapterId, array $orderedIds): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Le chapitre cible doit appartenir à CE cours (sinon ModelNotFound).
        $chapter = $this->resolveChapterFor($course, $chapterId);

        $ordered  = $this->sanitizeOrderedIds($orderedIds);
        $expected = Lesson::where('chapter_id', $chapter->id)->pluck('id')->all();

        if (! $this->orderedIdsMatch($ordered, $expected)) {
            return;
        }

        DB::transaction(function () use ($chapter, $ordered): void {
            foreach ($ordered as $index => $lessonId) {
                Lesson::where('id', $lessonId)
                    ->where('chapter_id', $chapter->id)
                    ->update(['position' => $index + 1]);
            }
        });

        $this->flashSaved('Ordre des leçons enregistré.');
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
        $this->flashSaved('Élément ajouté.');
    }

    /**
     * Met à jour un item. Les champs facultatifs propres à un type (vidéo / quiz)
     * sont passés en tableau associatif $extra pour éviter une signature trop longue
     * et rester rétrocompatible : un champ absent vaut null (ignoré au build).
     *
     * @param  array<string, mixed>  $extra  player_url, poster_url, duration_minutes,
     *                                        external_ref, rich_text, qt_bank_key,
     *                                        passing_score, attempts_allowed
     */
    public function updateItem(
        int $itemId,
        string $type,
        string $title,
        ?int $estimatedMinutes = null,
        array $extra = []
    ): void {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // Anti-IDOR : l'item doit appartenir à une leçon d'un chapitre de CE cours.
        $item = $this->resolveItemFor($course, $itemId);

        $input = [
            'type'              => $type,
            'title'             => $title,
            'estimated_minutes' => $estimatedMinutes,
            'external_ref'      => $extra['external_ref']     ?? null,
            'player_url'        => $extra['player_url']       ?? null,
            'poster_url'        => $extra['poster_url']       ?? null,
            'duration_minutes'  => $extra['duration_minutes'] ?? null,
            'rich_text'         => $extra['rich_text']        ?? null,
            'qt_bank_key'       => $extra['qt_bank_key']      ?? null,
            'passing_score'     => $extra['passing_score']    ?? null,
            'attempts_allowed'  => $extra['attempts_allowed'] ?? null,
            // V1-c : méthode de notation sur tentatives (highest/average/first/last).
            'grading_method'    => $extra['grading_method']   ?? null,
            // QB2 : lien optionnel vers une catégorie de MA banque + nb à tirer.
            'bank_category_id'  => $extra['bank_category_id'] ?? null,
            'bank_draw_count'   => $extra['bank_draw_count']  ?? null,
            // QB3 : toggle « inclure les sous-catégories » (défaut true si absent).
            'bank_include_subcategories' => $extra['bank_include_subcategories'] ?? null,
            // V1-d : mélange (questions / réponses), limite de temps, options de révision.
            'shuffle_questions'   => $extra['shuffle_questions']   ?? null,
            'shuffle_answers'     => $extra['shuffle_answers']     ?? null,
            'time_limit_minutes'  => $extra['time_limit_minutes']  ?? null,
            'review_options'      => $extra['review_options']      ?? null,
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

        $this->flashSaved('Élément mis à jour.');
    }

    public function deleteItem(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);
        $item->delete();

        $this->confirmingItemDeletion = null;
        $this->flashSaved('Élément supprimé.');
    }

    public function toggleRequired(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);
        $item->update(['is_required' => ! $item->is_required]);

        $this->flashSaved('Élément mis à jour.');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // MÉDIA DES ITEMS : affiche vidéo (poster) + pièces jointes document
    //
    // SÉCURITÉ : chaque action re-résout le cours + authorize('manageStructure'),
    // puis resolveItemFor() (anti-IDOR : l'item doit appartenir à CE cours) AVANT
    // toute écriture ; mime + taille validés côté SERVEUR ; noms non devinables.
    // ─────────────────────────────────────────────────────────────────────────────

    public function uploadItemPoster(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $this->validate([
            "itemPoster.$itemId" => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::POSTER_MAX_KB],
        ]);

        $file  = $this->itemPoster[$itemId];
        $media = $item->addMedia($file)
            ->usingFileName($this->safeFileName($file, 'affiche'))
            ->toMediaCollection('poster');

        // Le lecteur lit posterUrl() (média en priorité) ; on garde aussi payload['poster']
        // synchronisé pour la rétrocompatibilité de l'affichage existant.
        $payload           = $item->payload ?? [];
        $payload['poster'] = $media->getUrl();
        $item->forceFill(['poster_media_id' => $media->id, 'payload' => $payload])->save();

        unset($this->itemPoster[$itemId]);
        $this->flashSaved('Affiche de la vidéo mise à jour.');
    }

    public function removeItemPoster(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);
        $item->clearMediaCollection('poster');

        $payload = $item->payload ?? [];
        unset($payload['poster']);
        $item->forceFill(['poster_media_id' => null, 'payload' => $payload])->save();

        unset($this->itemPoster[$itemId]);
        $this->flashSaved('Affiche de la vidéo retirée.');
    }

    public function uploadItemAttachment(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $this->validate([
            "itemAttachment.$itemId" => [
                'required', 'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,webp',
                'max:'.self::ATTACHMENT_MAX_KB,
            ],
        ]);

        $file = $this->itemAttachment[$itemId];

        // Lire le nom client AVANT addMedia() (qui déplace le fichier temporaire).
        // Ce nom ne sert que de LIBELLÉ d'affichage, jamais de nom de stockage.
        $displayName = $file->getClientOriginalName();

        $media = $item->addMedia($file)
            ->usingFileName($this->safeFileName($file, 'document'))
            ->toMediaCollection('attachments');

        // Le lecteur (lesson.blade) lit payload['attachments'] = [{name, url}].
        $payload                = $item->payload ?? [];
        $attachments            = $payload['attachments'] ?? [];
        $attachments[]          = [
            'name'     => $displayName,
            'url'      => $media->getUrl(),
            'media_id' => $media->id,
        ];
        $payload['attachments'] = array_values($attachments);
        $item->forceFill(['payload' => $payload])->save();

        unset($this->itemAttachment[$itemId]);
        $this->flashSaved('Pièce jointe ajoutée.');
    }

    public function removeItemAttachment(int $itemId, int $mediaId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        // Le média doit appartenir à CET item (anti-IDOR) avant suppression.
        $media = $item->getMedia('attachments')->firstWhere('id', $mediaId);
        if ($media) {
            $media->delete();
        }

        $payload                = $item->payload ?? [];
        $payload['attachments'] = array_values(array_filter(
            $payload['attachments'] ?? [],
            fn ($a) => ($a['media_id'] ?? null) !== $mediaId
        ));
        $item->forceFill(['payload' => $payload])->save();

        $this->flashSaved('Pièce jointe retirée.');
    }

    /**
     * Nom de fichier non devinable et sûr : slug du préfixe + identifiant unique +
     * extension d'origine en liste blanche (jamais .php/.phtml, etc.). On ne se fie
     * jamais au nom client pour le stockage (le nom client n'est conservé que comme
     * libellé d'affichage des pièces jointes).
     */
    private function safeFileName($file, string $prefix): string
    {
        $ext  = strtolower((string) $file->getClientOriginalExtension());
        $safe = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'], true) ? $ext : 'bin';

        return Str::slug($prefix).'-'.Str::random(16).'.'.$safe;
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
     * Réordonne les ITEMS d'une leçon (glisser-déposer + persistance instantanée).
     *
     * Anti-IDOR : la LEÇON doit appartenir à un chapitre de CE cours, et l'ensemble
     * d'ids reçu doit être EXACTEMENT celui des items de CETTE leçon. Sinon → rejet
     * total, aucune écriture.
     *
     * @param  array<int|string>  $orderedIds
     */
    public function reorderItems(int $lessonId, array $orderedIds): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        // La leçon cible doit appartenir à un chapitre de CE cours (sinon ModelNotFound).
        $lesson = $this->resolveLessonFor($course, $lessonId);

        $ordered  = $this->sanitizeOrderedIds($orderedIds);
        $expected = LessonItem::where('lesson_id', $lesson->id)->pluck('id')->all();

        if (! $this->orderedIdsMatch($ordered, $expected)) {
            return;
        }

        DB::transaction(function () use ($lesson, $ordered): void {
            foreach ($ordered as $index => $itemId) {
                LessonItem::where('id', $itemId)
                    ->where('lesson_id', $lesson->id)
                    ->update(['position' => $index + 1]);
            }
        });

        $this->flashSaved('Ordre des éléments enregistré.');
    }

    /**
     * Normalise une liste d'ids venue du navigateur en entiers positifs (les valeurs
     * non numériques ou <= 0 sont écartées). On ne se fie jamais au type reçu.
     *
     * @param  array<int|string>  $ids
     * @return array<int, int>
     */
    private function sanitizeOrderedIds(array $ids): array
    {
        return array_values(array_filter(
            array_map(static fn ($id) => (int) $id, $ids),
            static fn (int $id) => $id > 0
        ));
    }

    /**
     * Vrai si l'ordre reçu est une PERMUTATION EXACTE de l'ensemble attendu :
     * même cardinalité, mêmes ids, aucun en double, aucun étranger, aucun manquant.
     * C'est la garde anti-IDOR : un seul écart → faux → aucune écriture.
     *
     * @param  array<int, int>  $ordered
     * @param  array<int, int>  $expected
     */
    private function orderedIdsMatch(array $ordered, array $expected): bool
    {
        if (count($ordered) !== count($expected)) {
            return false;
        }

        if (count(array_unique($ordered)) !== count($ordered)) {
            return false; // doublon → rejet.
        }

        sort($ordered);
        sort($expected);

        return $ordered === $expected;
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
            // Vidéo : URL d'intégration ScreenPal (champ canonique lu par le lecteur),
            // affiche (URL pour l'instant), durée estimée en minutes.
            'player_url'        => 'nullable|url|max:2000',
            'poster_url'        => 'nullable|url|max:2000',
            'duration_minutes'  => 'nullable|integer|min:1|max:1440',
            'rich_text'         => 'nullable|string|max:20000',
            // Quiz : clé de banque + seuil de réussite (0-100) + tentatives (>= 1, vide = illimité).
            'qt_bank_key'       => 'nullable|string|max:120',
            'passing_score'     => 'nullable|integer|min:0|max:100',
            'attempts_allowed'  => 'nullable|integer|min:1|max:99',
            // V1-c : méthode de notation (liste blanche stricte ; vide = défaut au build).
            'grading_method'    => ['nullable', Rule::in(\Modules\Academy\Services\QuizGradeService::METHODS)],
            // QB2 : catégorie de banque (validée comme MIENNE au build) + nb à tirer (1..50).
            'bank_category_id'  => 'nullable|integer',
            'bank_draw_count'   => 'nullable|integer|min:1|max:50',
            // QB3 : inclure les sous-catégories au tirage (parité Moodle, défaut true).
            'bank_include_subcategories' => 'nullable|boolean',
            // V1-d : mélange + limite de temps + options de révision (toutes facultatives).
            'shuffle_questions'  => 'nullable|boolean',
            'shuffle_answers'    => 'nullable|boolean',
            'time_limit_minutes' => 'nullable|integer|min:1|max:240',
            'review_options'     => 'nullable|array',
        ])->validate();
    }

    /**
     * Construit le payload (array casté) propre à chaque type, de façon défensive.
     *
     *  - video    : champ CANONIQUE « player_url » (l'URL d'intégration ScreenPal lue
     *               par le lecteur public, cf. public/lesson.blade.php). On conserve
     *               external_ref (colonne) et on accepte un repli historique payload.embed
     *               si player_url est absent (rétrocompatibilité des anciens items).
     *               Ajoute « poster » (URL) et « duration_seconds » (dérivé des minutes).
     *  - document : texte riche / markdown simple (payload.rich_text).
     *  - quiz     : clé de banque QT (qt_bank_key) + passing_score + attempts_allowed,
     *               consommés côté serveur par QuizController (seuil + limite de tentatives).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildItemPayload(string $type, array $input): array
    {
        return match ($type) {
            'video'    => $this->buildVideoPayload($input),
            'document' => ['rich_text' => (string) ($input['rich_text'] ?? '')],
            'quiz'     => $this->buildQuizPayload($input),
            default    => [],
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildVideoPayload(array $input): array
    {
        // Champ canonique : player_url. Repli historique : external_ref puis payload.embed.
        $playerUrl = trim((string) ($input['player_url'] ?? ''));
        if ($playerUrl === '') {
            $playerUrl = trim((string) ($input['external_ref'] ?? ''));
        }
        if ($playerUrl === '') {
            $playerUrl = trim((string) ($input['embed'] ?? ''));
        }

        $poster   = trim((string) ($input['poster_url'] ?? ''));
        $minutes  = $input['duration_minutes'] ?? null;
        $duration = ($minutes !== null && $minutes !== '' && (int) $minutes > 0)
            ? (int) $minutes * 60
            : null;

        $payload = ['player_url' => $playerUrl !== '' ? $playerUrl : null];
        if ($poster !== '') {
            $payload['poster'] = $poster;
        }
        if ($duration !== null) {
            $payload['duration_seconds'] = $duration;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildQuizPayload(array $input): array
    {
        $payload = ['qt_bank_key' => trim((string) ($input['qt_bank_key'] ?? '')) ?: null];

        $passing = $input['passing_score'] ?? null;
        if ($passing !== null && $passing !== '') {
            $payload['passing_score'] = max(0, min(100, (int) $passing));
        }

        $attempts = $input['attempts_allowed'] ?? null;
        if ($attempts !== null && $attempts !== '') {
            $payload['attempts_allowed'] = max(1, (int) $attempts);
        }

        // V1-c : méthode de notation sur tentatives. Liste blanche stricte ; toute
        // valeur absente/inconnue retombe sur le défaut Moodle 'highest' (jamais en
        // dur dans le payload si vide → QuizGradeService::methodFor applique le défaut).
        $method = $input['grading_method'] ?? null;
        if (is_string($method)
            && in_array($method, \Modules\Academy\Services\QuizGradeService::METHODS, true)
        ) {
            $payload['grading_method'] = $method;
        }

        // QB2 : si une catégorie de banque VALIDE (m'appartenant) est choisie, on
        // l'enregistre dans payload['question_bank']. ANTI-IDOR : la catégorie doit
        // figurer dans la liste blanche de MES catégories (re-résolue serveur) ;
        // un id forgé (catégorie d'un autre formateur) est simplement ignoré → on
        // retombe sur le comportement qt_bank_key existant. Aucune nouvelle table.
        $bankCategoryId = $input['bank_category_id'] ?? null;
        if ($bankCategoryId !== null && $bankCategoryId !== '') {
            $bankCategoryId = (int) $bankCategoryId;

            if ($bankCategoryId > 0 && in_array($bankCategoryId, $this->ownedCategoryIds(), true)) {
                $draw = $input['bank_draw_count'] ?? null;
                $drawCount = ($draw !== null && $draw !== '') ? max(1, min(50, (int) $draw)) : 5;

                // QB3 : inclure les sous-catégories (parité Moodle). Défaut true si le
                // champ est absent (rétrocompat des items QB2 déjà liés). Normalise
                // toute valeur du DOM ('1'/'0'/true/false/'on'/'') en booléen.
                $rawInclude   = $input['bank_include_subcategories'] ?? null;
                $includeSubs  = $rawInclude === null
                    ? true
                    : filter_var($rawInclude, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

                $payload['question_bank'] = [
                    'category_id'          => $bankCategoryId,
                    'draw_count'           => $drawCount,
                    'include_subcategories' => $includeSubs,
                ];
            }
        }

        // V1-d — MÉLANGE : 2 booléens. N'écrits QUE si true (un item sans ces clés
        // → pas de mélange = comportement actuel inchangé, rétrocompat stricte).
        if ($this->truthy($input['shuffle_questions'] ?? null)) {
            $payload['shuffle_questions'] = true;
        }
        if ($this->truthy($input['shuffle_answers'] ?? null)) {
            $payload['shuffle_answers'] = true;
        }

        // V1-d — LIMITE DE TEMPS : minutes (1..240). Vide → aucune limite (clé absente).
        $limit = $input['time_limit_minutes'] ?? null;
        if ($limit !== null && $limit !== '') {
            $payload['time_limit_minutes'] = max(1, min(240, (int) $limit));
        }

        // V1-d — OPTIONS DE RÉVISION : on persiste UNIQUEMENT les toggles désactivés
        // (false). Défaut = tout vrai (QuizReviewOptions). Donc un item sans choix
        // explicite → aucune clé → révision complète V1-a (rétrocompat stricte).
        $rawReview = $input['review_options'] ?? null;
        if (is_array($rawReview)) {
            $review = [];
            foreach (\Modules\Academy\Services\QuizReviewOptions::KEYS as $key) {
                if (array_key_exists($key, $rawReview)) {
                    $review[$key] = (bool) (filter_var(
                        $rawReview[$key],
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    ) ?? true);
                }
            }
            if ($review !== []) {
                $payload['review_options'] = $review;
            }
        }

        return $payload;
    }

    /**
     * V1-d : normalise une valeur du DOM ('1'/'0'/true/false/'on'/'' / null) en booléen.
     */
    private function truthy(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (bool) (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
    }

    /**
     * Liste blanche des ids de catégories de banque que l'utilisateur courant peut
     * lier (anti-IDOR). Un admin (academy.manage) peut lier n'importe quelle
     * catégorie ; un formateur UNIQUEMENT les siennes (owner_id = auth). Re-résolu
     * serveur à chaque build, jamais une valeur du navigateur.
     *
     * @return array<int, int>
     */
    private function ownedCategoryIds(): array
    {
        $user = Auth::user();

        $query = QuestionCategory::query();
        if (! ($user?->can('academy.manage'))) {
            $query->where('owner_id', $user?->id);
        }

        return $query->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // V1-a : FEEDBACK GLOBAL PAR TRANCHE DE SCORE (item quiz) - gâté manageStructure
    //
    // SÉCURITÉ : resolveCourse() → authorize('manageStructure') → resolveItemFor()
    // (anti-IDOR : l'item doit appartenir à CE cours) → normalisation/validation des
    // bornes (QuizFeedbackService) → écriture dans payload['overall_feedback'].
    // Une liste vide efface la clé (rétrocompat : pas de clé parasite).
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Charge dans $overallFeedback[item] les bornes existantes d'un item (édition).
     * Re-résout l'item scopé à CE cours (anti-IDOR). Ajoute une ligne vide si aucune,
     * pour que l'UI propose toujours un point de départ.
     */
    public function loadOverallFeedback(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $rows = [];
        foreach ((array) ($item->payload['overall_feedback'] ?? []) as $row) {
            if (is_array($row) && isset($row['message'])) {
                $rows[] = [
                    'min_percent' => (int) ($row['min_percent'] ?? 0),
                    'message'     => (string) $row['message'],
                ];
            }
        }

        if ($rows === []) {
            $rows[] = ['min_percent' => 80, 'message' => ''];
        }

        $this->overallFeedback[$itemId] = $rows;
    }

    public function addOverallBoundary(int $itemId): void
    {
        if (! isset($this->overallFeedback[$itemId])) {
            $this->overallFeedback[$itemId] = [];
        }

        if (count($this->overallFeedback[$itemId]) >= \Modules\Academy\Services\QuizFeedbackService::MAX_BOUNDARIES) {
            return; // garde-fou : pas plus que le maximum autorisé.
        }

        $this->overallFeedback[$itemId][] = ['min_percent' => 0, 'message' => ''];
    }

    public function removeOverallBoundary(int $itemId, int $index): void
    {
        if (! isset($this->overallFeedback[$itemId][$index])) {
            return;
        }

        unset($this->overallFeedback[$itemId][$index]);
        $this->overallFeedback[$itemId] = array_values($this->overallFeedback[$itemId]);
    }

    /**
     * Enregistre le feedback global d'un item quiz. La liste est NORMALISÉE/validée
     * par QuizFeedbackService (seuils 0..100, messages bornés, dédoublonnage, tri DESC,
     * max bornes). Une liste vide retire la clé (rétrocompat).
     */
    public function saveOverallFeedback(int $itemId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $item = $this->resolveItemFor($course, $itemId);

        $clean = \Modules\Academy\Services\QuizFeedbackService::normalizeBoundaries(
            $this->overallFeedback[$itemId] ?? []
        );

        $payload = is_array($item->payload) ? $item->payload : [];
        if ($clean === []) {
            unset($payload['overall_feedback']);
        } else {
            $payload['overall_feedback'] = $clean;
        }

        $item->update(['payload' => $payload]);

        // Reflète l'état normalisé dans l'UI (tri/dédoublonnage visibles immédiatement).
        $this->overallFeedback[$itemId] = $clean !== []
            ? $clean
            : [['min_percent' => 80, 'message' => '']];

        $this->flashSaved('Rétroaction globale du quiz enregistrée.');
    }

    /**
     * Catégories de banque liables par l'utilisateur (affichage du sélecteur dans le
     * formulaire d'item quiz). Même périmètre owner-scopé que ownedCategoryIds()
     * (sert l'AFFICHAGE ; l'autorisation reste serveur au build du payload).
     *
     * @return \Illuminate\Support\Collection<int, QuestionCategory>
     */
    #[Computed]
    public function bankCategories(): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        $query = QuestionCategory::query()
            ->withCount('children')
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name');

        if (! ($user?->can('academy.manage'))) {
            $query->where('owner_id', $user?->id);
        }

        $categories = $query->get();

        // QB3 : compteur RÉEL de questions ACTIVES incluant la descendance (parité
        // Moodle). Affiché « (N questions, sous-catégories incluses) » si la catégorie
        // a des enfants, « (N questions) » sinon. Reste owner-scopé via descendantIds().
        return $categories->each(function (QuestionCategory $cat): void {
            $cat->deep_active_count = $cat->activeQuestionCountDeep();
        });
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

    /**
     * Slug public d'un certificat DÉJÀ ÉMIS pour ce cours (s'il en existe un), pour
     * offrir un lien « Prévisualiser le certificat » qui montre le rendu réel avec la
     * personnalisation appliquée. Null si aucun certificat n'a encore été décerné :
     * dans ce cas le lien d'aperçu n'est tout simplement pas affiché (la prévisu se
     * fait alors via les valeurs saisies, déjà reflétées au prochain certificat émis).
     * Lecture seule, scopée au cours courant (aucune fuite : on n'expose qu'un slug
     * déjà public et vérifiable).
     */
    #[Computed]
    public function sampleCertificateSlug(): ?string
    {
        return CertificateIssued::where('course_id', $this->courseId)
            ->latest('id')
            ->value('public_url_slug');
    }

    /**
     * Id de la 1re leçon du cours (chapitres puis leçons triés par position),
     * ou null si le cours n'a encore aucune leçon. Sert au bouton « Prévisualiser
     * en tant qu'étudiant » : si une leçon existe, on ouvre le lecteur en preview ;
     * sinon, on retombe sur la fiche du cours en preview (géré côté vue).
     */
    #[Computed]
    public function firstLessonId(): ?int
    {
        foreach ($this->course->chapters as $chapter) {
            $lesson = $chapter->lessons->first();
            if ($lesson !== null) {
                return (int) $lesson->id;
            }
        }

        return null;
    }

    /**
     * Rendu d'aperçu SÛR d'un contenu document (markdown) DANS l'éditeur, via le
     * MÊME helper que le lecteur public (LessonItem::renderRichText) : l'aperçu ne
     * diverge donc JAMAIS du rendu final (pas de markdown JS côté client). Le HTML
     * retourné est déjà nettoyé (html_input=strip) → aucune XSS possible.
     */
    public function previewRichText(string $raw): string
    {
        return LessonItem::renderRichText($raw);
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
        $this->is_template = (bool) $course->is_template;

        // Personnalisation du certificat (E3) : valeurs courantes ou null (défaut gabarit).
        $this->certificate_title          = $course->certificate_title;
        $this->certificate_message        = $course->certificate_message;
        $this->certificate_signature_name = $course->certificate_signature_name;
        $this->certificate_accent_color   = $course->certificate_accent_color;
    }

    public function render()
    {
        return view('academy::livewire.course-editor');
    }
}
