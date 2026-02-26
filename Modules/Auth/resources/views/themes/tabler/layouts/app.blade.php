<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Mon espace')) - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#206bc4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <!-- Tabler CSS -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/backoffice/tabler/css/tabler-vendors.min.css') }}">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="{{ asset('assets/backoffice/tabler/icons/tabler-icons.min.css') }}">
    @vite(['resources/js/app.js'])
    @livewireStyles
    @stack('css')
</head>
<body class="antialiased">

@php
    $unreadCount  = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    $recentNotifs = auth()->check() ? auth()->user()->notifications()->latest()->limit(5)->get() : collect();
    $siteName = config('app.name');
    $initial  = strtoupper(substr($siteName, 0, 1));
    $color    = '#206bc4';
    $svgBase  = '<circle cx="18" cy="18" r="15" fill="' . $color . '"/><text x="18" y="23" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text>';
    $svgLight = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#1f2937">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgDark  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="36">' . $svgBase . '<text x="42" y="23" font-family="Inter,sans-serif" font-size="14" font-weight="600" fill="#f1f5f9">' . htmlspecialchars($siteName) . '</text></svg>');
    $svgIcon  = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><circle cx="16" cy="16" r="14" fill="' . $color . '"/><text x="16" y="21" text-anchor="middle" font-family="Inter,sans-serif" font-size="14" font-weight="700" fill="white">' . htmlspecialchars($initial) . '</text></svg>');
@endphp

