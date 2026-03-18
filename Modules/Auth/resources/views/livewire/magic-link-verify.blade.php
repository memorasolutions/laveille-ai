<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Saisir votre code'))
@section('content')
<h1 class="text-2xl font-bold leading-tight text-black">{{ __('Saisir votre code') }}</h1>
<p class="mt-2 text-base text-gray-600">{{ __('Entrez le code à 6 chiffres reçu par courriel.') }}</p>

@if(session('status'))
    <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded-md text-sm mb-6" role="alert">{{ session('status') }}</div>
@endif
@if(session('sms_sent'))
    <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded-md text-sm mb-6" role="alert">{{ session('sms_sent') }}</div>
@endif
@if($errors->has('sms'))
    <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded-md text-sm mb-6" role="alert">{{ $errors->first('sms') }}</div>
@endif
@if($errors->has('token'))
    <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded-md text-sm mb-6" role="alert">{{ $errors->first('token') }}</div>
@endif

<form action="{{ route('magic-link.confirm') }}" method="POST">
    @csrf
    <div class="mb-5">
        <label for="verify-email" class="text-base font-medium text-gray-900">{{ __('Courriel') }}</label>
        <div class="mt-2.5 relative text-gray-400 focus-within:text-gray-600">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <i class="ti ti-at text-xl"></i>
            </div>
            <input id="verify-email" name="email" type="email" autocomplete="email" class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600" value="{{ old('email', $email) }}" required>
        </div>
        @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="mb-5">
        <label for="token" class="text-base font-medium text-gray-900">{{ __('Code de connexion') }}</label>
        <input id="token" name="token" type="text" inputmode="numeric" pattern="[0-9]*" class="block w-full py-4 px-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600 text-center font-bold text-2xl tracking-[0.4em] font-mono" placeholder="000000" maxlength="6" value="{{ old('token') }}" required autofocus>
        <p class="text-gray-600 text-center text-sm mt-2">{{ __('Code valide') }} {{ $expiryMinutes ?? 15 }} {{ __('minutes') }}</p>
        @error('token')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0369a1">{{ __('Se connecter') }}</button>

    @if($hasPhone ?? false)
        <div class="border-t border-gray-200 mt-6 pt-6"
             x-data="{ countdown: {{ $smsButtonDelay ?? 10 }}, ready: false }"
             x-init="if(countdown > 0) { let t = setInterval(() => { countdown--; if(countdown <= 0) { ready = true; clearInterval(t); } }, 1000); } else { ready = true; }">
            <p class="text-gray-600 text-center text-sm mb-3">{{ __('Vous n\'avez pas reçu le code ?') }}</p>
            <div x-show="!ready" class="text-center">
                <span class="text-gray-600 text-sm">
                    {{ __('SMS disponible dans') }} <span x-text="countdown" class="font-bold text-sky-700"></span> {{ __('secondes') }}
                </span>
            </div>
            <div x-show="ready" x-cloak>
                <form action="{{ route('magic-link.sms') }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ old('email', $email) }}">
                    <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-sky-700 transition-all duration-200 border-2 border-gray-200 rounded-md bg-transparent hover:opacity-80 focus:opacity-80">{{ __('Recevoir par SMS') }}</button>
                </form>
            </div>
        </div>
    @endif

    <div class="text-center mt-6">
        <a href="{{ route('magic-link.request') }}" class="font-medium text-sky-700 transition-all duration-200 hover:text-sky-700 hover:underline">{{ __('Demander un nouveau code') }}</a>
    </div>
</form>
@endsection
