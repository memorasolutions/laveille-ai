<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\View\View;

class LoginHistoryController
{
    public function index(): View
    {
        // ACTION: Délégation de la pagination au composant Livewire LoginHistoryTable
        // MCP: SELF (< 5 lignes, suppression du paginator du contrôleur)
        // RAISON: La liste est gérée par LoginHistoryTable (scroll infini).
        return view('backoffice::login-history.index', [
            'title'    => 'Historique des connexions',
            'subtitle' => 'Sécurité',
        ]);
    }
}
