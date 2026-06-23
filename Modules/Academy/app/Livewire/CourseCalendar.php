<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Calendrier d'échéances d'un cours - V5-b. Composant Livewire role-aware.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR) :
 *  - L'identifiant du cours est figé au montage ($courseId, source de vérité).
 *  - Les étudiants inscrits : accès LECTURE SEULE (voir les événements).
 *  - Les gérants (manageStructure = admin OU owner/instructor/editor) :
 *    CRUD des événements manuels. Chaque mutation :
 *      a) ré-résout le cours via resolveCourse() (jamais confiance au state)
 *      b) ré-autorise via $this->authorize('manageStructure', $course)
 *      c) scope CalendarEvent au course_id du cours ré-résolu (anti-IDOR)
 *  - Validation SERVEUR : type sur liste blanche, titre borné,
 *    ends_at >= starts_at.
 *  - Jamais de popup native (confirm/alert/prompt) : confirmation inline
 *    à 2 temps via $confirmingRemoval.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\CalendarEvent;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\CalendarService;

class CourseCalendar extends Component
{
    /** Identifiant du cours (figé au montage - source de vérité serveur). */
    #[Locked]
    public int $courseId;

    // ── Formulaire CRUD d'événements manuels ─────────────────────────────────
    /** Id de l'événement en cours d'édition, null = création. */
    public ?int $editingEvent = null;
    public bool $showForm     = false;

    public string $evTitle       = '';
    public string $evDescription = '';
    public string $evType        = 'manual';
    public string $evStartsAt    = '';
    public string $evEndsAt      = '';

    // ── Confirmation inline à 2 temps (jamais de popup native) ──────────────
    public ?int $confirmingRemoval = null;

    // -------------------------------------------------------------------------
    // Montage (autorisation d'entree)
    // -------------------------------------------------------------------------

    /**
     * Accès : inscrit actif OU gérant du cours. Abort 403 sinon.
     */
    public function mount(Course $course): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        // Gérant (admin OU rôle de cours) : accès direct.
        if ($user->can('manageStructure', $course)) {
            $this->courseId = $course->id;
            return;
        }

        // Sinon : inscription active obligatoire (étudiant).
        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403);

        $this->courseId = $course->id;
    }

    // -------------------------------------------------------------------------
    // Propriétés calculées
    // -------------------------------------------------------------------------

    /**
     * Cours résolu (lecture seule, cache Livewire).
     */
    #[Computed]
    public function course(): Course
    {
        return Course::findOrFail($this->courseId);
    }

    /**
     * Événements fusionnés (manuels + dérivés), triés par date.
     * Toujours calculé serveur.
     */
    #[Computed]
    public function events(): \Illuminate\Support\Collection
    {
        return (new CalendarService())->forCourse($this->course);
    }

    /**
     * L'utilisateur courant peut-il gérer les événements ?
     * Cache Livewire : recalcule si le cours change (impossible ici car courseId
     * est figé - mais pattern défensif).
     */
    #[Computed]
    public function canManage(): bool
    {
        return (bool) Auth::user()?->can('manageStructure', $this->course);
    }

    // -------------------------------------------------------------------------
    // Actions CRUD (gérant uniquement, chaque action ré-autorise)
    // -------------------------------------------------------------------------

    /**
     * Ouvre le formulaire de création d'un événement manuel.
     */
    public function openCreate(): void
    {
        $this->authorize('manageStructure', $this->resolveCourse());
        $this->resetForm();
        $this->editingEvent = null;
        $this->showForm     = true;
    }

    /**
     * Ouvre le formulaire d'édition d'un événement existant.
     * L'événement est ré-résolu et scopé au cours (anti-IDOR) : si l'id n'appartient
     * pas à ce cours, on retourne silencieusement (rien n'est écrit, rien ne fuit).
     */
    public function openEdit(int $eventId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $ev = CalendarEvent::forCourse($course->id)->find($eventId);
        if ($ev === null) {
            return; // Événement inconnu dans ce cours : silently reject (anti-IDOR).
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
     * Enregistre un événement (création ou mise à jour).
     * Validation serveur obligatoire avant toute écriture.
     */
    public function save(): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $data = $this->validateForm();

        if ($this->editingEvent !== null) {
            // Mise à jour : event ré-résolu et scopé au cours (anti-IDOR).
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

        session()->flash('calendar_status', 'Événement enregistré.');
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
     * Supprime (soft-delete) un événement (2e clic). Ré-résolution + ré-autorisation.
     * Si l'id n'appartient pas à ce cours, on retourne silencieusement (anti-IDOR).
     */
    public function remove(int $eventId): void
    {
        $course = $this->resolveCourse();
        $this->authorize('manageStructure', $course);

        $ev = CalendarEvent::forCourse($course->id)->find($eventId);
        if ($ev === null) {
            $this->confirmingRemoval = null;
            return; // Événement inconnu dans ce cours : silently reject (anti-IDOR).
        }
        $ev->delete();

        $this->confirmingRemoval = null;
        unset($this->events);

        session()->flash('calendar_status', 'Événement supprimé.');
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
     * Valide les champs du formulaire et retourne les données normalisées.
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

    /** Remet à zéro les champs du formulaire et l'état de confirmation. */
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
     * Ré-résout le cours depuis la base (jamais confiance au state client).
     * Source unique de vérité pour les mutations.
     */
    private function resolveCourse(): Course
    {
        return Course::findOrFail($this->courseId);
    }
}
