<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Tableau de bord') . ' - ' . config('app.name'))

@section('user-content')

{{-- Bannière de vérification de courriel : la vérification est IMPOSÉE en middleware `verified`
     sur Santé, Journal et Feuille de route - un utilisateur non vérifié y est bloqué sans aucun
     avertissement préalable si cette bannière est absente (régression UX silencieuse). $user est
     garanti authentifié ici (UserDashboardController::dashboard() redirige les invités vers la
     vue guest avant d'atteindre cette vue). Seule des anciens widgets du tableau de bord à être
     resurfacée - les autres restent enterrés (v1.193.x). --}}
@if(! $user->hasVerifiedEmail())
<div class="alert alert-warning d-flex align-items-center justify-content-between" role="alert" style="margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
    <span><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> {{ __("Votre adresse courriel n'est pas encore vérifiée. Certaines fonctionnalités (Santé, Journal, Feuille de route) la requièrent.") }}</span>
    <x-core::button :href="route('verification.notice')" variant="primary">
        {{ __('Vérifier mon courriel') }}
    </x-core::button>
</div>
@endif

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">{{ __('Bonjour') }}, {{ $user->name }} !</h2>
<p style="color: #777; margin: 0 0 25px;">{{ __('Bienvenue dans votre espace personnel.') }}</p>

{{-- Cartes statistiques --}}
<div class="row" style="margin-bottom: 25px;">
    <div class="col-xs-6 col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-lightbulb-o fa-2x" style="color: #f0ad4e; margin-bottom: 8px;"></i>
                <h3 style="margin: 0; font-weight: 700;">{{ $stats['suggestions_count'] }}</h3>
                <small style="color: #777;">{{ __('Suggestions') }}</small>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-thumbs-up fa-2x" style="color: #337ab7; margin-bottom: 8px;"></i>
                <h3 style="margin: 0; font-weight: 700;">{{ $stats['votes_count'] }}</h3>
                <small style="color: #777;">{{ __('Votes') }}</small>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-bookmark fa-2x" style="color: #5bc0de; margin-bottom: 8px;"></i>
                <h3 style="margin: 0; font-weight: 700;">{{ $stats['bookmarks_count'] }}</h3>
                <small style="color: #777;">{{ __('Favoris') }}</small>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-bell fa-2x" style="color: #d9534f; margin-bottom: 8px;"></i>
                <h3 style="margin: 0; font-weight: 700;">{{ $unreadNotifications }}</h3>
                <small style="color: #777;">{{ __('Notifications non lues') }}</small>
            </div>
        </div>
    </div>
</div>

{{-- Actions rapides --}}
<div style="margin-bottom: 25px;">
    @if(Route::has('user.profile'))
        <a href="{{ route('user.profile') }}" class="btn btn-default" style="margin-right: 5px; margin-bottom: 5px;">
            <i class="fa fa-user"></i> {{ __('Mon profil') }}
        </a>
    @endif
    @if(Route::has('user.contributions'))
        <a href="{{ route('user.contributions') }}" class="btn btn-default" style="margin-right: 5px; margin-bottom: 5px;">
            <i class="fa fa-handshake-o"></i> {{ __('Mes contributions') }}
        </a>
    @endif
    @if(Route::has('bookmarks.index'))
        <a href="{{ route('bookmarks.index') }}" class="btn btn-default" style="margin-right: 5px; margin-bottom: 5px;">
            <i class="fa fa-bookmark"></i> {{ __('Mes favoris') }}
        </a>
    @endif
    @if(Route::has('user.crosswords.index'))
        <a href="{{ route('user.crosswords.index') }}" class="btn btn-default" style="margin-right: 5px; margin-bottom: 5px;">
            <i class="fa fa-puzzle-piece"></i> {{ __('Mes mots croisés') }}
        </a>
    @endif
    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']) && Route::has('admin.dashboard'))
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary" style="margin-bottom: 5px;">
            <i class="fa fa-shield"></i> {{ __('Administration') }}
        </a>
    @endif
</div>

{{-- Activité récente --}}
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-clock-o"></i> {{ __('Activité récente') }}</h3>
    </div>
    <div class="panel-body">
        <p style="color: #999; text-align: center; padding: 20px 0;">
            <i class="fa fa-info-circle"></i> {{ __('Votre activité récente apparaîtra ici.') }}
        </p>
    </div>
</div>

@endsection
