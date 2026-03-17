<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ Settings::get('branding.login_title', __('Connexion')) }}</h1>
    @if(Settings::get('branding.login_subtitle'))
        <p class="mt-2 text-base text-gray-600">{{ Settings::get('branding.login_subtitle') }}</p>
    @else
        <p class="mt-2 text-base text-gray-600">{{ __('Accédez à votre compte') }}</p>
    @endif

    @if(session('status'))
        <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded-md text-sm mb-4 mt-4" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @include('auth::livewire._lockout-panel')

    <form wire:submit="authenticate" class="mt-8" @if($isLocked) style="display:none" @endif>
        <div class="space-y-5">
            {{-- Email --}}
            <div>
                <label for="login-email" class="text-base font-medium text-gray-900">{{ __('Courriel') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-mail text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="email" type="email" id="login-email" autocomplete="email"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('votre@courriel.com') }}" required autofocus>
                </div>
                @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Password --}}
            <div>
                <div class="flex items-center justify-between">
                    <label for="login-password" class="text-base font-medium text-gray-900">{{ __('Mot de passe') }}</label>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-sky-600 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline" wire:navigate>
                        {{ __('Mot de passe oublié ?') }}
                    </a>
                </div>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-lock text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="password" type="password" id="login-password" autocomplete="current-password"
                           class="block w-full py-4 ps-10 pe-10 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('Mot de passe') }}" required>
                    <button type="button"
                            class="absolute inset-y-0 flex items-center text-gray-400 hover:text-gray-600" style="right:0;padding-right:0.75rem"
                            onclick="togglePasswordVisibility('login-password')"
                            aria-label="{{ __('Afficher le mot de passe') }}">
                        <i class="ti ti-eye text-xl toggle-eye-login-password"></i>
                    </button>
                </div>
                @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Remember me --}}
            <div class="flex items-center" style="gap: 0.75rem">
                <input wire:model="remember" type="checkbox" id="remember"
                       class="text-sky-600 border-gray-300 rounded focus:ring-sky-500" style="width: 1.125rem; height: 1.125rem; flex-shrink: 0">
                <label for="remember" class="text-sm font-normal text-gray-700" style="cursor: pointer">{{ __('Se souvenir de moi') }}</label>
            </div>

            {{-- Submit --}}
            <div>
                <button type="submit"
                        class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0284c7"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Se connecter') }}</span>
                    <span wire:loading>{{ __('Connexion en cours...') }}</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Passkey login --}}
    <div x-data="{ supported: typeof window.browserSupportsWebAuthn === 'function' && window.browserSupportsWebAuthn() }" x-show="supported" x-cloak x-bind:inert="!supported" @if($isLocked) style="display:none" @endif>
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-300"></div></div>
            <div class="relative flex justify-center text-sm"><span class="px-4 bg-white text-gray-500">{{ __('ou') }}</span></div>
        </div>
        <x-authenticate-passkey redirect="/admin">
            <button type="button"
                    class="w-full flex items-center justify-center gap-3 px-4 py-4 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                <i class="ti ti-fingerprint text-xl"></i>
                <span class="font-medium">{{ __('Se connecter avec une clé d\'accès') }}</span>
            </button>
        </x-authenticate-passkey>
    </div>

    @include('auth::partials.social-buttons')

    <div class="mt-6 text-center space-y-2">
        <p class="text-sm text-gray-600">
            {{ __('Pas de compte ?') }}
            <a href="{{ route('register') }}" class="font-medium text-sky-600 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline" wire:navigate>{{ __('Créer un compte') }}</a>
        </p>
        <p class="text-sm text-gray-600">
            <a href="{{ route('magic-link.request') }}" class="font-medium text-sky-600 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline" wire:navigate>{{ __('Connexion sans mot de passe (code)') }}</a>
        </p>
    </div>
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
