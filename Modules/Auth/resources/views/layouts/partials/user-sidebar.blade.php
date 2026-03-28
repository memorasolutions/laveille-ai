<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Sidebar utilisateur - réutilisée desktop + mobile --}}
@php
    $unreadNotifications = $unreadNotifications ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
@endphp
<div class="panel panel-default" style="margin-bottom: 20px;">
    <div class="panel-heading" style="padding: 12px 15px;">
        <div style="display: flex !important; align-items: center !important;">
            @if(auth()->user()->avatar)
                <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="{{ auth()->user()->name }}"
                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
            @else
                <span style="width: 40px; height: 40px; border-radius: 50%; background: #337ab7; color: #fff; display: flex !important; align-items: center !important; justify-content: center !important; font-weight: 700; font-size: 16px; margin-right: 10px;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            @endif
            <div>
                <strong style="display: block; font-size: 14px;">{{ auth()->user()->name }}</strong>
                <small style="color: #999;">{{ auth()->user()->email }}</small>
            </div>
        </div>
    </div>
    <div class="list-group" style="margin-bottom: 0;">
        @if(Route::has('user.dashboard'))
            <a href="{{ route('user.dashboard') }}" class="list-group-item {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                <i class="fa fa-tachometer" style="width: 20px;"></i> {{ __('Tableau de bord') }}
            </a>
        @endif
        @if(Route::has('user.profile'))
            <a href="{{ route('user.profile') }}" class="list-group-item {{ request()->routeIs('user.profile') ? 'active' : '' }}">
                <i class="fa fa-user" style="width: 20px;"></i> {{ __('Mon profil') }}
            </a>
        @endif
        @if(Route::has('user.contributions'))
            <a href="{{ route('user.contributions') }}" class="list-group-item {{ request()->routeIs('user.contributions') ? 'active' : '' }}">
                <i class="fa fa-handshake-o" style="width: 20px;"></i> {{ __('Mes contributions') }}
            </a>
        @endif
        @if(Route::has('bookmarks.index'))
            <a href="{{ route('bookmarks.index') }}" class="list-group-item {{ request()->routeIs('bookmarks.index') ? 'active' : '' }}">
                <i class="fa fa-bookmark" style="width: 20px;"></i> {{ __('Mes favoris') }}
            </a>
        @endif
        @if(Route::has('user.notifications'))
            <a href="{{ route('user.notifications') }}" class="list-group-item {{ request()->routeIs('user.notifications') ? 'active' : '' }}">
                <i class="fa fa-bell" style="width: 20px;"></i> {{ __('Notifications') }}
                @if($unreadNotifications > 0)
                    <span class="badge" style="background: #d9534f;">{{ $unreadNotifications }}</span>
                @endif
            </a>
        @endif

        @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
            @if(Route::has('admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="list-group-item">
                    <i class="fa fa-shield" style="width: 20px;"></i> {{ __('Administration') }}
                </a>
            @endif
        @endif

        <a href="{{ url('/') }}" class="list-group-item">
            <i class="fa fa-arrow-left" style="width: 20px;"></i> {{ __('Retour au site') }}
        </a>

        <a href="#" class="list-group-item" style="color: #d9534f;" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
            <i class="fa fa-sign-out" style="width: 20px;"></i> {{ __('Déconnexion') }}
        </a>
        <form id="sidebar-logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
</div>
