<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Shared\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait VerifiesPassword
{
    protected function verifyPasswordOrFail(Request $request): ?RedirectResponse
    {
        $request->validate(['password' => 'required']);

        if (! Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        return null;
    }
}
