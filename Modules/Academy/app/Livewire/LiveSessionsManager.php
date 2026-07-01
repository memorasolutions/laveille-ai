<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Séances en direct / visioconférence natives - GÉRANT (CRUD formateur).
 * Rendu dans l'éditeur de cours sous @can('manageStructure').
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR, anti-IDOR) :
 *  - Le cours est re-résolu serveur (binding) ; ENTRÉE gâtée manageStructure (mount).
 *  - Le drapeau academy.live_sessions_enabled est re-vérifié à l'entrée ET à chaque
 *    mutation (une action Livewire est un endpoint public : elle contourne le
 *    middleware de la route, donc on ne fait JAMAIS confiance au montage seul).
 *  - Chaque mutation RÉ-AUTORISE manageStructure sur CE cours ; la séance ciblée
 *    est RE-RÉSOLUE scopée au cours (une séance d'un autre cours est refusée) ;
 *    la cohorte éventuelle est RE-SCOPÉE au cours (anti-IDOR).
 *  - Heures : saisies en heure du Québec (America/Toronto), converties en UTC
 *    pour le stockage. L'affichage remet le Québec d'abord (UTC entre parenthèses).
 *  - @can en Blade = affichage ; l'autorisation reste SERVEUR.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\Cohort;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\LiveSession;

class LiveSessionsManager extends Component
{
    /** Fuseau de saisie/affichage (heure du Québec). */
    private const TZ = 'America/Toronto';

    /** Cours lié. Verrouillé : le navigateur ne peut pas substituer un autre cours. */
    #[Locked]
    public Course $course;

    // Champs du formulaire (création/édition).
    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $provider = 'meet';

    public string $join_url = '';

    /** Date/heure de début, en heure du Québec (format « Y-m-d\TH:i » du input datetime-local). */
    public string $starts_at = '';

    /** Date/heure de fin (facultative), en heure du Québec. */
    public string $ends_at = '';

    public ?int $cohort_id = null;

    /** ID de la séance en attente de confirmation de suppression (modale inline, jamais de popup natif). */
    public ?int $confirmingDeleteId = null;

    public function mount(Course $course): void
    {
        // ENTRÉE : le cours est re-résolu serveur ; on autorise manageStructure.
        $this->authorize('manageStructure', $course);
        abort_unless($this->featureEnabled(), 404);
        $this->course = $course;
    }

    /** Drapeau maître de la fonctionnalité (défaut FALSE). */
    private function featureEnabled(): bool
    {
        return (bool) config('academy.live_sessions_enabled', false);
    }

    /** Séances du cours, à venir d'abord, puis passées (les plus récentes en tête). */
    #[Computed]
    public function sessions()
    {
        return LiveSession::query()
            ->where('course_id', $this->course->id)
            ->orderByDesc('starts_at')
            ->get();
    }

    /** Cohortes du cours (pour restreindre une séance à un groupe). */
    #[Computed]
    public function cohorts()
    {
        return Cohort::query()
            ->where('course_id', $this->course->id)
            ->orderBy('name')
            ->get();
    }

    /** Libellés de fournisseurs (Google Meet en tête pour l'UI). */
    #[Computed]
    public function providerLabels(): array
    {
        return LiveSession::PROVIDER_LABELS;
    }

    /** Règles de validation communes (URL valide, date future à la création, fournisseur). */
    private function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            // meta.google.com/... doit passer : url standard, aucun rejet de domaine.
            'provider'    => ['required', 'in:' . implode(',', LiveSession::PROVIDERS)],
            'join_url'    => ['required', 'url', 'max:2048'],
            'starts_at'   => ['required', 'date'],
            'ends_at'     => ['nullable', 'date', 'after:starts_at'],
            'cohort_id'   => ['nullable', 'integer'],
        ];
    }

    /** Convertit une saisie « heure Québec » (datetime-local) en Carbon UTC pour le stockage. */
    private function toUtc(string $localValue): Carbon
    {
        return Carbon::parse($localValue, self::TZ)->utc();
    }

    /** Prépare le formulaire pour une nouvelle séance (défauts sûrs). */
    public function newSession(): void
    {
        $this->authorize('manageStructure', $this->course);
        abort_unless($this->featureEnabled(), 404);

        $this->reset(['editingId', 'title', 'description', 'join_url', 'starts_at', 'ends_at', 'cohort_id']);
        $this->provider = 'meet';
    }

    /** Charge une séance existante dans le formulaire (re-scopée au cours, anti-IDOR). */
    public function edit(int $id): void
    {
        $this->authorize('manageStructure', $this->course);
        abort_unless($this->featureEnabled(), 404);

        $session = $this->resolveSession($id);

        $this->editingId   = $session->id;
        $this->title       = $session->title;
        $this->description = (string) $session->description;
        $this->provider    = $session->provider;
        $this->join_url    = $session->join_url;
        // On repasse en heure du Québec pour l'affichage dans le champ datetime-local.
        $this->starts_at = $session->starts_at->setTimezone(self::TZ)->format('Y-m-d\TH:i');
        $this->ends_at   = $session->ends_at?->setTimezone(self::TZ)->format('Y-m-d\TH:i') ?? '';
        $this->cohort_id = $session->cohort_id;
    }

    /** Crée ou met à jour la séance (validation + conversion Québec -> UTC). */
    public function save(): void
    {
        $this->authorize('manageStructure', $this->course);
        abort_unless($this->featureEnabled(), 404);

        $data = $this->validate($this->rules());

        // La date de début doit être future à la CRÉATION (une séance passée n'est planifiée que par édition).
        if ($this->editingId === null && $this->toUtc($data['starts_at'])->isPast()) {
            $this->addError('starts_at', 'La date de début doit être dans le futur.');

            return;
        }

        // La cohorte, si fournie, DOIT appartenir à CE cours (anti-IDOR).
        $cohortId = null;
        if (! empty($data['cohort_id'])) {
            $cohortId = Cohort::query()
                ->whereKey($data['cohort_id'])
                ->where('course_id', $this->course->id)
                ->value('id');

            if ($cohortId === null) {
                $this->addError('cohort_id', 'Cohorte invalide pour ce cours.');

                return;
            }
        }

        $payload = [
            'course_id'   => $this->course->id,
            'cohort_id'   => $cohortId,
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'provider'    => $data['provider'],
            'join_url'    => $data['join_url'],
            'starts_at'   => $this->toUtc($data['starts_at']),
            'ends_at'     => ! empty($data['ends_at']) ? $this->toUtc($data['ends_at']) : null,
        ];

        if ($this->editingId !== null) {
            // Séance re-résolue scopée au cours (anti-IDOR) avant mise à jour.
            $session = $this->resolveSession($this->editingId);
            $session->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            LiveSession::create($payload);
        }

        $this->newSession();
        unset($this->sessions);
        $this->dispatch('live-session-saved');
    }

    /** Arme la confirmation inline de suppression (aucun popup navigateur natif). */
    public function confirmDelete(int $id): void
    {
        $this->authorize('manageStructure', $this->course);
        $this->confirmingDeleteId = $id;
    }

    /** Annule la confirmation de suppression. */
    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    /** Supprime une séance (re-résolue scopée au cours, anti-IDOR). */
    public function deleteSession(int $id): void
    {
        $this->authorize('manageStructure', $this->course);
        abort_unless($this->featureEnabled(), 404);

        $this->resolveSession($id)->delete();

        $this->confirmingDeleteId = null;
        if ($this->editingId === $id) {
            $this->newSession();
        }
        unset($this->sessions);
        $this->dispatch('live-session-saved');
    }

    /** Re-résout une séance SCOPÉE à CE cours. ModelNotFound sinon (anti-IDOR). */
    private function resolveSession(int $id): LiveSession
    {
        return LiveSession::query()
            ->whereKey($id)
            ->where('course_id', $this->course->id)
            ->firstOrFail();
    }

    public function render()
    {
        return view('academy::livewire.live-sessions-manager');
    }
}
