@extends('backoffice::layouts.admin')

@section('page-title', 'Nouveau rôle')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Nouveau rôle</h2>

        <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="requires_password" value="0">
                    <input type="checkbox" name="requires_password" value="1" {{ old('requires_password', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ce rôle nécessite un mot de passe</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">Si désactivé, les utilisateurs se connecteront uniquement par code OTP.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permissions</label>
                <div class="mt-2 space-y-4">
                    @foreach($permissions as $group => $perms)
                        <div>
                            <h4 class="mb-1 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ $group }}</h4>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                @foreach($perms as $perm)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" {{ in_array($perm->id, old('permissions', [])) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $perm->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Créer</button>
                <a href="{{ route('admin.roles.index') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300">Annuler</a>
            </div>
        </form>
    </div>
@endsection
