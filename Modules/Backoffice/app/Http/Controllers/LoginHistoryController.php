<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\View\View;
use Modules\Auth\Models\LoginAttempt;

class LoginHistoryController
{
    public function index(): View
    {
        $attempts = LoginAttempt::with('user')
            ->latest('logged_in_at')
            ->paginate(30);

        return view('backoffice::login-history.index', [
            'title' => 'Historique des connexions',
            'subtitle' => 'Sécurité',
            'attempts' => $attempts,
        ]);
    }
}
