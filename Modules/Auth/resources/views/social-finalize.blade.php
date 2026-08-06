<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Finaliser votre inscription'))
@section('content')

<h1 class="text-3xl font-bold leading-tight text-black mt-2">{{ __('Finaliser votre inscription') }}</h1>
<p class="mt-2 text-base text-gray-600">{{ __("Vous êtes sur le point de créer un compte avec l'adresse :email.", ['email' => $data['email']]) }}</p>

<form method="POST" action="{{ route('social.finalize.submit') }}" class="mt-8">
    @csrf
    <div class="space-y-5">
        {{-- Attestation d'âge (compte réservé aux personnes de :age ans ou plus) --}}
        <div>
            <label class="flex items-start gap-3">
                <input type="checkbox" name="age_attested" id="age_attested" value="1"
                       class="mt-1 h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-600"
                       required>
                <span class="text-sm text-gray-700">{{ __("J'atteste avoir :age ans ou plus", ['age' => config('privacy.minors.eu_age', 16)]) }}</span>
            </label>
            @error('age_attested')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <button type="submit"
                    class="inline-flex items-center justify-center w-full px-4 py-4 text-base font-semibold text-white transition-all duration-200 border border-transparent rounded-md focus:outline-none hover:opacity-80 focus:opacity-80" style="background-color:#0369a1">
                {{ __('Créer le compte') }}
            </button>
        </div>
    </div>
</form>

<div class="mt-6 text-center">
    <a href="{{ route('login') }}" class="font-medium text-sky-700 transition-all duration-200 hover:text-sky-700 focus:text-sky-700 hover:underline">
        {{ __('Retour à la connexion') }}
    </a>
</div>

@endsection
