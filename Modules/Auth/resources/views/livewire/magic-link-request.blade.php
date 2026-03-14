<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Connexion sans mot de passe'))
@section('content')

<h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Connexion sans mot de passe') }}</h1>
<p class="mt-2 text-base text-gray-600">{{ __('Entrez votre courriel pour recevoir un code à 6 caractères.') }}</p>

@if(session('status'))
    <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded-md text-sm mt-4 flex flex-col gap-1" role="alert">
        <div>{{ session('status') }}</div>
        <a href="{{ route('magic-link.verify') }}?email={{ urlencode(old('email', '')) }}"
           class="font-semibold text-sky-600 transition-all duration-200 hover:text-sky-700 hover:underline">
            {{ __('Saisir mon code') }} &rarr;
        </a>
    </div>
@endif

@if(session('dev_magic_code'))
<div class="bg-amber-50 border border-amber-400 rounded-md p-3 mt-4">
    <div class="font-semibold text-amber-800 mb-1">{{ __('DEV - Code magic link :') }}</div>
    <code class="text-xl font-bold tracking-widest text-amber-900">{{ session('dev_magic_code') }}</code>
    <button onclick="navigator.clipboard.writeText('{{ session('dev_magic_code') }}'); this.textContent='{{ __('Copié') }}';"
            class="ms-2 bg-amber-400 text-black border-none rounded px-2 py-1 text-sm cursor-pointer hover:bg-amber-500">
        {{ __('Copier') }}
    </button>
</div>
@endif

<form action="{{ route('magic-link.send') }}" method="POST" class="mt-8">
    @csrf
    <div class="space-y-5">
        <div>
            <label for="magic-email" class="text-base font-medium text-gray-900">{{ __('Courriel') }}</label>
            <div class="mt-2 relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    <i class="ti ti-mail text-gray-400 text-xl"></i>
                </div>
                <input id="magic-email" name="email" type="email" autocomplete="email"
                       class="block w-full py-4 ps-10 pe-4 text-black placeholder-gray-500 transition-all duration-200 border border-gray-200 rounded-md bg-gray-50 focus:outline-none focus:border-sky-600 focus:bg-white caret-sky-600"
                       placeholder="{{ __('votre@courriel.com') }}" value="{{ old('email') }}" required autofocus>
            </div>
            @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <button type="submit"
                    class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0284c7">
                {{ __('Envoyer le code') }}
            </button>
        </div>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="font-medium text-sky-600 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline">
            {{ __('Retour à la connexion') }}
        </a>
    </div>
</form>

@endsection
