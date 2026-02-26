@extends('backoffice::layouts.admin')

@section('page-title', 'Nouvel utilisateur')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Nouvel utilisateur</h2>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone <span class="text-gray-400">(optionnel, pour SMS OTP)</span></label>
                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                       placeholder="514-555-1234">
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mot de passe <span class="text-gray-400">(optionnel si le rôle n'exige pas de mot de passe)</span></label>
                <input type="password" name="password" id="password"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="must_change_password" value="0">
                    <input type="checkbox" name="must_change_password" value="1" {{ old('must_change_password') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Forcer le changement de mot de passe au premier login</span>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rôles</label>
                <div class="mt-2 space-y-2">
                    @foreach($roles as $id => $name)
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="roles[]" value="{{ $id }}" {{ in_array($id, old('roles', [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Créer</button>
                <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300">Annuler</a>
            </div>
        </form>
    </div>
@endsection
