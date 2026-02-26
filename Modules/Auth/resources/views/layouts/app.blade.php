<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Mon espace')) - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/backoffice/wowdash/images/favicon.png') }}" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/lib/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/wowdash/css/style.css') }}">
    @vite(['resources/js/app.js'])
    @livewireStyles
    @stack('css')
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
</head>
<body>

@php
    $unreadCount  = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $recentNotifs = auth()->check() ? auth()->user()->notifications()->latest()->limit(5)->get() : collect();
    $siteName = config('app.name');
    $initial  = strtoupper(substr($siteName, 0, 1));
    $color    = '#487FFF';
    $svgBase  = '<circle cx="18" cy="18" r="15" fill="' . $color . '"/><text x="18" y="23" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text>';
    $svgLight = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#1f2937">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgDark  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#f1f5f9">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgIcon  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><circle cx="16" cy="16" r="14" fill="' . $color . '"/><text x="16" y="21" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text></svg>');
@endphp

{{-- =================== SIDEBAR =================== --}}
<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="{{ route('user.dashboard') }}" class="sidebar-logo">
            <img src="{{ $svgLight }}" alt="{{ $siteName }}" class="light-logo">
            <img src="{{ $svgDark }}"  alt="{{ $siteName }}" class="dark-logo">
            <img src="{{ $svgIcon }}"  alt="{{ $siteName }}" class="logo-icon">
        </a>
    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">

            {{-- Tableau de bord --}}
            <li class="{{ request()->routeIs('user.dashboard') ? 'active-page' : '' }}">
                <a href="{{ route('user.dashboard') }}">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>{{ __('Tableau de bord') }}</span>
                </a>
            </li>

            {{-- ---- CONTENU ---- --}}
            <li class="sidebar-menu-group-title">{{ __('Contenu') }}</li>

            <li class="dropdown {{ request()->routeIs('user.articles.*') ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:document-bold" class="menu-icon"></iconify-icon>
                    <span>{{ __('Mes articles') }}</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="{{ request()->routeIs('user.articles.index') ? 'active-page' : '' }}">
                        <a href="{{ route('user.articles.index') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> {{ __('Liste') }}
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('user.articles.create') ? 'active-page' : '' }}">
                        <a href="{{ route('user.articles.create') }}">
                            <i class="ri-circle-fill circle-icon text-success-main w-auto"></i> {{ __('Nouvel article') }}
                        </a>
                    </li>
                </ul>
            </li>

            {{-- ---- COMPTE ---- --}}
            <li class="sidebar-menu-group-title">{{ __('Compte') }}</li>

            {{-- Notifications --}}
            <li class="{{ request()->routeIs('user.notifications') ? 'active-page' : '' }}">
                <a href="{{ route('user.notifications') }}">
                    <iconify-icon icon="solar:bell-outline" class="menu-icon"></iconify-icon>
                    <span>{{ __('Notifications') }}</span>
                    @if($unreadCount > 0)
                        <span class="badge text-sm fw-semibold w-auto px-8 py-4 radius-4 bg-danger-focus text-danger-main ms-auto">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </a>
            </li>

            {{-- Mon profil (accordéon) --}}
            <li class="dropdown {{ request()->routeIs('user.profile', 'user.sessions', 'user.activity') ? 'open' : '' }}">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:user-circle-outline" class="menu-icon"></iconify-icon>
                    <span>{{ __('Mon profil') }}</span>
                </a>
                <ul class="sidebar-submenu">
                    <li class="{{ request()->routeIs('user.profile') ? 'active-page' : '' }}">
                        <a href="{{ route('user.profile') }}">
                            <i class="ri-circle-fill circle-icon text-primary-600 w-auto"></i> {{ __('Informations') }}
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('user.sessions') ? 'active-page' : '' }}">
                        <a href="{{ route('user.sessions') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> {{ __('Sessions') }}
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('user.activity') ? 'active-page' : '' }}">
                        <a href="{{ route('user.activity') }}">
                            <i class="ri-circle-fill circle-icon text-info-main w-auto"></i> {{ __('Journal d\'activité') }}
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Abonnement --}}
            <li class="{{ request()->routeIs('user.subscription') ? 'active-page' : '' }}">
                <a href="{{ route('user.subscription') }}">
                    <iconify-icon icon="solar:card-recive-outline" class="menu-icon"></iconify-icon>
                    <span>{{ __('Abonnement') }}</span>
                </a>
            </li>

            {{-- Tokens API --}}
            <li class="{{ request()->routeIs('user.api-tokens') ? 'active-page' : '' }}">
                <a href="{{ route('user.api-tokens') }}">
                    <iconify-icon icon="solar:key-outline" class="menu-icon"></iconify-icon>
                    <span>{{ __('Tokens API') }}</span>
                </a>
            </li>

            {{-- Administration (admin uniquement) --}}
            @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
            <li class="sidebar-menu-group-title">{{ __('Administration') }}</li>
            <li>
                <a href="{{ route('admin.dashboard') }}">
                    <iconify-icon icon="solar:shield-user-outline" class="menu-icon"></iconify-icon>
                    <span>{{ __('Administration') }}</span>
                </a>
            </li>
            @endif

        </ul>
    </div>
