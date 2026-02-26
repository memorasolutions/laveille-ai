<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class UserApiTokenController extends Controller
{
    public function index(): View
    {
        $tokens = auth()->user()->tokens()->latest()->get();

        return view('auth::api-tokens.index', compact('tokens'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $token = auth()->user()->createToken($request->name);

        return redirect()->back()
            ->with('success', 'Token créé avec succès !')
            ->with('token_value', $token->plainTextToken);
    }

    public function destroy(int $id): RedirectResponse
    {
        auth()->user()->tokens()->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Token révoqué.');
    }
}
