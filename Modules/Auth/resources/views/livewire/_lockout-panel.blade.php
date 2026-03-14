<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@if($isLocked)
<div class="space-y-4 mt-6" x-data="{ showContact: false }">
    {{-- Alerte verrouillage --}}
    <div class="rounded-lg bg-amber-50 border border-amber-200 p-4">
        <div class="flex items-start">
            <i class="ti ti-lock text-amber-600 text-xl flex-shrink-0 mt-0.5"></i>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-semibold text-amber-800">{{ __('Compte verrouillé') }}</h3>
                <p class="mt-1 text-sm text-amber-700">
                    {{ __('Votre compte est verrouillé pour :minutes minute(s) suite à de trop nombreuses tentatives.', ['minutes' => $lockoutMinutes]) }}
                </p>
                <div class="mt-3 flex flex-col gap-2">
                    <a href="{{ route('password.request') }}"
                       class="inline-flex items-center text-sm font-medium text-sky-600 hover:text-sky-700 hover:underline">
                        <i class="ti ti-key mr-1.5"></i>
                        {{ __('Réinitialiser mon mot de passe') }}
                    </a>
                    <button type="button" @click="showContact = !showContact"
                            class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-800">
                        <i class="ti ti-lifebuoy mr-1.5"></i>
                        {{ __('Contacter un administrateur') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulaire contact admin --}}
    <div x-show="showContact" x-transition x-cloak class="rounded-lg bg-gray-50 border border-gray-200 p-4">
        @if($contactSent)
            <div class="flex items-center text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
                <i class="ti ti-check mr-2 text-green-600"></i>
                {{ __('Votre message a été envoyé aux administrateurs.') }}
            </div>
        @else
            <label for="contactMessage" class="block text-sm font-medium text-gray-700 mb-1.5">
                {{ __('Décrivez votre situation') }}
            </label>
            <textarea id="contactMessage" wire:model="contactMessage" rows="3"
                      class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                      placeholder="{{ __('Expliquez pourquoi vous avez besoin d\'accéder à votre compte...') }}"></textarea>
            @error('contactMessage')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <button type="button" wire:click="contactAdmin" wire:loading.attr="disabled"
                    class="mt-3 inline-flex items-center px-4 py-2 text-sm font-semibold text-white rounded-md transition-all duration-200 hover:opacity-80 focus:outline-none disabled:opacity-50"
                    style="background-color:#0284c7">
                <span wire:loading.remove wire:target="contactAdmin">
                    <i class="ti ti-send mr-1.5"></i>{{ __('Envoyer') }}
                </span>
                <span wire:loading wire:target="contactAdmin">{{ __('Envoi en cours...') }}</span>
            </button>
        @endif
    </div>
</div>
@endif