</aside>

{{-- =================== MAIN =================== --}}
<main class="dashboard-main">

    {{-- ========= TOPBAR ========= --}}
    <div class="navbar-header">
        <div class="row align-items-center justify-content-between">
            <div class="col-auto">
                <div class="d-flex flex-wrap align-items-center gap-4">
                    <button type="button" class="sidebar-toggle">
                        <iconify-icon icon="heroicons:bars-3-solid" class="icon text-2xl non-active"></iconify-icon>
                        <iconify-icon icon="iconoir:arrow-right" class="icon text-2xl active"></iconify-icon>
                    </button>
                    <button type="button" class="sidebar-mobile-toggle">
                        <iconify-icon icon="heroicons:bars-3-solid" class="icon"></iconify-icon>
                    </button>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex flex-wrap align-items-center gap-3">

                    {{-- Dark mode toggle --}}
                    <button type="button" data-theme-toggle
                            class="w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center">
                    </button>

                    {{-- Langue dropdown --}}
                    <div class="dropdown d-none d-sm-inline-block">
                        <button class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center"
                                type="button" data-bs-toggle="dropdown">
                            <img src="{{ asset('assets/backoffice/wowdash/images/flags/' . (app()->getLocale() === 'fr' ? 'flag3' : 'flag1') . '.png') }}"
                                 alt="{{ app()->getLocale() }}"
                                 class="w-24-px h-24-px object-fit-cover rounded-circle">
                        </button>
                        <div class="dropdown-menu to-top dropdown-menu-sm">
                            <div class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                <h6 class="text-lg text-primary-light fw-semibold mb-0">{{ __('Choisir la langue') }}</h6>
                            </div>
                            <div class="max-h-200-px overflow-y-auto scroll-sm pe-8">
                                <div class="form-check style-check d-flex align-items-center justify-content-between mb-16">
                                    <label class="form-check-label line-height-1 fw-medium text-secondary-light">
                                        <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                            <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag3.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle rounded-circle flex-shrink-0">
                                            <span class="text-md fw-semibold mb-0">Français</span>
                                        </span>
                                    </label>
                                    <form action="{{ route('locale.switch', 'fr') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input class="form-check-input" type="radio" {{ app()->getLocale() === 'fr' ? 'checked' : '' }} onclick="this.closest('form').submit()">
                                    </form>
                                </div>
                                <div class="form-check style-check d-flex align-items-center justify-content-between">
                                    <label class="form-check-label line-height-1 fw-medium text-secondary-light">
                                        <span class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                            <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag1.png') }}" alt="" class="w-36-px h-36-px bg-success-subtle rounded-circle flex-shrink-0">
                                            <span class="text-md fw-semibold mb-0">English</span>
                                        </span>
                                    </label>
                                    <form action="{{ route('locale.switch', 'en') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input class="form-check-input" type="radio" {{ app()->getLocale() === 'en' ? 'checked' : '' }} onclick="this.closest('form').submit()">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div><!-- Langue dropdown end -->

                    {{-- Notifications dropdown popup --}}
                    <div class="dropdown">
                        <button class="{{ $unreadCount > 0 ? 'has-indicator' : '' }} w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center"
                                type="button" data-bs-toggle="dropdown">
                            <iconify-icon icon="iconoir:bell" class="text-primary-light text-xl"></iconify-icon>
                        </button>
                        <div class="dropdown-menu to-top dropdown-menu-lg p-0">
                            <div class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                <h6 class="text-lg text-primary-light fw-semibold mb-0">{{ __('Notifications') }}</h6>
                                @if($unreadCount > 0)
                                    <span class="text-primary-600 fw-semibold text-lg w-40-px h-40-px rounded-circle bg-base d-flex justify-content-center align-items-center">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </div>
                            <div class="max-h-400-px overflow-y-auto scroll-sm pe-4">
                                @forelse($recentNotifs as $notif)
                                    @php
                                        $nType   = class_basename($notif->type ?? '');
                                        $nIcons  = [
                                            'PasswordChangedNotification' => ['icon' => 'solar:lock-password-bold',  'bg' => 'bg-warning-subtle',  'text' => 'text-warning-main'],
                                            'SystemAlertNotification'     => ['icon' => 'solar:danger-triangle-bold','bg' => 'bg-danger-subtle',   'text' => 'text-danger-main'],
                                        ];
                                        $nIc     = $nIcons[$nType] ?? ['icon' => 'solar:bell-bold', 'bg' => 'bg-primary-subtle', 'text' => 'text-primary-600'];
                                        $nMsg    = $notif->data['message'] ?? $notif->data['title'] ?? 'Notification';
                                        $nUnread = is_null($notif->read_at);
                                    @endphp
                                    <a href="{{ route('user.notifications') }}"
                                       class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between {{ $nUnread ? '' : 'bg-neutral-50' }}">
                                        <div class="text-black hover-bg-transparent hover-text-primary d-flex align-items-center gap-3">
                                            <span class="w-44-px h-44-px {{ $nIc['bg'] }} {{ $nIc['text'] }} rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                                                <iconify-icon icon="{{ $nIc['icon'] }}" class="icon text-xxl"></iconify-icon>
                                            </span>
                                            <div>
                                                <h6 class="text-md fw-semibold mb-4">{{ Str::limit($nMsg, 45) }}</h6>
                                                <p class="mb-0 text-sm text-secondary-light">{{ $notif->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                        @if($nUnread)
                                            <span class="w-8-px h-8-px bg-primary-600 rounded-circle flex-shrink-0 mt-8"></span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="px-24 py-20 text-center text-secondary-light">
                                        <iconify-icon icon="solar:bell-off-bold" class="text-3xl mb-2 d-block"></iconify-icon>
                                        {{ __('Aucune notification') }}
                                    </div>
                                @endforelse
                            </div>
                            <div class="text-center py-12 px-16 d-flex justify-content-between align-items-center gap-2">
                                <a href="{{ route('user.notifications') }}" class="text-primary-600 fw-semibold text-md">{{ __('Voir toutes') }}</a>
                                @if($unreadCount > 0)
                                    <form action="{{ route('user.notifications.markAllRead') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary-600 radius-8 py-4 px-12 text-xs">
                                            {{ __('Tout marquer lu') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div><!-- Notifications dropdown end -->

                    {{-- Profil dropdown WowDash --}}
                    <div class="dropdown">
                        <button class="d-flex justify-content-center align-items-center rounded-circle"
                                type="button" data-bs-toggle="dropdown">
                            <span class="w-40-px h-40-px bg-primary-600 text-white rounded-circle d-flex justify-content-center align-items-center fw-semibold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </span>
                        </button>
                        <div class="dropdown-menu to-top dropdown-menu-sm">
                            <div class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <h6 class="text-lg text-primary-light fw-semibold mb-2">{{ auth()->user()->name }}</h6>
                                    <span class="text-secondary-light fw-medium text-sm">{{ auth()->user()->roles->first()?->name ?? __('Membre') }}</span>
                                </div>
                                <button type="button" class="hover-text-danger" data-bs-dismiss="dropdown">
                                    <iconify-icon icon="radix-icons:cross-1" class="icon text-xl"></iconify-icon>
                                </button>
                            </div>
                            <ul class="to-top-list">
                                <li>
                                    <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3"
                                       href="{{ route('user.profile') }}">
                                        <iconify-icon icon="solar:user-linear" class="icon text-xl"></iconify-icon> {{ __('Mon profil') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3"
                                       href="{{ route('user.subscription') }}">
                                        <iconify-icon icon="solar:card-recive-linear" class="icon text-xl"></iconify-icon> {{ __('Abonnement') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3"
                                       href="{{ route('user.api-tokens') }}">
                                        <iconify-icon icon="solar:key-linear" class="icon text-xl"></iconify-icon> {{ __('Tokens API') }}
                                    </a>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-danger d-flex align-items-center gap-3 w-100 border-0 bg-transparent">
                                            <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> {{ __('Déconnexion') }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div><!-- Profil dropdown end -->

                </div>
            </div>
        </div>
    </div>{{-- fin navbar-header --}}

    {{-- ========= CONTENU ========= --}}
    <div class="dashboard-main-body">

        @auth
            @if(auth()->user()->needsOnboarding())
                @livewire(\Modules\Auth\Livewire\OnboardingWizard::class)
            @endif
        @endauth

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-20" role="alert">
                <iconify-icon icon="solar:check-circle-bold" class="text-lg"></iconify-icon>
                {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-20" role="alert">
                <iconify-icon icon="solar:close-circle-bold" class="text-lg"></iconify-icon>
                {{ session('error') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 mb-20" role="alert">
                <iconify-icon icon="solar:danger-triangle-bold" class="text-lg"></iconify-icon>
                {{ session('warning') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')

    </div>

    <footer class="d-footer">
        <div class="row align-items-center justify-content-between">
            <div class="col-auto">
                <p class="mb-0 text-secondary-light">&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Tous droits réservés.') }}</p>
            </div>
        </div>
    </footer>

</main>{{-- fin dashboard-main --}}

{{-- =================== JS =================== --}}
<script src="{{ asset('assets/backoffice/wowdash/js/lib/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/lib/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/lib/iconify-icon.min.js') }}"></script>
<script src="{{ asset('assets/backoffice/wowdash/js/app.js') }}"></script>
@livewire('ai-chatbot')
@livewireScripts
@stack('js')
<script>
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
}
</script>
@if(\Modules\Settings\Facades\Settings::get('push.web_push_enabled', false) && \Modules\Settings\Facades\Settings::get('push.vapid_public_key'))
<script>
(function() {
    const vapidKey = '{{ \Modules\Settings\Facades\Settings::get("push.vapid_public_key") }}';
    if (!('PushManager' in window) || !('serviceWorker' in navigator)) return;
    if (Notification.permission === 'denied') return;

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const arr = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    async function subscribePush() {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey)
        });
        const key = sub.getKey('p256dh');
        const auth = sub.getKey('auth');
        await fetch('/api/v1/push-subscriptions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Authorization': 'Bearer ' + (document.cookie.match(/sanctum_token=([^;]+)/)?.[1] || '')
            },
            body: JSON.stringify({
                endpoint: sub.endpoint,
                keys: {
                    p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                    auth: btoa(String.fromCharCode.apply(null, new Uint8Array(auth)))
                }
            })
        });
    }

    if (Notification.permission === 'granted') {
        subscribePush();
    } else if (Notification.permission === 'default') {
        Notification.requestPermission().then(function(p) { if (p === 'granted') subscribePush(); });
    }
})();
</script>
@endif
</body>
</html>
