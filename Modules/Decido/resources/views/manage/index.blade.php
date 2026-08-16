{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
@extends('auth::layouts.user-frontend')
@section('title', 'Mes sondages Décido · ' . config('app.name'))
{{-- Round 10 (skill /100) : page privée (auth requis, liste des sondages d'un compte), jamais
     destinée à l'indexation - même politique noindex que le reste des vues privées Décido. --}}
@section('page_noindex', true)
@section('meta_description', "Gère tes sondages Décido : crée, consulte et exporte les résultats de tes consultations collectives.")
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'Mes sondages Décido', 'breadcrumbItems' => [__('Outils'), 'Mes sondages Décido']])
@endsection
@section('user-content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Mes sondages Décido</h1>
    <x-core::button :href="route('decido.create')" variant="primary">+ Créer un sondage</x-core::button>
</div>

@if($polls->isEmpty())
    <div class="alert alert-info d-flex flex-column align-items-center justify-content-center text-center py-5">
        <p class="mb-3">Tu n'as pas encore créé de sondage Décido.</p>
        <x-core::button :href="route('decido.create')" variant="primary">+ Créer un sondage</x-core::button>
    </div>
@else
    <div class="list-group">
        @foreach($polls as $poll)
            <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 py-3">
                <div>
                    <h3 class="h5 mb-1">{{ $poll->title }}</h3>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <span class="badge rounded-pill" style="background-color: {{ $poll->type->value === 'date' ? 'var(--c-primary)' : 'var(--c-accent)' }}; color: white;">
                            {{ $poll->type->label() }}
                        </span>
                        @php
                            // Décido round 6 (skill /100) : #6c757d (gris Bootstrap générique)
                            // n'atteint que 4.69:1 en texte blanc - passe l'AA (4.5:1) mais
                            // échoue l'AAA (7:1) exigé par la charte du projet. --c-dark est
                            // déjà validé AAA sur blanc ailleurs sur le site.
                            $statusColor = match ($poll->status->value) {
                                'open' => 'var(--c-primary)',
                                'closed' => 'var(--c-dark)',
                                'draft' => 'var(--c-accent)',
                                default => 'var(--c-dark)',
                            };
                        @endphp
                        <span class="badge rounded-pill" style="background-color: {{ $statusColor }}; color: white;">
                            {{ $poll->status->label() }}
                        </span>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Créé le {{ $poll->created_at->locale('fr')->isoFormat('LL') }}
                    </small>
                    {{-- LOT 3 (2026-08-16) : nombre de réponses reçues + lien public à partager,
                         directement depuis "Mes sondages" - évite de devoir ouvrir chaque sondage
                         juste pour retrouver son lien. --}}
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2" x-data="{ copied: false }">
                        <span class="text-muted small">
                            {{ $poll->responseCount() }} réponse{{ $poll->responseCount() > 1 ? 's' : '' }} reçue{{ $poll->responseCount() > 1 ? 's' : '' }}
                        </span>
                        <code class="small">{{ $poll->share_url }}</code>
                        <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" style="min-height:44px;"
                                aria-label="Copier le lien du sondage {{ $poll->title }}"
                                x-on:click="copyToClipboard({{ Illuminate\Support\Js::from($poll->share_url) }}, 'Lien public copié').then(ok => { if (ok) { copied = true; setTimeout(() => copied = false, 2000) } })">
                            <span x-show="!copied">Copier</span>
                            <span x-show="copied" aria-hidden="true">✓ Copié !</span>
                        </button>
                    </div>
                </div>
                <x-core::button :href="route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => 'proprietaire'])" variant="ghost" size="sm">Gérer</x-core::button>
            </div>
        @endforeach
    </div>
@endif
@endsection
