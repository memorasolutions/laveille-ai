<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Vérification de courriel'))
@section('content')

<h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Vérification de courriel') }}</h1>
<p class="mt-2 text-base text-gray-600">
    {{ __('Nous vous avons envoyé un lien de vérification à votre adresse courriel.') }}<br>
    {{ __('Vérifiez aussi vos spams.') }}
</p>

@if (session('resent'))
    <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded-md text-sm mt-4" role="alert">
        {{ __('Courriel de vérification renvoyé !') }}
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}" class="mt-8">
    @csrf
    <button type="submit"
            class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0284c7">
        {{ __('Renvoyer le courriel') }}
    </button>
</form>

<div class="mt-6 text-center">
    <form method="POST" action="{{ route('logout') }}" class="inline">
        @csrf
        <button type="button" onclick="this.closest('form').submit()"
                class="font-medium text-sky-600 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline bg-transparent border-none cursor-pointer p-0">
            {{ __('Déconnexion') }}
        </button>
    </form>
</div>

@endsection
