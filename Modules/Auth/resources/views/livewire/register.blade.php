<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Créer un compte') }}</h1>
    <p class="mt-2 text-base text-gray-600">{{ __('Entrez vos informations pour commencer') }}</p>

    <form wire:submit="register" class="mt-8">
        <div class="space-y-5">
            {{-- Nom complet --}}
            <div>
                <label for="register-name" class="text-base font-medium text-gray-900">{{ __('Nom complet') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-user text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="name" type="text" id="register-name" autocomplete="name"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('Nom complet') }}" required autofocus>
                </div>
                @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Courriel --}}
            <div>
                <label for="register-email" class="text-base font-medium text-gray-900">{{ __('Courriel') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-mail text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="email" type="email" id="register-email" autocomplete="email"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('votre@courriel.com') }}" required>
                </div>
                @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Mot de passe avec jauge de force --}}
            <div x-data="{
                    pwd: '',
                    get strength() { if (!this.pwd) return 0; let s = 0; if (this.pwd.length >= 8) s++; if (/[A-Z]/.test(this.pwd)) s++; if (/[0-9]/.test(this.pwd)) s++; if (/[^A-Za-z0-9]/.test(this.pwd)) s++; return s; },
                    get label() { return ['', '{{ __('Faible') }}', '{{ __('Moyen') }}', '{{ __('Fort') }}', '{{ __('Très fort') }}'][this.strength] ?? ''; },
                    get barColor() { return ['', '#dc2626', '#f59e0b', '#3b82f6', '#22c55e'][this.strength] ?? ''; }
                }">
                <label for="register-password" class="text-base font-medium text-gray-900">{{ __('Mot de passe') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-lock text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="password" x-model="pwd" type="password" id="register-password" autocomplete="new-password"
                           class="block w-full py-4 ps-10 pe-10 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('Mot de passe') }}" required>
                    <button type="button"
                            class="absolute inset-y-0 flex items-center text-gray-400 hover:text-gray-600" style="right:0;padding-right:0.75rem"
                            onclick="togglePasswordVisibility('register-password')"
                            aria-label="{{ __('Afficher le mot de passe') }}">
                        <i class="ti ti-eye text-xl toggle-eye-register-password"></i>
                    </button>
                </div>

                <div x-show="pwd.length > 0" x-cloak class="mt-3">
                    <div class="h-1 bg-gray-200 rounded-full overflow-hidden mb-2">
                        <div :style="'width:' + (strength * 25) + '%;background:' + barColor + ';height:100%;transition:width 0.3s,background 0.3s;'"
                             role="progressbar" :aria-valuenow="strength * 25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium" x-text="label"></span>
                        <div class="flex gap-3 text-xs text-gray-500">
                            <span :class="pwd.length >= 8 ? 'text-green-500' : ''">{{ __('8+ caract.') }}</span>
                            <span :class="/[A-Z]/.test(pwd) ? 'text-green-500' : ''">{{ __('Majuscule') }}</span>
                            <span :class="/[0-9]/.test(pwd) ? 'text-green-500' : ''">{{ __('Chiffre') }}</span>
                        </div>
                    </div>
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ __('Minimum 8 caractères requis') }}</p>
                @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Confirmation mot de passe --}}
            <div>
                <label for="register-password-confirm" class="text-base font-medium text-gray-900">{{ __('Confirmer le mot de passe') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-lock text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="password_confirmation" type="password" id="register-password-confirm" autocomplete="new-password"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('Confirmer le mot de passe') }}" required>
                </div>
            </div>

            {{-- Submit --}}
            <div>
                <button type="submit"
                        class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0369a1"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Créer le compte') }}</span>
                    <span wire:loading>{{ __('Création...') }}</span>
                </button>
            </div>
        </div>
    </form>

    @include('auth::partials.social-buttons')

    <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
            {{ __('Déjà un compte ?') }}
            <a href="{{ route('login') }}" class="font-medium text-sky-700 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline" wire:navigate>{{ __('Se connecter') }}</a>
        </p>
    </div>

    @push('scripts')
    <script>
    function togglePasswordVisibility(id) {
        var input = document.getElementById(id);
        var icon = document.querySelector('.toggle-eye-' + id);
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) { icon.classList.remove('ti-eye'); icon.classList.add('ti-eye-off'); }
        } else {
            input.type = 'password';
            if (icon) { icon.classList.remove('ti-eye-off'); icon.classList.add('ti-eye'); }
        }
    }
    </script>
    @endpush
</div>
