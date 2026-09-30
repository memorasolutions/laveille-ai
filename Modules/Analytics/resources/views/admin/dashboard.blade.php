<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin', ['title' => 'Statistiques de fréquentation', 'subtitle' => 'Google Analytics et Search Console'])

@section('breadcrumbs')
<nav class="page-breadcrumb" aria-label="Fil d'Ariane">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Administration</a></li>
        <li class="breadcrumb-item active" aria-current="page">Statistiques de fréquentation</li>
    </ol>
</nav>
@endsection

@section('content')
@php
    $mmss = fn (int $s) => $s >= 60 ? intdiv($s, 60).' min '.str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT).' s' : $s.' s';
    $nf = fn ($n) => number_format((float) $n, 0, ',', ' ');
@endphp

@unless($enabled)
<div class="alert alert-warning" role="alert">{{ __('Le module Analytics est désactivé : les données affichées ne sont plus mises à jour.') }}</div>
@endunless

<form method="GET" action="{{ route('admin.mesure_contenu.dashboard') }}" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label for="analytics-from" class="form-label mb-1">{{ __('Du') }}</label>
        <input type="date" id="analytics-from" name="from" value="{{ $from }}" class="form-control">
    </div>
    <div class="col-auto">
        <label for="analytics-to" class="form-label mb-1">{{ __('Au') }}</label>
        <input type="date" id="analytics-to" name="to" value="{{ $to }}" class="form-control">
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary">{{ __('Appliquer') }}</button></div>
</form>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="analytics-h-ga4">
    <div class="card-body">
        <h2 class="h5 fw-bold mb-3" id="analytics-h-ga4">{{ __('Ce qui attire') }}</h2>
        @if($ga4->isEmpty())
            <p class="text-muted mb-0" data-empty="ga4">{{ __('Aucune donnée Google Analytics pour l\'instant sur cette période. La collecte quotidienne les fera apparaître ici.') }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <caption class="visually-hidden">{{ __('Pages les plus visitées, par sessions') }}</caption>
                <thead><tr><th scope="col">{{ __('Page') }}</th><th scope="col" class="text-end">{{ __('Sessions') }}</th><th scope="col" class="text-end">{{ __('Utilisateurs') }}</th><th scope="col" class="text-end">{{ __('Pages vues') }}</th><th scope="col" class="text-end">{{ __('Engagement moyen') }}</th></tr></thead>
                <tbody>
                @foreach($ga4 as $r)
                    <tr><td class="text-break">{{ $r['url'] }}</td><td class="text-end">{{ $nf($r['sessions']) }}</td><td class="text-end">{{ $nf($r['users']) }}</td><td class="text-end">{{ $nf($r['views']) }}</td><td class="text-end">{{ $mmss($r['avg_time']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="analytics-h-gsc">
    <div class="card-body">
        <h2 class="h5 fw-bold mb-3" id="analytics-h-gsc">{{ __('Ce qu\'on trouve sur Google') }}</h2>
        @if($gsc->isEmpty())
            <p class="text-muted mb-0" data-empty="gsc">{{ __('Aucune donnée Search Console pour l\'instant sur cette période. La collecte quotidienne les fera apparaître ici.') }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <caption class="visually-hidden">{{ __('Pages les plus cliquées dans Google') }}</caption>
                <thead><tr><th scope="col">{{ __('Page') }}</th><th scope="col" class="text-end">{{ __('Clics') }}</th><th scope="col" class="text-end">{{ __('Impressions') }}</th><th scope="col" class="text-end">{{ __('CTR') }}</th><th scope="col" class="text-end">{{ __('Position moyenne') }}</th></tr></thead>
                <tbody>
                @foreach($gsc as $r)
                    <tr><td class="text-break">{{ $r['url'] }}</td><td class="text-end">{{ $nf($r['clicks']) }}</td><td class="text-end">{{ $nf($r['impressions']) }}</td><td class="text-end">{{ number_format($r['ctr'], 2, ',', ' ') }} %</td><td class="text-end">{{ number_format($r['position'], 1, ',', ' ') }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="analytics-h-pub">
    <div class="card-body">
        <h2 class="h5 fw-bold mb-3" id="analytics-h-pub">{{ __('Ce qui a été publié récemment') }}</h2>
        @if(count($transitions) === 0)
            <p class="text-muted mb-0" data-empty="publications">{{ __('Aucune publication ni dépublication enregistrée sur cette période.') }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <caption class="visually-hidden">{{ __('Dernières publications et dépublications') }}</caption>
                <thead><tr><th scope="col">{{ __('Date') }}</th><th scope="col">{{ __('Événement') }}</th><th scope="col">{{ __('Titre') }}</th><th scope="col">{{ __('Adresse') }}</th></tr></thead>
                <tbody>
                @foreach($transitions as $t)
                    <tr><td>{{ $t['occurred_at']->timezone('America/Toronto')->format('Y-m-d H:i') }}</td><td>{{ $t['event'] === 'published' ? __('Publié') : __('Dépublié') }}</td><td class="text-break">{{ $t['title'] ?? '-' }}</td><td class="text-break">{{ $t['url'] ?? '-' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

<section class="card border-0 shadow-sm mb-4" aria-labelledby="analytics-h-runs">
    <div class="card-body">
        <h2 class="h5 fw-bold mb-3" id="analytics-h-runs">{{ __('Collectes') }}</h2>
        @if($runs->isEmpty())
            <p class="text-muted mb-0" data-empty="runs">{{ __('Aucune collecte n\'a encore eu lieu.') }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <caption class="visually-hidden">{{ __('Dernières collectes') }}</caption>
                <thead><tr><th scope="col">{{ __('Source') }}</th><th scope="col">{{ __('Jour') }}</th><th scope="col">{{ __('Statut') }}</th><th scope="col" class="text-end">{{ __('Lignes') }}</th><th scope="col">{{ __('Message') }}</th></tr></thead>
                <tbody>
                @foreach($runs as $run)
                    <tr><td>{{ strtoupper($run->source) }}</td><td>{{ $run->collected_for?->toDateString() }}</td><td>{{ $run->status }}</td><td class="text-end">{{ $nf($run->rows_upserted) }}</td><td class="text-break">{{ $run->message ?? '-' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>
@endsection
