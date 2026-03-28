{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('privacy::layouts.legal')
@section('title', __('Exercer vos droits'))
@section('content')
<div class="prose max-w-none mx-auto">
    <h1>{{ __('Exercer vos droits') }}</h1>

    @if (session('success'))
        <div class="mb-6 rounded-md bg-green-50 border-l-4 border-green-400 p-4" role="alert">
            <p class="text-green-800 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <div class="mb-8 rounded-md bg-blue-50 border-l-4 border-blue-400 p-4">
        <p class="text-blue-800 font-medium mb-1">
            {{ __('Conformément aux lois applicables (RGPD, Loi 25, LPRPDE), vous disposez de droits sur vos données personnelles&nbsp;:') }}
        </p>
        <ul class="ml-5 list-disc text-blue-700 text-sm mb-2">
            @foreach($request_types as $type => $label)
                <li>{{ $label }}</li>
            @endforeach
        </ul>
        <p class="text-blue-700 text-sm mb-2">
            {{ __('Nous nous engageons a repondre a votre demande dans un delai de :days jours maximum.', ['days' => $response_delay_days]) }}
        </p>
        <p class="text-blue-700 text-sm">
            {{ __('Pour toute question, contactez notre DPO :') }}
            <strong>{{ $company['dpo_name'] }}</strong> —
            <a href="mailto:{{ $company['dpo_email'] }}" class="underline text-blue-700">{{ $company['dpo_email'] }}</a>
        </p>
    </div>

    <form method="POST" action="{{ route('legal.rights.store') }}" enctype="multipart/form-data" novalidate class="space-y-6">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                {{ __('Nom complet') }} <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" required aria-required="true"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                value="{{ old('name') }}" autocomplete="name">
            @error('name')
                <p class="mt-1 text-red-600 text-sm" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
                {{ __('Adresse courriel') }} <span class="text-red-500">*</span>
            </label>
            <input type="email" name="email" id="email" required aria-required="true"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                value="{{ old('email') }}" autocomplete="email">
            @error('email')
                <p class="mt-1 text-red-600 text-sm" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="request_type" class="block text-sm font-medium text-gray-700">
                {{ __('Type de demande') }} <span class="text-red-500">*</span>
            </label>
            <select name="request_type" id="request_type" required aria-required="true"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('request_type') border-red-500 @enderror">
                <option value="" disabled selected>{{ __('Sélectionnez un type') }}</option>
                @foreach($request_types as $type => $label)
                    <option value="{{ $type }}" @selected(old('request_type') === $type)>{{ $label }}</option>
                @endforeach
            </select>
            @error('request_type')
                <p class="mt-1 text-red-600 text-sm" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">
                {{ __('Description de la demande') }} <span class="text-red-500">*</span>
            </label>
            <textarea name="description" id="description" required aria-required="true" rows="5"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1 text-red-600 text-sm" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="file" class="block text-sm font-medium text-gray-700">
                {{ __('Document justificatif (optionnel)') }}
            </label>
            <x-core::file-upload name="file" accept="application/pdf,image/jpeg,image/png" :max-size="10" help-text="{{ __('Formats acceptés : PDF, JPG, PNG. Taille maximale : 10 Mo.') }}" />

            @error('file')
                <p class="mt-1 text-red-600 text-sm" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <button type="submit"
                class="inline-flex items-center px-6 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                {{ __('Envoyer la demande') }}
            </button>
        </div>
    </form>
</div>
@endsection
