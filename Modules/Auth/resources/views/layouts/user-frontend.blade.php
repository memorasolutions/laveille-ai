<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $breadcrumbTitle ?? __('Mon espace'),
        'breadcrumbItems' => $breadcrumbItems ?? null,
    ])
@endsection

@section('content')
<div class="container user-space" style="padding: 30px 0 60px;">
    <div class="row" x-data="{ sidebarOpen: false }">

        {{-- Bouton toggle sidebar mobile uniquement --}}
        <div class="col-xs-12 user-space-mobile-only" style="margin-bottom: 15px;">
            <button type="button" class="btn btn-default btn-block" @click="sidebarOpen = !sidebarOpen">
                <i class="fa fa-bars"></i> {{ __('Menu de mon espace') }}
                <i class="fa pull-right" :class="sidebarOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
        </div>

        {{-- Sidebar desktop (toujours visible) --}}
        <div class="col-md-3 user-space-desktop-only">
            @include('auth::layouts.partials.user-sidebar')
        </div>

        {{-- Sidebar mobile (toggle Alpine.js) --}}
        <div class="col-xs-12 user-space-mobile-only" x-show="sidebarOpen" x-transition x-cloak>
            @include('auth::layouts.partials.user-sidebar')
        </div>

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

            @yield('user-content')
        </div>

    </div>
</div>
@endsection
