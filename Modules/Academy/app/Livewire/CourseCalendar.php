<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Calendrier d'echeances d'un cours - V5-b. Composant Livewire role-aware.
 *
 * MODELE DE SECURITE (OWASP A01, autorisation SERVEUR) :
 *  - L'identifiant du cours est fige au montage ($courseId, source de verite).
 *  - Les etudiants inscrits : acces LECTURE SEULE (voir les evenements).
 *  - Les gerants (manageStructure = admin OU owner/instructor/editor) :
 *    CRUD des evenements manuels. Chaque mutation :
 *      a) re-resout le cours via resolveCourse() (jamais confiance au state)
 *      b) re-autorise via $this->authorize('manageStructure', $course)
 *      c) scope CalendarEvent au course_id du cours re-resolu (anti-IDOR)
 *  - Validation SERVEUR : type sur liste blanche, titre borne,
 *    ends_at >= starts_at.
 *  - Jamais de popup native (confirm/alert/prompt) : confirmation inline
 *    a 2 temps via $confirmingRemoval.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\CalendarEvent;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\CalendarService;

class CourseCalendar extends Component
{
    /** Identifiant du cours (fige au montage - source de verite serveur). */
    public int $courseId;

    // ── Formulaire CRUD d'evenements manuels ─────────────────────────────────
    /** Id de l'evenement en cours d'edition, null = creation. */
    public ?int $editingEvent = null;
    public bool $showForm     = false;

    public string $evTitle       = '';
    public string $evDescription = '';
    public string $evType        = 'manual';
    public string $evStartsAt    = '';
    public string $evEndsAt      = '';

    // ── Confirmation inline a 2 temps (jamais de popup native) ───────────────
    public ?int $confirmingRemoval = null;

    // -------------------------------------------------------------------------
    // Montage (autorisation d'entree)
    // -------------------------------------------------------------------------

    /**
     * Acces : inscrit actif OU gerant du cours. Abort 403 sinon.
     */
    public function mount(Course $course): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        // Gerant (admin OU role de cours) : acces direct.
        if ($user->can('manageStructure', $course)) {
            $this->courseId = $course->id;
            return;
        }

