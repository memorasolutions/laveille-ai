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
use Modules\Academy\Livewire\Concerns\HandlesChapters;
use Modules\Academy\Livewire\Concerns\HandlesCourseReordering;
use Modules\Academy\Livewire\Concerns\HandlesCourseSettings;
use Modules\Academy\Livewire\Concerns\HandlesH5p;
use Modules\Academy\Livewire\Concerns\HandlesItemMedia;
use Modules\Academy\Livewire\Concerns\HandlesItems;
use Modules\Academy\Livewire\Concerns\HandlesDatabaseEditor;
use Modules\Academy\Livewire\Concerns\HandlesFeedbackEditor;
use Modules\Academy\Livewire\Concerns\HandlesItemRestrictions;
use Modules\Academy\Livewire\Concerns\HandlesLessons;
use Modules\Academy\Livewire\Concerns\HandlesOverallFeedback;
use Modules\Academy\Livewire\Concerns\HandlesWorkshopEditor;

class CourseEditor extends Component
{
    use WithFileUploads;
    use HandlesChapters;
    use HandlesCourseReordering;
    use HandlesCourseSettings;
    use HandlesH5p;
    use HandlesItemMedia;
    use HandlesItems;
    use HandlesItemRestrictions;
    use HandlesLessons;
    use HandlesFeedbackEditor;
    use HandlesDatabaseEditor;
    use HandlesOverallFeedback;
    use HandlesWorkshopEditor;

    /** Types d'items autorisés (liste blanche, alignée sur l'admin Backoffice). */
    private const ITEM_TYPES = ['video', 'document', 'quiz', 'choice', 'feedback', 'forum', 'wiki', 'database', 'workshop', 'h5p'];

    /** Taille maximale (Ko) de l'image de couverture (~4 Mo) - validée côté SERVEUR. */
    private const COVER_MAX_KB = 4096;

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

    // ── Tuteur IA : fenêtre d'accès + quota (recommandation veille juillet 2026) ─
    // Visible/actionnable uniquement si academy.ai_tutor_access_control_enabled
    // est activé (voir Concerns\HandlesCourseSettings::saveAiTutorAccess). Modifier
    // ces réglages n'affecte JAMAIS un grant déjà figé pour un apprenant déjà
    // inscrit (voir TutorAccessService) — uniquement les NOUVELLES inscriptions.
    public string $ai_tutor_window_type = 'none';
    public ?int $ai_tutor_window_days = null;
    public ?string $ai_tutor_fixed_expiry_at = null;
    public ?int $ai_tutor_monthly_quota = null;
    public ?int $ai_tutor_reminder_days_before = 7;

    // ── Achèvement du cours (course completion configurable) ────────────────────
    // Critère décidant quand le cours est COMPLÉTÉ (certificat + badges). Défaut
    // « all_required » = comportement historique. UN seul critère actif à la fois.
    public string $completion_type = 'all_required';

    /** Seuil X (1..100) pour les critères percent / min_grade. */
    public ?int $completion_value = 80;

    /** Ids des items DÉSIGNÉS pour le critère selected_activities (anti-IDOR à l'écriture). */
    public array $completion_selected = [];

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
     * FEEDBACK : tampon d'édition d'un item « feedback », indexé par item_id. Chaque
     * entrée = { title, intro, anonymous, completion, estimated_minutes, questions[] }.
     * Édité par des actions dédiées (chargement, ajout/retrait de question,
     * enregistrement), à la manière du feedback global : un répéteur de questions ne se
     * prête pas au $event.target inline du formulaire générique.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $editFeedback = [];

    /**
     * F20 - BASE DE DONNÉES : tampon d'édition du SCHÉMA d'un item « database », indexé par
     * item_id. Chaque entrée = { title, intro, allow_student_add, require_approval,
     * completion, estimated_minutes, fields[] }. Édité par des actions dédiées (chargement,
     * ajout/retrait de champ, enregistrement) : un répéteur de champs (le schéma) ne se
     * prête pas au $event.target inline du formulaire générique, comme la rétroaction.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $editDatabase = [];

    /**
     * F21 - ATELIER : tampon d'édition d'un item « workshop » indexé par item_id (titre,
     * intro, reviews_per_student, anonymous, grille de critères). Comme la base de données,
     * un répéteur de critères ne se prête pas au $event.target inline : actions dédiées.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $editWorkshop = [];

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

    /**
     * F16 - Paquet .h5p en attente pour un NOUVEL item, indexé par lesson_id
     * (TemporaryUploadedFile). Sa présence + un titre déclenchent addH5pItem().
     */
    public array $newH5p = [];

    /**
     * F16 - Paquet .h5p en attente pour REMPLACER le contenu d'un item h5p existant,
     * indexé par item_id (TemporaryUploadedFile).
     */
    public array $itemH5p = [];

    /**
     * V5-d : restrictions d'accès par item (tampon d'édition Livewire).
     * Structure : [item_id => ['match' => 'all'|'any', 'conditions' => [...]]]
     * null = panneau fermé (non chargé) ; tableau = en cours d'édition.
     *
     * @var array<int, array{match: string, conditions: array<int, array<string, mixed>>}|null>
     */
    public array $editRestrictions = [];

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

        // Tuteur IA — fenêtre d'accès + quota : valeurs courantes du cours.
        $this->ai_tutor_window_type          = (string) ($course->ai_tutor_window_type ?? 'none');
        $this->ai_tutor_window_days          = $course->ai_tutor_window_days;
        $this->ai_tutor_fixed_expiry_at      = $course->ai_tutor_fixed_expiry_at?->format('Y-m-d');
        $this->ai_tutor_monthly_quota        = $course->ai_tutor_monthly_quota;
        $this->ai_tutor_reminder_days_before = $course->ai_tutor_reminder_days_before ?? 7;

        // Achèvement du cours : critère normalisé (défaut all_required si NULL).
        $criteria                  = $course->completionCriteria();
        $this->completion_type     = $criteria['type'];
        $this->completion_value    = $criteria['value'] ?? 80;
        $this->completion_selected = array_map('intval', $criteria['items']);
    }

    public function render()
    {
        return view('academy::livewire.course-editor');
    }
}
