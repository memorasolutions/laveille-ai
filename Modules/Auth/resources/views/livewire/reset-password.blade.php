<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Réinitialiser le mot de passe') }}</h1>
    <p class="mt-2 text-base text-gray-600">{{ __('Choisissez un nouveau mot de passe pour votre compte.') }}</p>

    <form wire:submit="resetPassword" class="mt-8">
        <div class="space-y-5">
            {{-- Courriel --}}
            <div>
                <label for="reset-email" class="text-base font-medium text-gray-900">{{ __('Courriel') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-mail text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="email" type="email" id="reset-email" autocomplete="email"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           required>
                </div>
                @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Nouveau mot de passe --}}
            <div>
                <label for="reset-password" class="text-base font-medium text-gray-900">{{ __('Nouveau mot de passe') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-lock text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="password" type="password" id="reset-password"
                           class="block w-full py-4 ps-10 pe-10 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('Nouveau mot de passe') }}" required>
                    <button type="button"
                            class="absolute inset-y-0 flex items-center text-gray-400 hover:text-gray-600" style="right:0;padding-right:0.75rem"
                            onclick="togglePasswordVisibility('reset-password')"
                            aria-label="{{ __('Afficher le mot de passe') }}">
                        <i class="ti ti-eye text-xl toggle-eye-reset-password"></i>
                    </button>
                </div>
                @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Confirmation mot de passe --}}
            <div>
                <label for="reset-password-confirm" class="text-base font-medium text-gray-900">{{ __('Confirmer le mot de passe') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-lock text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="password_confirmation" type="password" id="reset-password-confirm"
                           class="block w-full py-4 ps-10 pe-10 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('Confirmer le mot de passe') }}" required>
                    <button type="button"
                            class="absolute inset-y-0 flex items-center text-gray-400 hover:text-gray-600" style="right:0;padding-right:0.75rem"
                            onclick="togglePasswordVisibility('reset-password-confirm')"
                            aria-label="{{ __('Afficher le mot de passe') }}">
                        <i class="ti ti-eye text-xl toggle-eye-reset-password-confirm"></i>
                    </button>
                </div>
            </div>

            {{-- Submit --}}
            <div>
                <button type="submit"
                        class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0369a1"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Réinitialiser') }}</span>
                    <span wire:loading>{{ __('Réinitialisation...') }}</span>
                </button>
            </div>
        </div>
    </form>

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
