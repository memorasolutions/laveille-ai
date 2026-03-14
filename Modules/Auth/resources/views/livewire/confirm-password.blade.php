<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Confirmation requise'))
@section('content')

<h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Confirmation requise') }}</h1>
<p class="mt-2 text-base text-gray-600">{{ __('Cette action nécessite de confirmer votre mot de passe pour continuer.') }}</p>

<form method="POST" action="{{ route('password.confirm') }}" class="mt-8">
    @csrf
    <div class="space-y-5">
        <div>
            <label for="confirm-password" class="text-base font-medium text-gray-900">{{ __('Mot de passe') }}</label>
            <div class="mt-2 relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class="ti ti-lock text-gray-400 text-xl"></i>
                </div>
                <input id="confirm-password" type="password" name="password" autocomplete="current-password"
                       class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                       placeholder="{{ __('Votre mot de passe') }}" required>
            </div>
            @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <button type="submit"
                    class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0284c7">
                {{ __('Confirmer') }}
            </button>
        </div>
    </div>
</form>

<div class="mt-6 text-center">
    <a href="{{ route('user.dashboard') }}" class="font-medium text-sky-600 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline">
        {{ __('Retour au tableau de bord') }}
    </a>
</div>

@endsection
