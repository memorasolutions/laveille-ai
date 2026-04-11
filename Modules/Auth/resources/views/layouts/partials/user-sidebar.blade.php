<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Sidebar utilisateur - réutilisée desktop + mobile --}}
@php
    $unreadNotifications = $unreadNotifications ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
@endphp
<div class="panel panel-default" style="margin-bottom: 20px;">
    <div class="panel-heading" style="padding: 12px 15px;">
        <div style="display: flex !important; align-items: center !important;">
            @if(auth()->user()?->avatar)
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
        @include('auth::components.user-menu-links', ['variant' => 'sidebar'])

        @if(Route::has('user.roadmap.ideas'))
            <a href="{{ route('user.roadmap.ideas') }}" class="list-group-item {{ request()->routeIs('user.roadmap.ideas') ? 'active' : '' }}">
                💡 {{ __('Idées et suggestions') }}
            </a>
            <a href="{{ route('user.roadmap.bugs') }}" class="list-group-item {{ request()->routeIs('user.roadmap.bugs') ? 'active' : '' }}" style="color: #d9534f;">
                🐛 {{ __('Signaler un bug') }}
            </a>
        @elseif(Route::has('roadmap.boards.index'))
            <a href="{{ route('roadmap.boards.index') }}" class="list-group-item" target="_blank">
                💡 {{ __('Idées et suggestions') }}
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