<div class="page">

    {{-- =================== SIDEBAR =================== --}}
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">

            {{-- Logo --}}
            <h1 class="navbar-brand navbar-brand-autodark">
                <a href="{{ route('user.dashboard') }}">
                    <img src="{{ $svgLight }}" alt="{{ $siteName }}" height="32" class="navbar-brand-image light-logo">
                    <img src="{{ $svgDark }}" alt="{{ $siteName }}" height="32" class="navbar-brand-image dark-logo d-none">
                </a>
            </h1>

            {{-- Mobile toggler --}}
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#user-sidebar-menu"
                    aria-controls="user-sidebar-menu"
                    aria-expanded="false"
                    aria-label="{{ __('Basculer le menu') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="user-sidebar-menu">
                <ul class="navbar-nav pt-lg-3">

                    {{-- Tableau de bord --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}"
                           href="{{ route('user.dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-home"></i>
                            </span>
                            <span class="nav-link-title">{{ __('Tableau de bord') }}</span>
                        </a>
                    </li>

                    {{-- Séparateur Contenu --}}
                    <li class="nav-item mt-2">
                        <div class="nav-link disabled text-uppercase text-xs opacity-50 fw-bold" style="font-size:0.65rem; letter-spacing:0.08em;">
                            {{ __('Contenu') }}
                        </div>
                    </li>

                    {{-- Mes articles (dropdown) --}}
                    @php $articlesOpen = request()->routeIs('user.articles.*'); @endphp
                    <li class="nav-item dropdown {{ $articlesOpen ? 'show' : '' }}">
                        <a class="nav-link dropdown-toggle {{ $articlesOpen ? 'show' : '' }}"
                           href="#sidebar-articles"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="false"
                           role="button"
                           aria-expanded="{{ $articlesOpen ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-file-text"></i>
                            </span>
                            <span class="nav-link-title">{{ __('Mes articles') }}</span>
                        </a>
                        <div class="dropdown-menu {{ $articlesOpen ? 'show' : '' }}" id="sidebar-articles">
                            <a class="dropdown-item {{ request()->routeIs('user.articles.index') ? 'active' : '' }}"
                               href="{{ route('user.articles.index') }}">
                                <i class="ti ti-list me-2"></i>{{ __('Liste') }}
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.articles.create') ? 'active' : '' }}"
                               href="{{ route('user.articles.create') }}">
                                <i class="ti ti-plus me-2"></i>{{ __('Nouvel article') }}
                            </a>
                        </div>
                    </li>

                    {{-- Séparateur Compte --}}
                    <li class="nav-item mt-2">
                        <div class="nav-link disabled text-uppercase text-xs opacity-50 fw-bold" style="font-size:0.65rem; letter-spacing:0.08em;">
                            {{ __('Compte') }}
                        </div>
                    </li>

                    {{-- Notifications --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('user.notifications') ? 'active' : '' }}"
                           href="{{ route('user.notifications') }}"
                           aria-label="{{ __('Notifications') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-bell"></i>
                            </span>
                            <span class="nav-link-title">
                                {{ __('Notifications') }}
                                @if($unreadCount > 0)
                                    <span class="badge bg-danger ms-auto">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                                @endif
                            </span>
                        </a>
                    </li>

                    {{-- Mon profil (dropdown) --}}
                    @php $profileOpen = request()->routeIs('user.profile', 'user.sessions', 'user.activity'); @endphp
                    <li class="nav-item dropdown {{ $profileOpen ? 'show' : '' }}">
                        <a class="nav-link dropdown-toggle {{ $profileOpen ? 'show' : '' }}"
                           href="#sidebar-profile"
                           data-bs-toggle="dropdown"
                           data-bs-auto-close="false"
                           role="button"
                           aria-expanded="{{ $profileOpen ? 'true' : 'false' }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-user"></i>
                            </span>
                            <span class="nav-link-title">{{ __('Mon profil') }}</span>
                        </a>
                        <div class="dropdown-menu {{ $profileOpen ? 'show' : '' }}" id="sidebar-profile">
                            <a class="dropdown-item {{ request()->routeIs('user.profile') ? 'active' : '' }}"
                               href="{{ route('user.profile') }}">
                                <i class="ti ti-id-badge me-2"></i>{{ __('Informations') }}
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.sessions') ? 'active' : '' }}"
                               href="{{ route('user.sessions') }}">
                                <i class="ti ti-device-laptop me-2"></i>{{ __('Sessions') }}
                            </a>
                            <a class="dropdown-item {{ request()->routeIs('user.activity') ? 'active' : '' }}"
                               href="{{ route('user.activity') }}">
                                <i class="ti ti-activity me-2"></i>{{ __("Journal d'activité") }}
                            </a>
                        </div>
                    </li>

                    {{-- Abonnement --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('user.subscription') ? 'active' : '' }}"
                           href="{{ route('user.subscription') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-credit-card"></i>
                            </span>
                            <span class="nav-link-title">{{ __('Abonnement') }}</span>
                        </a>
                    </li>

                    {{-- Tokens API --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('user.api-tokens') ? 'active' : '' }}"
                           href="{{ route('user.api-tokens') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-key"></i>
                            </span>
                            <span class="nav-link-title">{{ __('Tokens API') }}</span>
                        </a>
                    </li>

                    {{-- Administration (admin uniquement) --}}
                    @if(auth()->check() && auth()->user()->hasAnyRole(['admin', 'super_admin']))
                        <li class="nav-item mt-2">
                            <div class="nav-link disabled text-uppercase text-xs opacity-50 fw-bold" style="font-size:0.65rem; letter-spacing:0.08em;">
                                {{ __('Administration') }}
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-shield"></i>
                                </span>
                                <span class="nav-link-title">{{ __('Administration') }}</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </div>
        </div>
    </aside>

    {{-- =================== PAGE WRAPPER =================== --}}
    <div class="page-wrapper">

        {{-- =================== TOPBAR =================== --}}
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">

                {{-- Mobile sidebar toggle --}}
                <button class="navbar-toggler me-2" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#user-sidebar-menu"
                        aria-controls="user-sidebar-menu"
                        aria-expanded="false"
                        aria-label="{{ __('Basculer le menu') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="navbar-nav flex-row order-md-last gap-2">

                    {{-- Dark mode toggle --}}
                    <div class="nav-item">
                        <button class="nav-link px-2" id="dark-mode-toggle"
                                title="{{ __('Basculer le mode sombre') }}"
                                aria-label="{{ __('Basculer le mode sombre') }}">
                            <i class="ti ti-moon" id="tabler-dark-icon"></i>
                            <i class="ti ti-sun d-none" id="tabler-light-icon"></i>
                        </button>
                    </div>

                    {{-- Langue dropdown --}}
                    <div class="nav-item dropdown d-none d-sm-flex">
                        <a href="#" class="nav-link d-flex align-items-center gap-1 px-2"
                           data-bs-toggle="dropdown"
                           aria-label="{{ __('Choisir la langue') }}">
                            <i class="ti ti-world"></i>
                            <span class="d-none d-md-inline text-uppercase" style="font-size:0.75rem;">
                                {{ strtoupper(app()->getLocale()) }}
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <h6 class="dropdown-header">{{ __('Choisir la langue') }}</h6>
                            <div class="d-flex flex-column px-3 gap-2">
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <span class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag3.png') }}"
                                             alt="Français" width="24" height="24"
                                             class="rounded-circle object-fit-cover">
                                        <span class="text-sm">Français</span>
                                    </span>
                                    <form action="{{ route('locale.switch', 'fr') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="radio" class="form-check-input"
                                               {{ app()->getLocale() === 'fr' ? 'checked' : '' }}
                                               onclick="this.closest('form').submit()"
                                               aria-label="Français">
                                    </form>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-3 pb-1">
                                    <span class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/backoffice/wowdash/images/flags/flag1.png') }}"
                                             alt="English" width="24" height="24"
                                             class="rounded-circle object-fit-cover">
                                        <span class="text-sm">English</span>
                                    </span>
                                    <form action="{{ route('locale.switch', 'en') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="radio" class="form-check-input"
                                               {{ app()->getLocale() === 'en' ? 'checked' : '' }}
                                               onclick="this.closest('form').submit()"
                                               aria-label="English">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notifications dropdown --}}
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link px-2 position-relative"
                           data-bs-toggle="dropdown"
                           aria-label="{{ __('Notifications') }}">
                            <i class="ti ti-bell"></i>
                            @if($unreadCount > 0)
                                <span class="badge bg-danger badge-notification"
                                      style="position:absolute;top:2px;right:2px;font-size:0.6rem;min-width:1rem;padding:0.15rem 0.3rem;">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow" style="min-width:340px;">
                            <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                                <h6 class="mb-0 fw-semibold">{{ __('Notifications') }}</h6>
                                @if($unreadCount > 0)
                                    <span class="badge bg-primary">{{ $unreadCount }}</span>
                                @endif
                            </div>
                            <div style="max-height:320px;overflow-y:auto;">
                                @forelse($recentNotifs as $notif)
                                    @php
                                        $nMsg    = $notif->data['message'] ?? $notif->data['title'] ?? __('Notification');
                                        $nUnread = is_null($notif->read_at);
                                        $nType   = class_basename($notif->type ?? '');
                                        $nIcon   = match($nType) {
                                            'PasswordChangedNotification' => 'ti ti-lock-password',
                                            'SystemAlertNotification'     => 'ti ti-alert-triangle',
                                            default                       => 'ti ti-bell',
                                        };
                                        $nColor  = match($nType) {
                                            'PasswordChangedNotification' => 'text-warning',
                                            'SystemAlertNotification'     => 'text-danger',
                                            default                       => 'text-primary',
                                        };
                                    @endphp
                                    <a href="{{ route('user.notifications') }}"
                                       class="d-flex align-items-start gap-3 px-3 py-2 border-bottom text-decoration-none {{ $nUnread ? '' : 'bg-light' }}"
                                       style="color:inherit;">
                                        <span class="d-flex align-items-center justify-content-center rounded-circle bg-light {{ $nColor }}"
                                              style="width:36px;height:36px;flex-shrink:0;">
                                            <i class="{{ $nIcon }}"></i>
                                        </span>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="text-truncate small fw-medium">{{ Str::limit($nMsg, 50) }}</div>
                                            <div class="text-muted" style="font-size:0.75rem;">{{ $notif->created_at->diffForHumans() }}</div>
                                        </div>
                                        @if($nUnread)
                                            <span class="rounded-circle bg-primary flex-shrink-0 mt-1"
                                                  style="width:8px;height:8px;display:inline-block;"></span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="text-center py-4 text-muted">
                                        <i class="ti ti-bell-off d-block mb-1" style="font-size:1.5rem;"></i>
                                        {{ __('Aucune notification') }}
                                    </div>
                                @endforelse
                            </div>
                            <div class="d-flex align-items-center justify-content-between px-3 py-2">
                                <a href="{{ route('user.notifications') }}" class="text-primary small fw-semibold">
                                    {{ __('Voir toutes') }}
                                </a>
                                @if($unreadCount > 0)
                                    <form action="{{ route('user.notifications.markAllRead') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            {{ __('Tout marquer lu') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Profil dropdown --}}
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex align-items-center gap-2 px-2"
                           data-bs-toggle="dropdown"
                           aria-label="{{ __('Mon profil') }}">
                            <span class="avatar avatar-sm rounded-circle"
                                  style="background-color:{{ $color }};color:white;">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                            </span>
                            <span class="d-none d-xl-block">
                                <div class="small fw-medium lh-1">{{ auth()->user()->name ?? '' }}</div>
                                <div class="text-muted" style="font-size:0.7rem;">{{ auth()->user()->roles->first()?->name ?? __('Membre') }}</div>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <div class="px-3 py-2 border-bottom">
                                <div class="fw-semibold">{{ auth()->user()->name ?? '' }}</div>
                                <div class="text-muted small">{{ auth()->user()->email ?? '' }}</div>
                            </div>
                            <a href="{{ route('user.profile') }}" class="dropdown-item">
                                <i class="ti ti-user me-2"></i>{{ __('Mon profil') }}
                            </a>
                            <a href="{{ route('user.subscription') }}" class="dropdown-item">
                                <i class="ti ti-credit-card me-2"></i>{{ __('Abonnement') }}
                            </a>
                            <a href="{{ route('user.api-tokens') }}" class="dropdown-item">
                                <i class="ti ti-key me-2"></i>{{ __('Tokens API') }}
                            </a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="ti ti-logout me-2"></i>{{ __('Déconnexion') }}
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        {{-- =================== CONTENU =================== --}}
        <div class="page-body">
            <div class="container-xl">

                @auth
                    @if(auth()->user()->needsOnboarding())
                        @livewire(\Modules\Auth\Livewire\OnboardingWizard::class)
                    @endif
                @endauth

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-circle-check"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-alert-circle"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-alert-triangle"></i>
                            <div>{{ session('warning') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fermer') }}"></button>
                    </div>
                @endif

                @yield('content')

            </div>
        </div>

        {{-- =================== FOOTER =================== --}}
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center">
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <p class="mb-0 text-secondary">
                            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('Tous droits réservés.') }}
                        </p>
                    </div>
                </div>
            </div>
        </footer>

    </div>{{-- fin page-wrapper --}}

