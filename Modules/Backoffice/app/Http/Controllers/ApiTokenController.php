<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = auth()->user()->tokens()->latest()->get();

        return view('backoffice::profile.tokens', ['tokens' => $tokens, 'title' => 'Tokens API', 'subtitle' => 'Gestion']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $token = auth()->user()->createToken($validated['name']);

        return back()
            ->with('token_value', $token->plainTextToken)
            ->with('success', 'Token créé');
    }

    public function destroy(Request $request, int $id)
    {
        $token = auth()->user()->tokens()->findOrFail($id);
        $token->delete();

        return back()->with('success', 'Token révoqué');
    }
}
