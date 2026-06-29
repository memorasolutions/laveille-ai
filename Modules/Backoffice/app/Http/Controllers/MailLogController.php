<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\View\View;

class MailLogController
{
    public function index(): View
    {
        // ACTION: Délégation de la pagination au composant Livewire MailLogTable
        // MCP: SELF (< 5 lignes, suppression du paginator du contrôleur)
        // RAISON: La liste est gérée par MailLogTable (scroll infini) ; le contrôleur ne sert plus qu'à rendre la vue hôte.
        return view('backoffice::mail-log.index', [
            'title'    => 'Journal des emails',
            'subtitle' => 'Communications',
        ]);
    }
}