        // Sinon : inscription active obligatoire (etudiant).
        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403);

        $this->courseId = $course->id;
    }

    // -------------------------------------------------------------------------
    // Proprietes calculees
    // -------------------------------------------------------------------------

    /**
     * Cours resolu (lecture seule, cache Livewire).
     */
    #[Computed]
    public function course(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Evenements fusionnes (manuels + derives), tries par date.
     * Toujours calcule serveur.
     */
    #[Computed]
    public function events(): \Illuminate\Support\Collection
    {
        return (new CalendarService())->forCourse($this->course);
    }

    /**
     * L'utilisateur courant peut-il gerer les evenements ?
     * Cache Livewire : recalcule si le cours change (impossible ici car courseId
     * est fige - mais pattern defensif).
     */
    #[Computed]
    public function canManage(): bool
    {
        return (bool) Auth::user()?->can('manageStructure', $this->course);
    }

    // -------------------------------------------------------------------------
    // Actions CRUD (gerant uniquement, chaque action re-autorise)
    // -------------------------------------------------------------------------

    /**
     * Ouvre le formulaire de creation d'un evenement manuel.
     */
    public function openCreate(): void
    {
        $this->authorize('manageStructure', $this->resolveCourse());
        $this->resetForm();
        $this->editingEvent = null;
        $this->showForm     = true;
    }

    /**
     * Ouvre le formulaire d'edition d'un evenement existant.
     * L'evenement est re-resolu scope au cours (anti-IDOR) : si l'id n'appartient
     * pas a ce cours, on retourne silencieusement (rien n'est ecrit, rien ne fuit).
     */
    public function openEdit(int $eventId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $ev = CalendarEvent::forCourse($course->id)->find($eventId);
        if ($ev === null) {
            return; // Evenement inconnu dans ce cours : silently reject (anti-IDOR).
        }

        $this->editingEvent  = $ev->id;
        $this->evTitle       = $ev->title;
        $this->evDescription = $ev->description ?? '';
        $this->evType        = $ev->type;
        $this->evStartsAt    = $ev->starts_at->format('Y-m-d\TH:i');
        $this->evEndsAt      = $ev->ends_at ? $ev->ends_at->format('Y-m-d\TH:i') : '';
        $this->showForm      = true;
    }

    /**
     * Enregistre un evenement (creation ou mise a jour).
     * Validation serveur obligatoire avant toute ecriture.
     */
    public function save(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $data = $this->validateForm();

        if ($this->editingEvent !== null) {
            // Mise a jour : event re-resolu et scope au cours (anti-IDOR).
            $ev = CalendarEvent::forCourse($course->id)->findOrFail($this->editingEvent);
            $ev->update($data);
        } else {
            CalendarEvent::create(array_merge($data, [
                'course_id'  => $course->id,
                'created_by' => Auth::id(),
            ]));
        }

        $this->resetForm();
        $this->showForm = false;

        // Invalide le cache Livewire (computed $events).
        unset($this->events);

        session()->flash('calendar_status', 'Evenement enregistre.');
    }

    /**
     * Demande de confirmation de suppression (1er clic - inline, jamais confirm()).
     */
    public function confirmRemove(int $eventId): void
    {
        $this->authorize('manageStructure', $this->resolveCourse());
        $this->confirmingRemoval = $eventId;
    }

    /** Annule la confirmation de suppression. */
    public function cancelRemove(): void
    {
        $this->confirmingRemoval = null;
    }

    /**
     * Supprime (soft-delete) un evenement (2e clic). Re-resolution + re-autorisation.
     * Si l'id n'appartient pas a ce cours, on retourne silencieusement (anti-IDOR).
     */
    public function remove(int $eventId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $ev = CalendarEvent::forCourse($course->id)->find($eventId);
        if ($ev === null) {
            $this->confirmingRemoval = null;
            return; // Evenement inconnu dans ce cours : silently reject (anti-IDOR).
        }
        $ev->delete();

        $this->confirmingRemoval = null;
        unset($this->events);

        session()->flash('calendar_status', 'Evenement supprime.');
    }

    /** Annule la saisie du formulaire. */
    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    // -------------------------------------------------------------------------
    // Rendu
    // -------------------------------------------------------------------------

    public function render()
    {
        return view('academy::livewire.course-calendar');
    }

    // -------------------------------------------------------------------------
    // Utilitaires prives
    // -------------------------------------------------------------------------

    /**
     * Valide les champs du formulaire et retourne les donnees normalisees.
     * La validation est serveur (jamais via JS).
     *
     * @return array<string, mixed>
     */
    private function validateForm(): array
    {
        $raw = $this->validate([
            'evTitle'       => ['required', 'string', 'min:2', 'max:200'],
            'evDescription' => ['nullable', 'string', 'max:1000'],
            'evType'        => ['required', 'string', 'in:' . implode(',', CalendarEvent::TYPES)],
            'evStartsAt'    => ['required', 'date'],
            'evEndsAt'      => ['nullable', 'date', 'after_or_equal:evStartsAt'],
        ]);

        return [
            'title'       => $raw['evTitle'],
            'description' => $raw['evDescription'] ?: null,
            'type'        => $raw['evType'],
            'starts_at'   => $raw['evStartsAt'],
            'ends_at'     => $raw['evEndsAt'] ?: null,
        ];
    }

    /** Remet a zero les champs du formulaire et l'etat de confirmation. */
    private function resetForm(): void
    {
        $this->editingEvent    = null;
        $this->evTitle         = '';
        $this->evDescription   = '';
        $this->evType          = 'manual';
        $this->evStartsAt      = '';
        $this->evEndsAt        = '';
        $this->confirmingRemoval = null;
    }

    /**
     * Re-resout le cours depuis la base (jamais confiance au state client).
     * Source unique de verite pour les mutations.
     */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }
}
