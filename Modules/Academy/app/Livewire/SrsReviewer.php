<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Session de révision espacée (SRS). Présente les cartes DUES de l'utilisateur
 * courant, une à la fois (esprit DeckPlayer) : recto -> révélation du verso ->
 * auto-évaluation (Facile / Correct / Difficile / À revoir) mappée vers une
 * qualité SM-2, appliquée côté SERVEUR par SrsService::review().
 *
 * Sécurité (OWASP A01, anti-IDOR) : chaque action recharge la file des cartes
 * DUES scopée à auth()->id() ; on ne révise QUE des cartes de l'utilisateur
 * courant. Un cardId hors de cette file est ignoré (jamais la carte d'autrui).
 * Gardé par le drapeau academy.srs_enabled (page inaccessible/vide si off).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\SrsCard;
use Modules\Academy\Services\SrsService;

class SrsReviewer extends Component
{
    /** Recto révélé (verso visible) pour la carte courante. */
    public bool $revealed = false;

    /** Nombre de cartes revues durant cette session (feedback de fin). */
    public int $reviewedThisSession = 0;

    /**
     * Mappage des libellés d'auto-évaluation vers la qualité SM-2 (0..5).
     * « À revoir » = échec (réinitialise l'intervalle) ; « Facile » = rappel parfait.
     */
    private const QUALITY = [
        'again' => 2, // À revoir  (q < 3 : échec, on réapprend)
        'hard'  => 3, // Difficile (rappel avec effort)
        'good'  => 4, // Correct
        'easy'  => 5, // Facile    (rappel parfait)
    ];

    public function mount(SrsService $srs): void
    {
        // Page réservée aux utilisateurs connectés ; inaccessible si SRS désactivé.
        abort_unless(Auth::check(), 403);
        abort_unless($srs->isEnabled(), 404);
    }

    /**
     * File des cartes DUES de l'utilisateur courant (toujours re-scopée serveur).
     *
     * @return \Illuminate\Support\Collection<int, SrsCard>
     */
    #[Computed]
    public function dueCards()
    {
        // ACTION: fix 500 régression Scénario A (simulation 2026-07-02) —
        // Livewire n'injecte PAS les dépendances des méthodes #[Computed]
        // (contrairement à mount()/aux actions publiques) : appelées sans
        // argument via __get(), ArgumentCountError sur $srs.
        // SELF: 2 lignes — RAISON: résoudre le service depuis le conteneur.
        return app(SrsService::class)->dueFor($this->user());
    }

    /** Carte courante = première carte due, ou null si la file est vide. */
    #[Computed]
    public function currentCard(): ?SrsCard
    {
        return $this->dueCards->first();
    }

    /** Révèle le verso de la carte courante. */
    public function reveal(): void
    {
        $this->revealed = true;
    }

    /**
     * Auto-évaluation de la carte courante -> application SM-2 côté serveur.
     * Anti-IDOR : on ne rate QUE la carte courante (issue de la file scopée user).
     */
    public function rate(string $grade, SrsService $srs): void
    {
        $quality = self::QUALITY[$grade] ?? null;
        if ($quality === null) {
            return;
        }

        $card = $this->currentCard;
        if ($card === null || (int) $card->user_id !== (int) Auth::id()) {
            return;
        }

        $srs->review($card, $quality);

        $this->reviewedThisSession++;
        $this->revealed = false;

        // Recharge la file (la carte revue n'est plus due).
        unset($this->dueCards, $this->currentCard);
    }

    private function user(): \App\Models\User
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user;
    }

    public function render(): \Illuminate\View\View
    {
        return view('academy::livewire.srs-reviewer');
    }
}
