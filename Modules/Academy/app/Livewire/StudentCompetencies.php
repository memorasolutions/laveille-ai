<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - ÉTUDIANT : « Mes compétences » (LECTURE SEULE). Composant Livewire rendu dans
 * l'espace personnel.
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR) :
 *  - tout est scopé à auth()->id() : un étudiant ne voit QUE SES compétences (celles
 *    rattachées à ses cours suivis), JAMAIS celles d'un autre (anti-IDOR) ;
 *  - état d'acquisition DÉRIVÉ serveur (CompetencyService) de SON achèvement / SES notes ;
 *  - rétrocompat : aucune compétence rattachée → section vide (rien ne change).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Services\CompetencyService;

class StudentCompetencies extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::check(), 403);
    }

    /**
     * MES compétences + état, strictement scopées à auth()->id() (anti-IDOR).
     *
     * @return Collection<int, array{competency: \Modules\Academy\Models\Competency, state: array<string, mixed>}>
     */
    #[Computed]
    public function competencies(): Collection
    {
        $user = Auth::user();

        return $user === null ? collect() : CompetencyService::studentCompetencies($user);
    }

    public function render()
    {
        return view('academy::livewire.student-competencies');
    }
}
