<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\Backoffice\Http\Requests\StoreUserRequest;
use Modules\Backoffice\Http\Requests\UpdateUserRequest;
use Spatie\Permission\Models\Role;

class UserController
{
    public function index(): View
    {
        return view('backoffice::users.index');
    }

    public function create(): View
    {
        return view('backoffice::users.create', [
            'roles' => Role::pluck('name', 'id'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'must_change_password' => $validated['must_change_password'] ?? false,
            ];
            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }
            $user = User::create($userData);

            if (! empty($validated['roles'])) {
                $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name');
                $user->syncRoles($roleNames);
            }
        });

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé.');
    }

    public function show(User $user): View
    {
        $user->load('roles');

        return view('backoffice::users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('backoffice::users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name', 'id'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $user) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'must_change_password' => $validated['must_change_password'] ?? false,
                'is_active' => $validated['is_active'] ?? false,
            ]);

            if (! empty($validated['password'])) {
                $user->update(['password' => Hash::make($validated['password'])]);
            }

            $roleNames = Role::whereIn('id', $validated['roles'] ?? [])->pluck('name');
            $user->syncRoles($roleNames);
        });

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur modifié.');
    }

    public function unlock(User $user): RedirectResponse
    {
        $user->update(['failed_login_count' => 0, 'locked_until' => null]);

        return back()->with('success', 'Compte déverrouillé.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé.');
    }
}
