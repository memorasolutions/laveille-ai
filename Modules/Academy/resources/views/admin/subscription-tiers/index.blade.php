{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('backoffice::layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Académie — Paliers d'abonnement</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Freemium / Pro / Organisation. Prix affichés = données locales, aucun appel Stripe.
            </p>
        </div>
        <a href="{{ route('admin.academy.subscription-tiers.create') }}"
           class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
            + Nouveau palier
        </a>
    </div>

    @if(! config('academy.subscription_tiers_enabled'))
        <div class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 ring-1 ring-yellow-200 dark:ring-yellow-800 px-4 py-3 text-sm text-yellow-800 dark:text-yellow-300">
            Le gating par palier est actuellement <strong>désactivé</strong> (drapeau <code>academy.subscription_tiers_enabled</code>).
            Tant qu'il reste désactivé, tous les apprenants gardent l'accès actuel (inchangé) : les paliers ci-dessous
            sont préparés mais n'ont encore aucun effet.
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-lg bg-green-50 dark:bg-green-900/20 ring-1 ring-green-200 dark:ring-green-800 px-4 py-3 text-sm text-green-800 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 ring-1 ring-red-200 dark:ring-red-800 px-4 py-3 text-sm text-red-800 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10 bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Palier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fonctionnalités</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sièges max</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Assignés</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($subscriptionTiers as $tier)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $tier->name }}
                                    @if($tier->is_default)
                                        <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">Défaut</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $tier->slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $tier->price_label }} / {{ $tier->billing_period === 'yearly' ? 'an' : 'mois' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ count($tier->features ?? []) }} fonctionnalité(s)
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $tier->max_seats ?? '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $tier->assignments_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $tier->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $tier->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.academy.subscription-tiers.edit', $tier) }}"
                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Modifier
                                    </a>

                                    <form action="{{ route('admin.academy.subscription-tiers.toggle-status', $tier) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="{{ $tier->is_active ? 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400' : 'text-green-600 hover:text-green-900 dark:text-green-400' }}">
                                            {{ $tier->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>

                                    @if($tier->assignments_count === 0)
                                        <div x-data="{ confirming: false }" class="inline-flex items-center gap-2">
                                            <button type="button" x-show="!confirming" x-on:click="confirming = true"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                Supprimer
                                            </button>
                                            <template x-if="confirming">
                                                <div class="inline-flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Confirmer ?</span>
                                                    <form action="{{ route('admin.academy.subscription-tiers.destroy', $tier) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-700 dark:text-red-400 font-semibold">
                                                            Oui
                                                        </button>
                                                    </form>
                                                    <button type="button" x-on:click="confirming = false" class="text-gray-500 dark:text-gray-400">
                                                        Annuler
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucun palier. <a href="{{ route('admin.academy.subscription-tiers.create') }}" class="text-indigo-600 hover:underline">Créer le premier palier.</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptionTiers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $subscriptionTiers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
