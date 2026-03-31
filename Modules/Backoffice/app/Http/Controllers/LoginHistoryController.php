<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\View\View;
use Modules\Auth\Models\LoginAttempt;
use Modules\Settings\Facades\Settings;

class LoginHistoryController
{
    public function index(): View
    {
        $attempts = LoginAttempt::with('user')
            ->latest('logged_in_at')
            ->paginate((int) Settings::get('backoffice.login_history_per_page', 30));

        return view('backoffice::login-history.index', [
            'title' => 'Historique des connexions',
            'subtitle' => 'Sécurité',
            'attempts' => $attempts,
        ]);
    }
}