</div>{{-- fin page --}}

{{-- =================== JS =================== --}}
<script src="{{ asset('assets/backoffice/tabler/js/tabler.min.js') }}" defer></script>
<script src="{{ asset('assets/backoffice/tabler/js/tabler-theme.min.js') }}" defer></script>

<script>
// Dark mode toggle
document.addEventListener('DOMContentLoaded', function() {
    var toggle   = document.getElementById('dark-mode-toggle');
    var html     = document.documentElement;
    var darkIcon = document.getElementById('tabler-dark-icon');
    var lightIcon = document.getElementById('tabler-light-icon');

    var saved = localStorage.getItem('tablerUserTheme');
    if (saved === 'dark') {
        html.setAttribute('data-bs-theme', 'dark');
        if (darkIcon)  darkIcon.classList.add('d-none');
        if (lightIcon) lightIcon.classList.remove('d-none');
    }

    if (toggle) {
        toggle.addEventListener('click', function() {
            var isDark = html.getAttribute('data-bs-theme') === 'dark';
            html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
            localStorage.setItem('tablerUserTheme', isDark ? 'light' : 'dark');
            if (darkIcon)  darkIcon.classList.toggle('d-none');
            if (lightIcon) lightIcon.classList.toggle('d-none');
        });
    }
});
</script>

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
    var vapidKey = '{{ \Modules\Settings\Facades\Settings::get("push.vapid_public_key") }}';
    if (!('PushManager' in window) || !('serviceWorker' in navigator)) return;
    if (Notification.permission === 'denied') return;

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = atob(base64);
        var arr = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; ++i) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    async function subscribePush() {
        try {
            var reg = await navigator.serviceWorker.ready;
            var sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey)
            });
            var key  = sub.getKey('p256dh');
            var auth = sub.getKey('auth');
            await fetch('/api/v1/push-subscriptions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    endpoint: sub.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                        auth:   btoa(String.fromCharCode.apply(null, new Uint8Array(auth)))
                    }
                })
            });
        } catch (e) { /* Push registration failed silently */ }
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
