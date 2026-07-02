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
 *
 * AUTO-CRÉATION DU LIEN MEET (phase 2) :
 *  - Gâtée par academy.google_meet_autocreate_enabled ET GoogleMeetService::isConfigured()
 *    (identifiants Google présents) ; re-vérifiée dans save() (jamais confiance au
 *    seul affichage de la case). $generateMeetLink est réinitialisé à chaque
 *    newSession()/edit() (jamais persistant entre deux formulaires).
 *  - Si le drapeau est OFF, le service non configuré, ou l'appel Google échoue :
 *    repli EXACTEMENT identique à aujourd'hui (le formateur colle son lien
 *    manuellement dans join_url) — AUCUNE régression, voir GoogleMeetService.
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
use Modules\Academy\Services\GoogleMeetService;

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

    /**
     * Case « Générer automatiquement le lien Meet » (phase 2). Réinitialisée à
     * chaque newSession()/edit() ; re-vérifiée serveur dans save() (jamais
     * confiance au seul état du formulaire — voir GoogleMeetService).
     */
    public bool $generateMeetLink = false;

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

    /**
     * Vrai si la case « Générer automatiquement le lien Meet » doit être
     * proposée dans le formulaire (drapeau actif ET identifiants configurés).
     * Défaut false : n'affiche rien tant que le service n'est pas configuré
     * (comportement du champ manuel inchangé).
     */
    #[Computed]
    public function canAutoCreateMeet(): bool
    {
        return app(GoogleMeetService::class)->isConfigured();
    }

    /** Règles de validation communes (URL valide, date future à la création, fournisseur). */
    private function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            // meta.google.com/... doit passer : url standard, aucun rejet de domaine.
            'provider'    => ['required', 'in:' . implode(',', LiveSession::PROVIDERS)],
            // join_url reste OBLIGATOIRE sauf quand l'auto-génération Meet est
            // effectivement demandée ET disponible : l'URL n'est alors connue
            // qu'APRÈS l'appel Google (voir save()). Si le service est absent/
            // désactivé, la règle « required » standard s'applique (inchangé).
            'join_url'    => [$this->willAutoCreateMeet() ? 'nullable' : 'required', 'nullable', 'url', 'max:2048'],
            'starts_at'   => ['required', 'date'],
            'ends_at'     => ['nullable', 'date', 'after:starts_at'],
            'cohort_id'   => ['nullable', 'integer'],
        ];
    }

    /**
     * Vrai si CETTE soumission doit déclencher l'auto-création du lien Meet :
     * case cochée, fournisseur = meet, ET le service est réellement configuré
     * (re-vérifié serveur, jamais confiance au seul état du formulaire).
     */
    private function willAutoCreateMeet(): bool
    {
        return $this->generateMeetLink
            && $this->provider === 'meet'
            && $this->canAutoCreateMeet;
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

        $this->reset(['editingId', 'title', 'description', 'join_url', 'starts_at', 'ends_at', 'cohort_id', 'generateMeetLink']);
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
        // Édition d'une séance existante : jamais de régénération auto par défaut
        // (le lien déjà enregistré n'est pas remplacé sans action explicite).
        $this->generateMeetLink = false;
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

        $startsAtUtc = $this->toUtc($data['starts_at']);
        $endsAtUtc   = ! empty($data['ends_at']) ? $this->toUtc($data['ends_at']) : $startsAtUtc->copy()->addHour();

        $joinUrl = $data['join_url'] ?: null;

        // Auto-création du lien Meet (phase 2) : re-vérifiée serveur (jamais
        // confiance au seul état du formulaire). Échec/non configuré => repli
        // IDENTIQUE à aujourd'hui : join_url manuel si fourni, sinon erreur de
        // validation normale (le champ redevient requis côté utilisateur).
        if ($this->willAutoCreateMeet() && empty($joinUrl)) {
            $joinUrl = app(GoogleMeetService::class)->createMeetLink($data['title'], $startsAtUtc, $endsAtUtc);

            if (empty($joinUrl)) {
                $this->addError('join_url', "La génération automatique du lien Meet a échoué. Collez le lien manuellement ou réessayez.");

                return;
            }
        }

        $payload = [
            'course_id'   => $this->course->id,
            'cohort_id'   => $cohortId,
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'provider'    => $data['provider'],
            'join_url'    => $joinUrl,
            'starts_at'   => $startsAtUtc,
            'ends_at'     => ! empty($data['ends_at']) ? $endsAtUtc : null,
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
