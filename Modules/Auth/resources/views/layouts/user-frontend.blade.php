<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $breadcrumbTitle ?? __('Mon espace'),
        'breadcrumbItems' => $breadcrumbItems ?? null,
    ])
@endsection

@section('content')
{{-- Bannière d'impersonation : un admin qui « devient » un utilisateur (session
     impersonating_original_id, ImpersonationController) doit voir en permanence qu'il l'est et
     pouvoir revenir - trou de sécurité/UX comblé (triage tests hérités 2026-08-18, Phase121).
     Placée dans le layout de l'espace utilisateur : couvre le tableau de bord et toutes les
     pages de cet espace, jamais dupliquée. --}}
@if(session('impersonating_original_id'))
<div class="container" style="padding-top: 15px;">
    <div class="alert alert-warning d-flex align-items-center justify-content-between" role="alert" style="margin-bottom: 0;">
        <span>⚠️ {{ __('Impersonnification en cours') }} — {{ __('vous naviguez au nom d\'un autre utilisateur.') }}</span>
        <form method="POST" action="{{ route('admin.impersonate.stop') }}" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-sm btn-dark">{{ __('Revenir à mon compte') }}</button>
        </form>
    </div>
</div>
@endif
<div class="container user-space" style="padding: 30px 0 60px;">
    <div class="row" x-data="{ sidebarOpen: false }">

        {{-- Bouton toggle sidebar mobile uniquement --}}
        <div class="col-xs-12 user-space-mobile-only" style="margin-bottom: 15px;">
            <button type="button" class="btn btn-default btn-block" @click="sidebarOpen = !sidebarOpen">
                <i class="fa fa-bars"></i> {{ __('Menu de mon espace') }}
                <i class="fa pull-right" :class="sidebarOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>

        @auth
        {{-- Sidebar desktop (toujours visible si authentifie) --}}
        <div class="col-md-3 user-space-desktop-only">
            @include('auth::layouts.partials.user-sidebar')
        </div>

        {{-- Sidebar mobile (toggle Alpine.js) — PAS la classe user-space-mobile-only ici : son
             display:block!important (media query) écrasait le style inline qu'Alpine pose via
             x-show, empêchant le repli mobile (même bug de fond que les groupes d'accordéon
             .msp-group, corrigé le 2026-07-18). x-cloak+x-show suffisent seuls à contrôler
             l'affichage sur tous les breakpoints (fermé par défaut, ouvert au clic du bouton
             "Menu de mon espace" ci-dessus). --}}
        <div class="col-xs-12" x-show="sidebarOpen" x-transition x-cloak>
            @include('auth::layouts.partials.user-sidebar')
        </div>
        @endauth

        {{-- Contenu principal --}}
        <div class="col-md-9">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Fermer" style="background:none;border:none;font-size:20px;font-weight:700;color:inherit;opacity:0.5;cursor:pointer;padding:0;float:right;line-height:1;"><span aria-hidden="true">&times;</span></button>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Fermer" style="background:none;border:none;font-size:20px;font-weight:700;color:inherit;opacity:0.5;cursor:pointer;padding:0;float:right;line-height:1;"><span aria-hidden="true">&times;</span></button>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Fermer" style="background:none;border:none;font-size:20px;font-weight:700;color:inherit;opacity:0.5;cursor:pointer;padding:0;float:right;line-height:1;"><span aria-hidden="true">&times;</span></button>
                    {{ session('warning') }}
                </div>
            @endif
            @if($errors->any())
                {{-- withErrors() (extend/export/shortlink/slug dans PollManageController, etc.) n'était
                     jamais rendu par ce layout - échec silencieux découvert par vérification visuelle
                     (2026-07-19, plafond de prolongations Décido). --}}
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Fermer" style="background:none;border:none;font-size:20px;font-weight:700;color:inherit;opacity:0.5;cursor:pointer;padding:0;float:right;line-height:1;"><span aria-hidden="true">&times;</span></button>
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('user-content')
        </div>

    </div>
</div>
@endsection
