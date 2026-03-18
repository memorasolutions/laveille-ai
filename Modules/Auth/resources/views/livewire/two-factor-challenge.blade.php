<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Double authentification') }}</h1>
    <p class="mt-2 text-base text-gray-600">
        @if ($usingRecoveryCode)
            {{ __('Entrez l\'un de vos codes de récupération.') }}
        @else
            {{ __('Entrez le code à 6 chiffres de votre application d\'authentification.') }}
        @endif
    </p>

    <div class="mt-8 space-y-5">
        @if (! $usingRecoveryCode)
            <div>
                <label for="code" class="text-base font-medium text-gray-900">{{ __('Code OTP') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-shield-check text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="code" type="text" id="code"
                           inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus
                           placeholder="000000"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600 text-center font-bold text-2xl tracking-widest font-mono">
                </div>
                @error('code')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        @else
            <div>
                <label for="recoveryCode" class="text-base font-medium text-gray-900">{{ __('Code de récupération') }}</label>
                <div class="mt-2 relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="ti ti-lock text-gray-400 text-xl"></i>
                    </div>
                    <input wire:model="recoveryCode" type="text" id="recoveryCode" autofocus
                           placeholder="XXXXXXXX-XXXXXXXX"
                           class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600 font-mono">
                </div>
                @error('recoveryCode')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        @endif

        <div>
            <button wire:click="authenticate" wire:loading.attr="disabled" type="button"
                    class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0369a1">
                <span wire:loading.remove>{{ __('Vérifier') }}</span>
                <span wire:loading>{{ __('Vérification en cours...') }}</span>
            </button>
        </div>
    </div>

    <div class="mt-6 text-center">
        <button wire:click="toggleRecoveryMode" type="button"
                class="font-medium text-sky-700 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline bg-transparent border-none cursor-pointer p-0">
            @if ($usingRecoveryCode)
                {{ __('Utiliser le code OTP') }}
            @else
                {{ __('Utiliser un code de récupération') }}
            @endif
        </button>
    </div>
</div>
