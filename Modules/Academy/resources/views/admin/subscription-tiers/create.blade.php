{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('backoffice::layouts.admin')

@section('content')
<a href="{{ route('admin.academy.subscription-tiers.index') }}"
   class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
    </svg>
    Retour à la liste
</a>

<div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 p-6 bg-white">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Créer un palier d'abonnement</h1>

    <form action="{{ route('admin.academy.subscription-tiers.store') }}" method="POST">
        @csrf

        @include('academy::admin.subscription-tiers.partials.form', ['tier' => null, 'featureKeys' => $featureKeys])

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('admin.academy.subscription-tiers.index') }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Annuler
            </a>
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                Créer le palier
            </button>
        </div>
    </form>
</div>
@endsection
