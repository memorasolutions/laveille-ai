<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Mot de passe oublié') }}</h1>
    <p class="mt-2 text-base text-gray-600">{{ __('Entrez votre courriel et nous vous enverrons un lien de réinitialisation.') }}</p>

    @if($status)
        <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded-md text-sm mt-4" role="alert">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="mt-8">
        <div class="space-y-5">
            <div>
                <label for="forgot-email" class="text-base font-medium text-gray-900">{{ __('Courriel') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-mail text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="email" type="email" id="forgot-email" autocomplete="email"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                           placeholder="{{ __('votre@courriel.com') }}" required autofocus>
                </div>
                @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <button type="submit"
                        class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0284c7"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Envoyer le lien') }}</span>
                    <span wire:loading>{{ __('Envoi...') }}</span>
                </button>
            </div>
        </div>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="font-medium text-sky-600 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline" wire:navigate>
            {{ __('Retour à la connexion') }}
        </a>
    </div>
</div>
