{{-- Composant réutilisable — liens menu utilisateur
     Usage: @include('auth::components.user-menu-links', ['variant' => 'sidebar']) ou 'dropdown'
     Source unique de vérité pour les liens du menu profil.
--}}
@php
    $variant = $variant ?? 'sidebar';
    $unreadNotifications = $unreadNotifications ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);

    $links = [
        ['route' => 'user.dashboard', 'label' => __('Tableau de bord'), 'icon' => 'fa-tachometer', 'emoji' => '📊'],
        ['route' => 'user.profile', 'label' => __('Mon profil'), 'icon' => 'fa-user', 'emoji' => '👤'],
        ['route' => 'user.contributions', 'label' => __('Mes contributions'), 'icon' => 'fa-handshake-o', 'emoji' => '📝'],
        ['route' => 'user.saved', 'label' => __('Mes sauvegardes'), 'icon' => 'fa-floppy-o', 'emoji' => '💾'],
        ['route' => 'bookmarks.index', 'label' => __('Mes favoris'), 'icon' => 'fa-bookmark', 'emoji' => '❤️'],
        ['route' => 'shop.my-orders', 'label' => __('Mes commandes'), 'icon' => 'fa-shopping-bag', 'emoji' => '🛒'],
        ['route' => 'shorturl.user.index', 'label' => __('Mes liens courts'), 'icon' => 'fa-link', 'emoji' => '🔗'],
        ['route' => 'user.notifications', 'label' => __('Notifications'), 'icon' => 'fa-bell', 'emoji' => '🔔', 'badge' => true],
    ];
@endphp

@foreach($links as $link)
    @if(Route::has($link['route']))
        @if($variant === 'dropdown')
            <a href="{{ route($link['route']) }}" style="display:block;padding:10px 16px;color:var(--c-dark);text-decoration:none!important;font-size:13px;font-weight:500;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">{{ $link['emoji'] }} {{ $link['label'] }}@if(($link['badge'] ?? false) && $unreadNotifications > 0) <span style="background:#ef4444;color:#fff;padding:1px 6px;border-radius:10px;font-size:10px;font-weight:700;margin-left:4px;">{{ $unreadNotifications }}</span>@endif</a>
        @else
            <a href="{{ route($link['route']) }}" class="list-group-item {{ request()->routeIs($link['route'] . '*') ? 'active' : '' }}">
                <i class="fa {{ $link['icon'] }}" style="width: 20px;"></i> {{ $link['label'] }}
                @if($link['route'] === 'user.notifications' && $unreadNotifications > 0)
                    <span class="badge" style="background: #d9534f;">{{ $unreadNotifications }}</span>
                @endif
            </a>
        @endif
    @endif
@endforeach
