<?php

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Services\RiskScoreService;

/**
 * Tableau de bord organisationnel des analytiques prédictifs (vue admin uniquement).
 *
 * MODÈLE DE SÉCURITÉ :
 *  - gate `academy.manage` vérifiée au montage ET à chaque calcul (#[Computed]).
 *  - le drapeau `academy.predictive_analytics_enabled` doit être true (404 sinon).
 *  - aucune écriture, lecture seule.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class OrgAnalyticsDashboard extends Component
{
    /**
     * Entrée. Vérifie la permission academy.manage et le drapeau feature.
     */
    public function mount(): void
    {
        abort_unless(
            (bool) auth()->user()?->can('academy.manage'),
            403,
        );

        abort_unless(
            (bool) config('academy.predictive_analytics_enabled', false),
            403,
            'Analytiques prédictifs désactivés.',
        );
    }

    /**
     * Résumé organisationnel. Re-autorise à chaque calcul (anti-IDOR).
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function summary(): array
    {
        abort_unless(
            (bool) auth()->user()?->can('academy.manage'),
            403,
        );

        return app(RiskScoreService::class)->organisationSummary();
    }

    public function render()
    {
        return view('academy::livewire.org-analytics-dashboard');
    }
}
