@extends('backoffice::layouts.admin')

@section('page-title', 'Profil')

@section('content')
    <div class="mx-auto max-w-2xl space-y-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Mon profil</h2>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800 ring-1 ring-green-600/20">
                {{ session('success') }}
            </div>
        @endif

        {{-- Informations du profil --}}
        <form method="POST" action="{{ route('admin.profile.update') }}"
              class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            @csrf
            @method('PUT')

            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informations du profil</h3>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Courriel</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Enregistrer
            </button>
        </form>

        {{-- Mot de passe --}}
        <form method="POST" action="{{ route('admin.profile.password') }}"
              class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            @csrf
            @method('PUT')

            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Modifier le mot de passe</h3>

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mot de passe actuel</label>
                <input type="password" name="current_password" id="current_password" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nouveau mot de passe</label>
                <input type="password" name="password" id="password" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmer</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm">
            </div>

            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                Modifier le mot de passe
            </button>
        </form>

        {{-- Double authentification (2FA) --}}
        <div class="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Double authentification (2FA)</h3>
            </div>

            @if (session('status') === '2fa-setup' || session()->has('2fa.setup'))
                {{-- Étape de configuration : afficher le QR code --}}
                <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800 ring-1 ring-blue-600/20">
                    <p class="font-semibold mb-1">Configurez votre application d'authentification</p>
                    <p>Scannez ce QR code avec Google Authenticator, Authy ou toute autre application compatible TOTP.</p>
                </div>

                <div class="flex justify-center">
                    <img src="{{ session('2fa.setup')['qr_url'] }}" alt="QR Code 2FA"
                         class="h-48 w-48 rounded-lg border border-gray-200 p-2">
                </div>

                <div class="rounded-lg bg-amber-50 p-4 ring-1 ring-amber-600/20">
                    <p class="text-sm font-semibold text-amber-800 mb-2">Codes de récupération - Conservez-les en lieu sûr</p>
                    <p class="text-xs text-amber-700 mb-3">Si vous perdez l'accès à votre application, ces codes vous permettront de vous connecter. Chaque code ne peut être utilisé qu'une seule fois.</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach(session('2fa.setup')['recovery_codes'] as $code)
                            <code class="block rounded bg-white px-3 py-1.5 text-sm font-mono text-gray-900 ring-1 ring-gray-300">{{ $code }}</code>
                        @endforeach
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.profile.2fa.confirm') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="otp_code" class="block text-sm font-medium text-gray-700">Code OTP de confirmation</label>
                        <input type="text" name="code" id="otp_code" maxlength="6" inputmode="numeric"
                               placeholder="000000"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono tracking-widest text-center"
                               required>
                        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        Confirmer et activer le 2FA
                    </button>
                </form>

            @elseif($user->hasEnabledTwoFactor())
                {{-- 2FA actif --}}
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        Activé
                    </span>
                    <span class="text-sm text-gray-600">Votre compte est protégé par la double authentification.</span>
                </div>

                <form method="POST" action="{{ route('admin.profile.2fa.disable') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="password_2fa" class="block text-sm font-medium text-gray-700">Confirmez votre mot de passe pour désactiver</label>
                        <input type="password" name="password" id="password_2fa" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Désactiver le 2FA
                    </button>
                </form>

            @else
                {{-- 2FA inactif --}}
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        Désactivé
                    </span>
                    <span class="text-sm text-gray-600">Ajoutez une couche de sécurité supplémentaire à votre compte.</span>
                </div>

                <form method="POST" action="{{ route('admin.profile.2fa.enable') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Activer la double authentification
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection
